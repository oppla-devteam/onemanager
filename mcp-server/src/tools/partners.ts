import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerPartnerTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "partners_list",
    "List all partners (OPPLA platform partners linked to restaurants).",
    {
      search: z.string().optional().describe("Search partners"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/partners", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "partners_get",
    "Get partner details including linked client and restaurants.",
    {
      id: z.number().describe("Partner ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/partners/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "partners_stats",
    "Get partner statistics: total count, active/inactive, revenue breakdown.",
    {},
    async () => {
      const data = await api.get("/partners-stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
