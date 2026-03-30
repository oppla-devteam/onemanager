import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { OneManagerAPI } from "../api-client.js";

export function registerOpplaTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "oppla_sync_database",
    "Sync the local database with OPPLA platform data (clients, partners, restaurants). Imports new records and updates existing ones.",
    {},
    async () => {
      const data = await api.post("/oppla/sync/database");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "oppla_sync_all",
    "Run a full sync: OPPLA database + Stripe transactions + Fatture in Cloud. This is a comprehensive sync operation.",
    {},
    async () => {
      const data = await api.post("/oppla/sync/all");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "oppla_users_with_restaurants",
    "Get OPPLA platform users with their associated restaurants.",
    {},
    async () => {
      const data = await api.get("/oppla/users-with-restaurants");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

}
