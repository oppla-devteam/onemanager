import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerCrmTools(server: McpServer, api: OneManagerAPI) {
  // ─── LEADS ──────────────────────────────────────────────
  server.tool(
    "crm_leads_list",
    "List CRM leads with filters. Leads represent potential customers in the sales pipeline.",
    {
      search: z.string().optional().describe("Search by company name, contact name, or email"),
      status: z.string().optional().describe("Filter by status (new, contacted, qualified, unqualified)"),
      source: z.string().optional().describe("Filter by lead source"),
      rating: z.string().optional().describe("Filter by rating (hot, warm, cold)"),
      assigned_to: z.number().optional().describe("Filter by assigned user ID"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/crm/leads", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_leads_create",
    "Create a new CRM lead.",
    {
      company_name: z.string().describe("Company/business name"),
      contact_name: z.string().optional().describe("Contact person name"),
      email: z.string().optional().describe("Email address"),
      phone: z.string().optional().describe("Phone number"),
      source: z.string().optional().describe("Lead source (web, referral, cold_call, etc.)"),
      priority: z.string().optional().describe("Priority level"),
      industry: z.string().optional().describe("Business industry"),
      estimated_value: z.number().optional().describe("Estimated deal value"),
      notes: z.string().optional().describe("Notes"),
      assigned_to: z.number().optional().describe("Assign to user ID"),
    },
    async (params) => {
      const data = await api.post("/crm/leads", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_leads_update",
    "Update an existing lead.",
    {
      id: z.number().describe("Lead ID"),
      company_name: z.string().optional().describe("Company name"),
      contact_name: z.string().optional().describe("Contact person"),
      email: z.string().optional().describe("Email"),
      phone: z.string().optional().describe("Phone"),
      status: z.string().optional().describe("Lead status"),
      rating: z.string().optional().describe("Rating (hot, warm, cold)"),
      source: z.string().optional().describe("Lead source"),
      estimated_value: z.number().optional().describe("Estimated value"),
      notes: z.string().optional().describe("Notes"),
      assigned_to: z.number().optional().describe("Assigned user ID"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/crm/leads/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_leads_stats",
    "Get lead statistics: total count, by status, conversion rates, sources breakdown.",
    {},
    async () => {
      const data = await api.get("/crm/leads/stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_leads_convert",
    "Convert a lead to a client or opportunity.",
    {
      id: z.number().describe("Lead ID to convert"),
      convert_to: z.enum(["client", "opportunity"]).describe("What to convert the lead into"),
    },
    async ({ id, convert_to }) => {
      const endpoint = convert_to === "client"
        ? `/crm/leads/${id}/convert-to-client`
        : `/crm/leads/${id}/convert-to-opportunity`;
      const data = await api.post(endpoint);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  // ─── OPPORTUNITIES ──────────────────────────────────────
  server.tool(
    "crm_opportunities_list",
    "List sales opportunities in the pipeline.",
    {
      search: z.string().optional().describe("Search by name or client"),
      status: z.string().optional().describe("Filter by status"),
      assigned_to: z.number().optional().describe("Filter by assigned user"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/crm/opportunities", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_opportunities_create",
    "Create a new sales opportunity.",
    {
      name: z.string().describe("Opportunity name"),
      amount: z.number().optional().describe("Deal amount"),
      expected_close_date: z.string().optional().describe("Expected close date (YYYY-MM-DD)"),
      pipeline_stage_id: z.number().optional().describe("Pipeline stage ID"),
      client_id: z.number().optional().describe("Client ID"),
      lead_id: z.number().optional().describe("Source lead ID"),
      description: z.string().optional().describe("Description"),
      assigned_to: z.number().optional().describe("Assigned user ID"),
    },
    async (params) => {
      const data = await api.post("/crm/opportunities", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_opportunities_update",
    "Update an existing opportunity.",
    {
      id: z.number().describe("Opportunity ID"),
      name: z.string().optional().describe("Opportunity name"),
      amount: z.number().optional().describe("Deal amount"),
      expected_close_date: z.string().optional().describe("Expected close date"),
      pipeline_stage_id: z.number().optional().describe("Pipeline stage ID"),
      description: z.string().optional().describe("Description"),
      assigned_to: z.number().optional().describe("Assigned user ID"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/crm/opportunities/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_opportunities_stats",
    "Get opportunity statistics: pipeline overview, conversion rates, total deal value.",
    {},
    async () => {
      const data = await api.get("/crm/opportunities/stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_opportunities_outcome",
    "Mark an opportunity as won or lost.",
    {
      id: z.number().describe("Opportunity ID"),
      outcome: z.enum(["won", "lost"]).describe("Outcome: won or lost"),
      lost_reason: z.string().optional().describe("Reason for losing (required when outcome=lost)"),
    },
    async ({ id, outcome, lost_reason }) => {
      const endpoint = outcome === "won"
        ? `/crm/opportunities/${id}/mark-as-won`
        : `/crm/opportunities/${id}/mark-as-lost`;
      const body = outcome === "lost" && lost_reason ? { lost_reason } : undefined;
      const data = await api.post(endpoint, body);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  // ─── ACTIVITIES ─────────────────────────────────────────
  server.tool(
    "crm_activities_list",
    "List CRM activities (calls, emails, meetings, tasks, notes).",
    {
      type: z.string().optional().describe("Filter by type (call, email, meeting, task, note)"),
      status: z.string().optional().describe("Filter by status"),
      assigned_to: z.number().optional().describe("Filter by assigned user"),
      related_type: z.string().optional().describe("Filter by related entity type (lead, client, opportunity)"),
      related_id: z.number().optional().describe("Filter by related entity ID"),
      page: z.number().optional().describe("Page number"),
    },
    async (params) => {
      const data = await api.get("/crm/activities", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_activities_create",
    "Create a CRM activity (call, email, meeting, task, or note).",
    {
      type: z.enum(["call", "email", "meeting", "task", "note"]).describe("Activity type"),
      subject: z.string().describe("Activity subject/title"),
      description: z.string().optional().describe("Detailed description"),
      due_date: z.string().optional().describe("Due date (YYYY-MM-DD)"),
      priority: z.string().optional().describe("Priority (low, medium, high)"),
      assigned_to: z.number().optional().describe("Assign to user ID"),
      related_type: z.string().optional().describe("Related entity type (lead, client, opportunity)"),
      related_id: z.number().optional().describe("Related entity ID"),
    },
    async (params) => {
      const data = await api.post("/crm/activities", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_activities_update",
    "Update an existing CRM activity.",
    {
      id: z.number().describe("Activity ID"),
      subject: z.string().optional().describe("Subject"),
      description: z.string().optional().describe("Description"),
      due_date: z.string().optional().describe("Due date"),
      priority: z.string().optional().describe("Priority"),
      assigned_to: z.number().optional().describe("Assigned user"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/crm/activities/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_activities_complete",
    "Mark a CRM activity as completed with optional outcome and notes.",
    {
      id: z.number().describe("Activity ID"),
      outcome: z.string().optional().describe("Activity outcome"),
      notes: z.string().optional().describe("Completion notes"),
    },
    async ({ id, ...fields }) => {
      const data = await api.post(`/crm/activities/${id}/complete`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  // ─── CAMPAIGNS ──────────────────────────────────────────
  server.tool(
    "crm_campaigns_list",
    "List marketing campaigns.",
    {
      type: z.string().optional().describe("Filter by campaign type"),
      status: z.string().optional().describe("Filter by status"),
      search: z.string().optional().describe("Search by name"),
      page: z.number().optional().describe("Page number"),
    },
    async (params) => {
      const data = await api.get("/crm/campaigns", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_campaigns_create",
    "Create a new marketing campaign.",
    {
      name: z.string().describe("Campaign name"),
      type: z.string().describe("Campaign type (email, social, event, etc.)"),
      description: z.string().optional().describe("Campaign description"),
      start_date: z.string().optional().describe("Start date (YYYY-MM-DD)"),
      end_date: z.string().optional().describe("End date (YYYY-MM-DD)"),
      budget: z.number().optional().describe("Campaign budget"),
      target_audience: z.string().optional().describe("Target audience description"),
    },
    async (params) => {
      const data = await api.post("/crm/campaigns", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "crm_campaigns_stats",
    "Get campaign statistics: totals by type, performance metrics, member counts.",
    {},
    async () => {
      const data = await api.get("/crm/campaigns/stats");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
