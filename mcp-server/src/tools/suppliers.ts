import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerSupplierTools(server: McpServer, api: OneManagerAPI) {
  // ─── SUPPLIERS ──────────────────────────────────────────
  server.tool(
    "suppliers_list",
    "List suppliers (vendors/fornitori). Each supplier has ragione_sociale, piva, contact info.",
    {
      search: z.string().optional().describe("Search by name, VAT, or email"),
      is_active: z.boolean().optional().describe("Filter active/inactive"),
      sort_by: z.string().optional().describe("Sort field"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/suppliers", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "suppliers_create",
    "Create a new supplier. Italian fields: ragione_sociale=business name, piva=VAT.",
    {
      ragione_sociale: z.string().describe("Business name"),
      piva: z.string().optional().describe("VAT number (Partita IVA)"),
      codice_fiscale: z.string().optional().describe("Tax code"),
      email: z.string().optional().describe("Email"),
      phone: z.string().optional().describe("Phone"),
      pec: z.string().optional().describe("Certified email (PEC)"),
      indirizzo: z.string().optional().describe("Address"),
      citta: z.string().optional().describe("City"),
      provincia: z.string().optional().describe("Province"),
      cap: z.string().optional().describe("Postal code"),
      category: z.string().optional().describe("Supplier category"),
      notes: z.string().optional().describe("Notes"),
    },
    async (params) => {
      const data = await api.post("/suppliers", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "suppliers_update",
    "Update an existing supplier.",
    {
      id: z.number().describe("Supplier ID"),
      ragione_sociale: z.string().optional().describe("Business name"),
      piva: z.string().optional().describe("VAT number"),
      email: z.string().optional().describe("Email"),
      phone: z.string().optional().describe("Phone"),
      is_active: z.boolean().optional().describe("Active status"),
      notes: z.string().optional().describe("Notes"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/suppliers/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "suppliers_stats",
    "Get supplier statistics: total count, active/inactive, spending breakdown.",
    {},
    async () => {
      const data = await api.get("/suppliers/stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  // ─── SUPPLIER INVOICES ──────────────────────────────────
  server.tool(
    "supplier_invoices_list",
    "List supplier invoices (fatture passive / purchase invoices).",
    {
      supplier_id: z.number().optional().describe("Filter by supplier ID"),
      payment_status: z.string().optional().describe("Filter by payment status"),
      date_from: z.string().optional().describe("From date (YYYY-MM-DD)"),
      date_to: z.string().optional().describe("To date (YYYY-MM-DD)"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/supplier-invoices", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "supplier_invoices_create",
    "Create a supplier invoice (fattura passiva). Fields: numero_fattura=invoice number, data_emissione=issue date, totale=total amount.",
    {
      supplier_id: z.number().describe("Supplier ID"),
      numero_fattura: z.string().describe("Invoice number"),
      data_emissione: z.string().describe("Issue date (YYYY-MM-DD)"),
      data_scadenza: z.string().optional().describe("Due date (YYYY-MM-DD)"),
      totale: z.number().describe("Total amount"),
      imponibile: z.number().optional().describe("Taxable amount"),
      iva: z.number().optional().describe("VAT amount"),
      category: z.string().optional().describe("Expense category"),
      notes: z.string().optional().describe("Notes"),
    },
    async (params) => {
      const data = await api.post("/supplier-invoices", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "supplier_invoices_stats",
    "Get supplier invoice statistics: totals, payment status, upcoming due dates.",
    {},
    async () => {
      const data = await api.get("/supplier-invoices/stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
