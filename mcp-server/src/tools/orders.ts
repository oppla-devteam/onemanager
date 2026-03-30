import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerOrderTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "orders_list",
    "List orders with filters. Orders come from the OPPLA platform and are linked to clients/restaurants.",
    {
      search: z.string().optional().describe("Search by order number or client name"),
      status: z.string().optional().describe("Filter by order status"),
      client_id: z.number().optional().describe("Filter by client ID"),
      period: z.enum(["all", "today", "week", "month", "year", "last_month", "last_year"]).optional().describe("Predefined period filter"),
      start_date: z.string().optional().describe("Custom start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("Custom end date (YYYY-MM-DD)"),
      page: z.number().optional().describe("Page number for pagination"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/orders", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "orders_get",
    "Get details of a specific order including client info and related invoice.",
    {
      id: z.number().describe("Order ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/orders/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "orders_stats",
    "Get order statistics: totals, counts by status, monthly trends.",
    {
      period: z.string().optional().describe("Period filter"),
    },
    async ({ period }) => {
      const data = await api.get("/orders/stats", { period });
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "orders_sync",
    "Sync orders from the OPPLA platform database. This imports new orders and updates existing ones.",
    {},
    async () => {
      const data = await api.post("/orders/sync");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "orders_delete",
    "Delete an order by ID. This will also propagate changes to related invoices.",
    {
      id: z.number().describe("Order ID to delete"),
    },
    async ({ id }) => {
      const data = await api.delete(`/orders/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
