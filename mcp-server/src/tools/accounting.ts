import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerAccountingTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "accounting_dashboard",
    "Get accounting dashboard: bank balances, cash flow summary, reconciliation status.",
    {
      month: z.number().optional().describe("Month (1-12)"),
      year: z.number().optional().describe("Year (e.g. 2025)"),
    },
    async (params) => {
      const data = await api.get("/accounting/dashboard", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "accounting_financial_report",
    "Get financial report with income, expenses, and profit breakdown.",
    {
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD)"),
    },
    async (params) => {
      const data = await api.get("/accounting/financial-report", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "accounting_transactions",
    "List bank transactions with filters.",
    {
      date_from: z.string().optional().describe("From date (YYYY-MM-DD)"),
      date_to: z.string().optional().describe("To date (YYYY-MM-DD)"),
      category: z.string().optional().describe("Transaction category"),
      is_reconciled: z.boolean().optional().describe("Filter reconciled/unreconciled"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/accounting/transactions", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "accounting_auto_reconcile",
    "Run automatic bank reconciliation. Matches bank transactions with invoices and payments.",
    {},
    async () => {
      const data = await api.post("/accounting/auto-reconcile");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
