import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerReportTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "reports_invoicing",
    "Get detailed invoicing report with breakdowns by client, type, and period.",
    {
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD)"),
    },
    async (params) => {
      const data = await api.get("/reports/invoicing", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "reports_stripe_monthly",
    "Get monthly Stripe report with transaction classification, fees, and net amounts.",
    {
      year: z.number().describe("Year (e.g. 2025)"),
      month: z.number().describe("Month (1-12)"),
    },
    async ({ year, month }) => {
      const data = await api.get(`/stripe-report/${year}/${month}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
