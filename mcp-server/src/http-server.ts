import { randomUUID } from "node:crypto";
import express from "express";
import cors from "cors";
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { mcpAuthRouter } from "@modelcontextprotocol/sdk/server/auth/router.js";
import { requireBearerAuth } from "@modelcontextprotocol/sdk/server/auth/middleware/bearerAuth.js";
import { isInitializeRequest } from "@modelcontextprotocol/sdk/types.js";
import { registerAllTools } from "./register-tools.js";
import { OneManagerAPI } from "./api-client.js";
import { BinkOAuthProvider } from "./auth/bink-oauth-provider.js";
import { config } from "./config.js";

// ── Setup ──

const provider = new BinkOAuthProvider();

const serverUrl = new URL(`${config.mcpServerUrl}/mcp-server`);
const issuerUrl = new URL(config.mcpServerUrl);

const app = express();
app.use(cors());
app.use(express.json());
app.set("trust proxy", 1);

// ── OAuth routes (handled by MCP SDK) ──

app.use(
  mcpAuthRouter({
    provider,
    issuerUrl,
    baseUrl: issuerUrl,
    resourceServerUrl: serverUrl,
    resourceName: "OneManager MCP Server",
  })
);

// ── Bink OAuth callback ──

app.get("/auth/bink/callback", (req, res) => {
  const state = req.query.state as string | undefined;
  if (state && provider.hasPendingAuthorization(state)) {
    // MCP OAuth flow — handle it
    provider.handleBinkCallback(req, res);
  } else {
    // Frontend Bink login — let nginx serve the SPA
    res.status(404).send("Not found");
  }
});

// ── Auth middleware for MCP endpoints ──

const authMiddleware = requireBearerAuth({
  verifier: provider,
  requiredScopes: [],
});

// ── Session management ──

interface SessionData {
  transport: StreamableHTTPServerTransport;
  server: McpServer;
}

const sessions = new Map<string, SessionData>();

function createServerForSession(sanctumToken: string, userRole?: string): McpServer {
  const server = new McpServer({
    name: "onemanager",
    version: "1.0.0",
  });
  const api = new OneManagerAPI(sanctumToken);
  registerAllTools(server, api, userRole);
  return server;
}

// ── MCP POST endpoint ──

app.post("/mcp-server", authMiddleware, async (req, res) => {
  const sessionId = req.headers["mcp-session-id"] as string | undefined;

  try {
    let transport: StreamableHTTPServerTransport;

    if (sessionId && sessions.has(sessionId)) {
      // Reuse existing session
      transport = sessions.get(sessionId)!.transport;
    } else if (!sessionId && isInitializeRequest(req.body)) {
      // New initialization request
      const mcpToken = req.auth?.token;
      const sanctumToken = mcpToken
        ? provider.getSanctumToken(mcpToken) || config.authToken
        : config.authToken;

      // Get user role for tool filtering
      const userInfo = mcpToken ? provider.getUserInfo(mcpToken) : undefined;
      const userRole = (userInfo?.role as string) || undefined;

      transport = new StreamableHTTPServerTransport({
        sessionIdGenerator: () => randomUUID(),
        onsessioninitialized: (sid: string) => {
          const server = createServerForSession(sanctumToken, userRole);
          sessions.set(sid, { transport, server });
          server.connect(transport);
          console.log(`Session initialized: ${sid} (role: ${userRole || "admin"})`);
        },
      });

      transport.onclose = () => {
        const sid = transport.sessionId;
        if (sid && sessions.has(sid)) {
          console.log(`Session closed: ${sid}`);
          sessions.delete(sid);
        }
      };

      await transport.handleRequest(req, res, req.body);
      return;
    } else {
      res.status(400).json({
        jsonrpc: "2.0",
        error: {
          code: -32000,
          message: "Bad Request: No valid session ID provided",
        },
        id: null,
      });
      return;
    }

    await transport.handleRequest(req, res, req.body);
  } catch (error) {
    console.error("Error handling MCP POST:", error);
    if (!res.headersSent) {
      res.status(500).json({
        jsonrpc: "2.0",
        error: { code: -32603, message: "Internal server error" },
        id: null,
      });
    }
  }
});

// ── MCP GET endpoint (SSE streams) ──

app.get("/mcp-server", authMiddleware, async (req, res) => {
  const sessionId = req.headers["mcp-session-id"] as string | undefined;
  if (!sessionId || !sessions.has(sessionId)) {
    res.status(400).send("Invalid or missing session ID");
    return;
  }

  const { transport } = sessions.get(sessionId)!;
  await transport.handleRequest(req, res);
});

// ── MCP DELETE endpoint (session termination) ──

app.delete("/mcp-server", authMiddleware, async (req, res) => {
  const sessionId = req.headers["mcp-session-id"] as string | undefined;
  if (!sessionId || !sessions.has(sessionId)) {
    res.status(400).send("Invalid or missing session ID");
    return;
  }

  try {
    const { transport } = sessions.get(sessionId)!;
    await transport.handleRequest(req, res);
  } catch (error) {
    console.error("Error handling session termination:", error);
    if (!res.headersSent) {
      res.status(500).send("Error processing session termination");
    }
  }
});

// ── Health check ──

app.get("/health", (_req, res) => {
  res.json({
    status: "ok",
    sessions: sessions.size,
    timestamp: new Date().toISOString(),
  });
});

// ── Start server ──

app.listen(config.mcpPort, "0.0.0.0", () => {
  console.log(`OneManager MCP HTTP Server listening on port ${config.mcpPort}`);
  console.log(`Server URL: ${serverUrl}`);
  console.log(`OAuth callback: ${config.mcpServerUrl}/auth/bink/callback`);
});

// ── Graceful shutdown ──

process.on("SIGINT", async () => {
  console.log("Shutting down MCP server...");
  for (const [sessionId, { transport }] of sessions) {
    try {
      await transport.close();
      console.log(`Closed session: ${sessionId}`);
    } catch (error) {
      console.error(`Error closing session ${sessionId}:`, error);
    }
  }
  sessions.clear();
  console.log("Shutdown complete");
  process.exit(0);
});

process.on("SIGTERM", async () => {
  console.log("Received SIGTERM, shutting down...");
  for (const [, { transport }] of sessions) {
    try {
      await transport.close();
    } catch {}
  }
  sessions.clear();
  process.exit(0);
});
