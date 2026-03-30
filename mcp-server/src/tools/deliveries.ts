import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerDeliveryTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "deliveries_list",
    "List deliveries with filters. Deliveries are managed via Tookan integration.",
    {
      client_id: z.number().optional().describe("Filter by client ID"),
      status: z.string().optional().describe("Filter by delivery status"),
      start_date: z.string().optional().describe("From date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("To date (YYYY-MM-DD)"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/deliveries", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "deliveries_get",
    "Get full details of a specific delivery.",
    {
      id: z.number().describe("Delivery ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/deliveries/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "deliveries_create",
    "Create a new delivery record.",
    {
      client_id: z.number().describe("Client ID"),
      delivery_date: z.string().describe("Delivery date (YYYY-MM-DD)"),
      amount: z.number().optional().describe("Delivery fee amount"),
      status: z.string().optional().describe("Delivery status"),
      notes: z.string().optional().describe("Delivery notes"),
    },
    async (params) => {
      const data = await api.post("/deliveries", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "deliveries_stats",
    "Get delivery statistics: totals, by status, daily/monthly trends.",
    {},
    async () => {
      const data = await api.get("/deliveries-stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
