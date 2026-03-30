import { randomUUID } from "node:crypto";
import type { Response, Request } from "express";
import type {
  OAuthServerProvider,
  AuthorizationParams,
} from "@modelcontextprotocol/sdk/server/auth/provider.js";
import type { OAuthRegisteredClientsStore } from "@modelcontextprotocol/sdk/server/auth/clients.js";
import type { AuthInfo } from "@modelcontextprotocol/sdk/server/auth/types.js";
import type {
  OAuthClientInformationFull,
  OAuthTokens,
  OAuthTokenRevocationRequest,
} from "@modelcontextprotocol/sdk/shared/auth.js";
import { config } from "../config.js";

// ── In-memory stores ──

interface PendingAuthorization {
  client: OAuthClientInformationFull;
  params: AuthorizationParams;
}

interface StoredAuthCode {
  clientId: string;
  codeChallenge: string;
  sanctumToken: string;
  redirectUri: string;
  user: Record<string, unknown>;
}

interface StoredToken {
  sanctumToken: string;
  clientId: string;
  user: Record<string, unknown>;
  expiresAt: number;
}

// ── Client Store ──

class InMemoryClientsStore implements OAuthRegisteredClientsStore {
  private clients = new Map<string, OAuthClientInformationFull>();

  getClient(clientId: string): OAuthClientInformationFull | undefined {
    return this.clients.get(clientId);
  }

  registerClient(
    client: Omit<OAuthClientInformationFull, "client_id" | "client_id_issued_at">
  ): OAuthClientInformationFull {
    const clientId = randomUUID();
    const full: OAuthClientInformationFull = {
      ...client,
      client_id: clientId,
      client_id_issued_at: Math.floor(Date.now() / 1000),
    } as OAuthClientInformationFull;
    this.clients.set(clientId, full);
    return full;
  }
}

// ── Bink OAuth Provider ──

export class BinkOAuthProvider implements OAuthServerProvider {
  readonly clientsStore: InMemoryClientsStore;

  // State maps
  private pendingAuthorizations = new Map<string, PendingAuthorization>();
  private authCodes = new Map<string, StoredAuthCode>();
  private accessTokens = new Map<string, StoredToken>();
  private refreshTokens = new Map<string, StoredToken>();

  constructor() {
    this.clientsStore = new InMemoryClientsStore();
  }

  /**
   * Check if a state parameter corresponds to a pending MCP authorization.
   */
  hasPendingAuthorization(state: string): boolean {
    return this.pendingAuthorizations.has(state);
  }

  /**
   * Get the Sanctum token associated with an MCP access token.
   * Used by the HTTP server to create per-session API clients.
   */
  getSanctumToken(mcpToken: string): string | undefined {
    return this.accessTokens.get(mcpToken)?.sanctumToken;
  }

  /**
   * Get the user info (role, permissions) associated with an MCP access token.
   */
  getUserInfo(mcpToken: string): Record<string, unknown> | undefined {
    return this.accessTokens.get(mcpToken)?.user;
  }

  // ── OAuthServerProvider interface ──

  async authorize(
    client: OAuthClientInformationFull,
    params: AuthorizationParams,
    res: Response
  ): Promise<void> {
    // Generate a unique state to track this auth request
    const state = randomUUID();
    this.pendingAuthorizations.set(state, { client, params });

    // Build Bink OAuth authorization URL
    const binkUrl = new URL("https://binatomy.link/api/oauth/authorize");
    binkUrl.searchParams.set("client_id", config.binkClientId);
    binkUrl.searchParams.set(
      "redirect_uri",
      `${config.mcpServerUrl}/auth/bink/callback`
    );
    binkUrl.searchParams.set("response_type", "code");
    binkUrl.searchParams.set("state", state);

    res.redirect(binkUrl.toString());
  }

  async challengeForAuthorizationCode(
    _client: OAuthClientInformationFull,
    authorizationCode: string
  ): Promise<string> {
    const stored = this.authCodes.get(authorizationCode);
    if (!stored) {
      throw new Error("Invalid authorization code");
    }
    return stored.codeChallenge;
  }

  async exchangeAuthorizationCode(
    client: OAuthClientInformationFull,
    authorizationCode: string,
    _codeVerifier?: string,
    redirectUri?: string
  ): Promise<OAuthTokens> {
    const stored = this.authCodes.get(authorizationCode);
    if (!stored) {
      throw new Error("Invalid authorization code");
    }
    if (stored.clientId !== client.client_id) {
      throw new Error("Client ID mismatch");
    }
    if (redirectUri && stored.redirectUri !== redirectUri) {
      throw new Error("Redirect URI mismatch");
    }

    // Generate MCP tokens
    const accessToken = randomUUID();
    const refreshToken = randomUUID();
    const expiresIn = 3600 * 24; // 24 hours

    this.accessTokens.set(accessToken, {
      sanctumToken: stored.sanctumToken,
      clientId: client.client_id,
      user: stored.user,
      expiresAt: Math.floor(Date.now() / 1000) + expiresIn,
    });

    this.refreshTokens.set(refreshToken, {
      sanctumToken: stored.sanctumToken,
      clientId: client.client_id,
      user: stored.user,
      expiresAt: Math.floor(Date.now() / 1000) + 3600 * 24 * 30, // 30 days
    });

    // Clean up used auth code
    this.authCodes.delete(authorizationCode);

    return {
      access_token: accessToken,
      token_type: "bearer",
      expires_in: expiresIn,
      refresh_token: refreshToken,
    };
  }

