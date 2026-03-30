import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerDashboardTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "dashboard_unified",
    "Get the unified OneManager dashboard with all KPIs: economic metrics, delivery stats, and operational data.",
    {
      period: z.enum(["today", "week", "month", "quarter", "year", "custom"]).optional().describe("Time period filter"),
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD), required when period=custom"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD), required when period=custom"),
    },
    async ({ period, start_date, end_date }) => {
      const data = await api.get("/dashboard/unified", { period, start_date, end_date });
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "dashboard_economic_kpis",
    "Get economic KPIs: revenue, invoiced amount, payments received, outstanding amounts, trends.",
    {
      period: z.enum(["today", "week", "month", "quarter", "year", "custom"]).optional().describe("Time period filter"),
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD)"),
    },
    async ({ period, start_date, end_date }) => {
      const data = await api.get("/dashboard/economic-kpis", { period, start_date, end_date });
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "dashboard_delivery_ops",
    "Get real-time delivery operations KPIs: active deliveries, rider availability, pending tasks.",
    {},
    async () => {
      const data = await api.get("/dashboard/delivery-ops");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "dashboard_delivery_monthly",
    "Get monthly delivery summary with trends and comparisons.",
    {},
    async () => {
      const data = await api.get("/dashboard/delivery-monthly");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "dashboard_riders",
    "Get riders dashboard: online/offline status, active deliveries per rider, performance metrics.",
    {},
    async () => {
      const data = await api.get("/dashboard/riders");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
