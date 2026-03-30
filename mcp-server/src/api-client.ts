import { config } from "./config.js";

export class OneManagerAPI {
  private baseUrl: string;
  private authToken: string;

  constructor(token?: string) {
    this.baseUrl = config.apiUrl.replace(/\/$/, "");
    this.authToken = token || config.authToken;
  }

  private buildUrl(path: string, params?: Record<string, unknown>): string {
    const url = new URL(`${this.baseUrl}${path}`);
    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== "") {
          url.searchParams.set(key, String(value));
        }
      }
    }
    return url.toString();
  }

  private get headers(): Record<string, string> {
    return {
      Authorization: `Bearer ${this.authToken}`,
      "Content-Type": "application/json",
      Accept: "application/json",
    };
  }

  async get(path: string, params?: Record<string, unknown>): Promise<unknown> {
    const url = this.buildUrl(path, params);
    const res = await fetch(url, { method: "GET", headers: this.headers });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`GET ${path} failed (${res.status}): ${text}`);
    }
    return res.json();
  }

  async post(path: string, body?: Record<string, unknown>): Promise<unknown> {
    const url = this.buildUrl(path);
    const res = await fetch(url, {
      method: "POST",
      headers: this.headers,
      body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`POST ${path} failed (${res.status}): ${text}`);
    }
    return res.json();
  }

  async put(path: string, body?: Record<string, unknown>): Promise<unknown> {
    const url = this.buildUrl(path);
    const res = await fetch(url, {
      method: "PUT",
      headers: this.headers,
      body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`PUT ${path} failed (${res.status}): ${text}`);
    }
    return res.json();
  }

  async delete(path: string): Promise<unknown> {
    const url = this.buildUrl(path);
    const res = await fetch(url, { method: "DELETE", headers: this.headers });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`DELETE ${path} failed (${res.status}): ${text}`);
    }
    return res.json();
  }
}