  async exchangeRefreshToken(
    client: OAuthClientInformationFull,
    refreshToken: string
  ): Promise<OAuthTokens> {
    const stored = this.refreshTokens.get(refreshToken);
    if (!stored) {
      throw new Error("Invalid refresh token");
    }
    if (stored.clientId !== client.client_id) {
      throw new Error("Client ID mismatch");
    }

    // Generate new access token
    const newAccessToken = randomUUID();
    const newRefreshToken = randomUUID();
    const expiresIn = 3600 * 24;

    this.accessTokens.set(newAccessToken, {
      sanctumToken: stored.sanctumToken,
      clientId: client.client_id,
      user: stored.user,
      expiresAt: Math.floor(Date.now() / 1000) + expiresIn,
    });

    this.refreshTokens.set(newRefreshToken, {
      sanctumToken: stored.sanctumToken,
      clientId: client.client_id,
      user: stored.user,
      expiresAt: Math.floor(Date.now() / 1000) + 3600 * 24 * 30,
    });

    // Remove old refresh token
    this.refreshTokens.delete(refreshToken);

    return {
      access_token: newAccessToken,
      token_type: "bearer",
      expires_in: expiresIn,
      refresh_token: newRefreshToken,
    };
  }

  async verifyAccessToken(token: string): Promise<AuthInfo> {
    const stored = this.accessTokens.get(token);
    if (!stored) {
      throw new Error("Invalid or expired token");
    }

    if (stored.expiresAt < Math.floor(Date.now() / 1000)) {
      this.accessTokens.delete(token);
      throw new Error("Token expired");
    }

    return {
      token,
      clientId: stored.clientId,
      scopes: [],
      expiresAt: stored.expiresAt,
    };
  }

  async revokeToken(
    _client: OAuthClientInformationFull,
    request: OAuthTokenRevocationRequest
  ): Promise<void> {
    this.accessTokens.delete(request.token);
    this.refreshTokens.delete(request.token);
  }

  // ── Bink OAuth callback handler ──

  /**
   * Handle the callback from Bink OAuth.
   * Exchanges the Bink code via Laravel, verifies the user, and redirects
   * back to Claude with our own auth code.
   */
  async handleBinkCallback(req: Request, res: Response): Promise<void> {
    const code = req.query.code as string | undefined;
    const state = req.query.state as string | undefined;

    if (!code || !state) {
      res.status(400).send("Missing code or state parameter");
      return;
    }

    const pending = this.pendingAuthorizations.get(state);
    if (!pending) {
      res.status(400).send("Invalid or expired state parameter");
      return;
    }

    try {
      // Exchange Bink code via Laravel's BinkAuthController
      const laravelResponse = await fetch(`${config.apiUrl}/auth/bink`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          code,
          redirect_uri: `${config.mcpServerUrl}/auth/bink/callback`,
        }),
      });

      if (!laravelResponse.ok) {
        const errorData = (await laravelResponse.json().catch(() => ({}))) as Record<string, unknown>;
        // User not authorized or Bink auth failed
        const redirectUrl = new URL(pending.params.redirectUri);
        redirectUrl.searchParams.set("error", "access_denied");
        redirectUrl.searchParams.set(
          "error_description",
          (errorData.message as string) || "Authentication failed"
        );
        if (pending.params.state) {
          redirectUrl.searchParams.set("state", pending.params.state);
        }
        this.pendingAuthorizations.delete(state);
        res.redirect(redirectUrl.toString());
        return;
      }

      const authData = (await laravelResponse.json()) as {
        token: string;
        user: Record<string, unknown>;
      };

      // Generate our own authorization code
      const authCode = randomUUID();
      this.authCodes.set(authCode, {
        clientId: pending.client.client_id,
        codeChallenge: pending.params.codeChallenge,
        sanctumToken: authData.token,
        redirectUri: pending.params.redirectUri,
        user: authData.user,
      });

      // Redirect back to Claude with our auth code
      const redirectUrl = new URL(pending.params.redirectUri);
      redirectUrl.searchParams.set("code", authCode);
      if (pending.params.state) {
        redirectUrl.searchParams.set("state", pending.params.state);
      }

      this.pendingAuthorizations.delete(state);
      res.redirect(redirectUrl.toString());
    } catch (error) {
      console.error("Bink callback error:", error);
      const redirectUrl = new URL(pending.params.redirectUri);
      redirectUrl.searchParams.set("error", "server_error");
      redirectUrl.searchParams.set(
        "error_description",
        "Internal server error during authentication"
      );
      if (pending.params.state) {
        redirectUrl.searchParams.set("state", pending.params.state);
      }
      this.pendingAuthorizations.delete(state);
      res.redirect(redirectUrl.toString());
    }
  }
}
