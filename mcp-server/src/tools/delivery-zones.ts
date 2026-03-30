import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerDeliveryZoneTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "delivery_zones_list",
    "List all delivery zones with their coverage areas and pricing.",
    {},
    async () => {
      const data = await api.get("/delivery-zones");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "delivery_zones_create",
    "Create a new delivery zone with coverage area and pricing rules.",
    {
      name: z.string().describe("Zone name"),
      city: z.string().optional().describe("City"),
      postal_codes: z.string().optional().describe("Comma-separated postal codes"),
      is_active: z.boolean().optional().describe("Active status"),
    },
    async (params) => {
      const data = await api.post("/delivery-zones", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "delivery_zones_sync",
    "Sync delivery zones from the OPPLA platform.",
    {},
    async () => {
      const data = await api.post("/delivery-zones/sync");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
