import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerPartnerProtectionTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "partner_protection_incidents",
    "List partner protection incidents (delays, forgotten items, bulky items). Used to track service quality issues.",
    {
      restaurant_id: z.number().optional().describe("Filter by restaurant ID"),
      incident_type: z.string().optional().describe("Filter by type (delay, forgotten_item, bulky_unmarked)"),
      status: z.string().optional().describe("Filter by status (open, resolved)"),
      date_from: z.string().optional().describe("From date (YYYY-MM-DD)"),
      date_to: z.string().optional().describe("To date (YYYY-MM-DD)"),
      page: z.number().optional().describe("Page number"),
    },
    async (params) => {
      const data = await api.get("/partner-protection/incidents", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "partner_protection_incident_stats",
    "Get incident statistics: counts by type, resolution rates, trends.",
    {
      days: z.number().optional().describe("Number of days to look back (default 30)"),
    },
    async (params) => {
      const data = await api.get("/partner-protection/incidents/stats", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "partner_protection_report_delay",
    "Report a delivery delay incident for a restaurant.",
    {
      restaurant_id: z.number().describe("Restaurant ID"),
      delivery_id: z.number().optional().describe("Related delivery ID"),
      delay_minutes: z.number().describe("Delay in minutes"),
      notes: z.string().optional().describe("Additional notes"),
    },
    async (params) => {
      const data = await api.post("/partner-protection/incidents/delay", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "partner_protection_penalties",
    "List penalties generated from partner protection incidents.",
    {},
    async () => {
      const data = await api.get("/partner-protection/penalties");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
