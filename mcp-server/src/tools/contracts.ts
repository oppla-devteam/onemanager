import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerContractTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "contracts_list",
    "List digital contracts with filters. Contracts go through lifecycle: draft -> sent -> signed -> active -> expired/terminated.",
    {
      status: z.string().optional().describe("Filter by status (draft, sent, signed, active, expired, terminated)"),
      client_id: z.number().optional().describe("Filter by client ID"),
      search: z.string().optional().describe("Search by title or client name"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/contracts", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_get",
    "Get full contract details including signatures, history, and attachments.",
    {
      id: z.number().describe("Contract ID"),
    },
    async ({ id }) => {
      const data = await api.get(`/contracts/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_create",
    "Create a new contract for a client.",
    {
      client_id: z.number().describe("Client ID"),
      title: z.string().describe("Contract title"),
      contract_type: z.string().optional().describe("Contract type"),
      template_id: z.number().optional().describe("Template ID to use"),
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD)"),
      value: z.number().optional().describe("Contract value"),
      content: z.string().optional().describe("Contract body text (HTML)"),
      notes: z.string().optional().describe("Internal notes"),
    },
    async (params) => {
      const data = await api.post("/contracts", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_update",
    "Update an existing contract.",
    {
      id: z.number().describe("Contract ID"),
      title: z.string().optional().describe("Contract title"),
      start_date: z.string().optional().describe("Start date"),
      end_date: z.string().optional().describe("End date"),
      value: z.number().optional().describe("Contract value"),
      content: z.string().optional().describe("Contract body text"),
      notes: z.string().optional().describe("Internal notes"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/contracts/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_send",
    "Send a contract for e-signature. The client will receive an email with a link to sign via OTP verification.",
    {
      id: z.number().describe("Contract ID"),
    },
    async ({ id }) => {
      const data = await api.post(`/contracts/${id}/send`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_activate",
    "Activate a signed contract, marking it as in effect.",
    {
      id: z.number().describe("Contract ID"),
    },
    async ({ id }) => {
      const data = await api.post(`/contracts/${id}/activate`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "contracts_stats",
    "Get contract statistics: totals by status, upcoming expirations, renewal stats.",
    {},
    async () => {
      const data = await api.get("/contracts/statistics");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
