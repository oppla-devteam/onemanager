import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerPaymentTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "payments_list",
    "List payment transactions from Stripe and other sources.",
    {
      source: z.string().optional().describe("Filter by payment source"),
      date_from: z.string().optional().describe("From date (YYYY-MM-DD)"),
      date_to: z.string().optional().describe("To date (YYYY-MM-DD)"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/payments", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "payments_create",
    "Create a manual payment transaction record.",
    {
      amount: z.number().describe("Payment amount"),
      type: z.string().describe("Payment type"),
      transaction_date: z.string().describe("Transaction date (YYYY-MM-DD)"),
      description: z.string().optional().describe("Payment description"),
      client_id: z.number().optional().describe("Associated client ID"),
      invoice_id: z.number().optional().describe("Associated invoice ID"),
    },
    async (params) => {
      const data = await api.post("/payments", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "payments_stats",
    "Get payment statistics: totals, by source, monthly trends.",
    {},
    async () => {
      const data = await api.get("/payments-stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "payments_aggregate_by_client",
    "Get payments aggregated by client: total paid, number of transactions per client.",
    {},
    async () => {
      const data = await api.get("/payments/aggregate-by-client");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "payments_stripe_sync",
    "Sync payment transactions from Stripe. Imports new charges, refunds, and payouts.",
    {},
    async () => {
      const data = await api.post("/stripe/sync");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
