import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerClientTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "clients_list",
    "List clients (partners, extra clients, consumers). Each client has ragione_sociale (business name), piva (VAT), email, and linked restaurants.",
    {
      search: z.string().optional().describe("Search by business name (ragione_sociale), email, or VAT (piva)"),
      type: z.enum(["all", "partner_oppla", "cliente_extra", "consumatore"]).optional().describe("Client type filter"),
      is_active: z.boolean().optional().describe("Filter active/inactive clients"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/clients", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "clients_get",
    "Get full details of a client including linked restaurants, invoices, deliveries, and contracts.",
    {
      id: z.number().describe("Client ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/clients/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "clients_create",
    "Create a new client. Required: ragione_sociale (business name), email, type. Italian fields: piva=VAT, pec=certified email, sdi_code=SDI code, indirizzo=address, citta=city, provincia=province, cap=postal code.",
    {
      ragione_sociale: z.string().describe("Business name"),
      email: z.string().describe("Email address"),
      type: z.enum(["partner_oppla", "cliente_extra", "consumatore"]).describe("Client type"),
      piva: z.string().optional().describe("VAT number (Partita IVA)"),
      codice_fiscale: z.string().optional().describe("Tax code (Codice Fiscale)"),
      phone: z.string().optional().describe("Phone number"),
      pec: z.string().optional().describe("Certified email (PEC)"),
      sdi_code: z.string().optional().describe("SDI code for electronic invoicing"),
      indirizzo: z.string().optional().describe("Street address"),
      citta: z.string().optional().describe("City"),
      provincia: z.string().optional().describe("Province (2-letter code, e.g. MI, RM)"),
      cap: z.string().optional().describe("Postal code (CAP)"),
      has_delivery: z.boolean().optional().describe("Whether client has delivery service"),
      fee_mensile: z.number().optional().describe("Monthly fee amount"),
      fee_ordine: z.number().optional().describe("Per-order fee amount"),
      abbonamento_mensile: z.number().optional().describe("Monthly subscription amount"),
    },
    async (params) => {
      const data = await api.post("/clients", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "clients_update",
    "Update an existing client. Pass the client ID and any fields to update.",
    {
      id: z.number().describe("Client ID"),
      ragione_sociale: z.string().optional().describe("Business name"),
      email: z.string().optional().describe("Email address"),
      type: z.enum(["partner_oppla", "cliente_extra", "consumatore"]).optional().describe("Client type"),
      piva: z.string().optional().describe("VAT number"),
      codice_fiscale: z.string().optional().describe("Tax code"),
      phone: z.string().optional().describe("Phone number"),
      pec: z.string().optional().describe("Certified email (PEC)"),
      sdi_code: z.string().optional().describe("SDI code"),
      indirizzo: z.string().optional().describe("Street address"),
      citta: z.string().optional().describe("City"),
      provincia: z.string().optional().describe("Province"),
      cap: z.string().optional().describe("Postal code"),
      has_delivery: z.boolean().optional().describe("Has delivery service"),
      is_active: z.boolean().optional().describe("Active status"),
      fee_mensile: z.number().optional().describe("Monthly fee"),
      fee_ordine: z.number().optional().describe("Per-order fee"),
      abbonamento_mensile: z.number().optional().describe("Monthly subscription"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/clients/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "clients_stats",
    "Get client statistics: total count, count by type, active/inactive breakdown.",
    {},
    async () => {
      const data = await api.get("/clients-stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
