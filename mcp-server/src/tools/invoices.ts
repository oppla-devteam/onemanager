import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerInvoiceTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "invoices_list",
    "List invoices with filters. Fields: numero_fattura=invoice number, data_emissione=issue date, payment_status, type (attiva=sales/passiva=purchase).",
    {
      search: z.string().optional().describe("Search by invoice number or client name"),
      status: z.enum(["all", "pagata", "non_pagata", "parziale", "scaduta"]).optional().describe("Payment status: pagata=paid, non_pagata=unpaid, parziale=partial, scaduta=overdue"),
      type: z.enum(["all", "attiva", "passiva"]).optional().describe("Invoice type: attiva=sales invoice, passiva=purchase invoice"),
      date_from: z.string().optional().describe("From date (YYYY-MM-DD)"),
      date_to: z.string().optional().describe("To date (YYYY-MM-DD)"),
      sort_by: z.string().optional().describe("Sort field (default: data_emissione)"),
      sort_order: z.enum(["asc", "desc"]).optional().describe("Sort direction"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/invoices", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_get",
    "Get full details of an invoice including line items and client info.",
    {
      id: z.number().describe("Invoice ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/invoices/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_create",
    "Create a new invoice. Fields: client_id, type (attiva/passiva), items array with descrizione, quantita, prezzo_unitario, aliquota_iva.",
    {
      client_id: z.number().describe("Client ID"),
      type: z.enum(["attiva", "passiva"]).describe("attiva=sales invoice, passiva=purchase invoice"),
      numero_fattura: z.string().optional().describe("Invoice number (auto-generated if omitted)"),
      data_emissione: z.string().optional().describe("Issue date (YYYY-MM-DD, defaults to today)"),
      data_scadenza: z.string().optional().describe("Due date (YYYY-MM-DD)"),
      items: z.array(z.object({
        descrizione: z.string().describe("Item description"),
        quantita: z.number().describe("Quantity"),
        prezzo_unitario: z.number().describe("Unit price"),
        aliquota_iva: z.number().optional().describe("VAT rate (default 22)"),
      })).describe("Invoice line items"),
      note: z.string().optional().describe("Invoice notes"),
    },
    async (params) => {
      const data = await api.post("/invoices", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_update",
    "Update an existing invoice.",
    {
      id: z.number().describe("Invoice ID"),
      numero_fattura: z.string().optional().describe("Invoice number"),
      data_emissione: z.string().optional().describe("Issue date"),
      data_scadenza: z.string().optional().describe("Due date"),
      payment_status: z.string().optional().describe("Payment status"),
      note: z.string().optional().describe("Notes"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/invoices/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_stats",
    "Get invoice statistics: totals, amounts by status, monthly trends.",
    {},
    async () => {
      const data = await api.get("/invoices-stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_send_to_fic",
    "Send an invoice to Fatture in Cloud (Italian accounting software). Creates or updates the invoice in FIC.",
    {
      id: z.number().describe("Invoice ID"),
    },
    async ({ id }) => {
      const data = await api.post(`/invoices/${id}/send-to-fic`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_send_sdi",
    "Send an invoice to SDI (Sistema di Interscambio) for Italian electronic invoicing compliance.",
    {
      id: z.number().describe("Invoice ID"),
    },
    async ({ id }) => {
      const data = await api.post(`/invoices/${id}/send-sdi`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_mark_paid",
    "Mark an invoice as paid.",
    {
      id: z.number().describe("Invoice ID"),
      payment_date: z.string().optional().describe("Payment date (YYYY-MM-DD)"),
      payment_method: z.string().optional().describe("Payment method"),
    },
    async ({ id, ...fields }) => {
      const data = await api.post(`/invoices/${id}/mark-paid`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "invoices_credit_note",
    "Create a credit note (nota di credito) for an existing invoice.",
    {
      id: z.number().describe("Invoice ID to credit"),
    },
    async ({ id }) => {
      const data = await api.post(`/invoices/${id}/credit-note`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
