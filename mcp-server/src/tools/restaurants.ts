import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerRestaurantTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "restaurants_list",
    "List all restaurants from the OPPLA platform with their details, operating status, and linked partners.",
    {},
    async () => {
      const data = await api.get("/oppla/restaurants");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_close_period",
    "Mass-close ALL OPPLA restaurants for a given period. Runs a background job that closes every restaurant via Selenium automation. Returns a job_id and batch_id to track progress.",
    {
      start_date: z.string().describe("Start datetime in format YYYY-MM-DDTHH:mm (e.g. 2026-08-01T18:00)"),
      end_date: z.string().describe("End datetime in format YYYY-MM-DDTHH:mm (e.g. 2026-08-31T11:00). Must be after start_date"),
      reason: z.string().optional().describe("Reason for closure (e.g. 'Chiusura estiva', 'Festività')"),
    },
    async (params) => {
      const data = await api.post("/restaurants/close-period", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_close_status",
    "Check the status of a mass restaurant closure job. Returns progress, stats, and output.",
    {
      job_id: z.string().describe("The job ID returned by restaurants_close_period"),
    },
    async ({ job_id }) => {
      const data = await api.get(`/restaurants/close-status/${job_id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_reopen_batch",
    "Reopen all restaurants that were closed in a specific batch. Reverses a previous mass closure by deleting the holiday entries.",
    {
      batch_id: z.string().describe("The batch ID from the closure operation"),
    },
    async ({ batch_id }) => {
      const data = await api.post(`/restaurants/reopen-batch/${batch_id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_reopen_status",
    "Check the status of a mass restaurant reopen job.",
    {
      job_id: z.string().describe("The job ID returned by restaurants_reopen_batch"),
    },
    async ({ job_id }) => {
      const data = await api.get(`/restaurants/reopen-status/${job_id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_closure_batches",
    "List all recent mass closure batches with their status, dates, and stats.",
    {},
    async () => {
      const data = await api.get("/restaurants/closure-batches");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_close_single",
    "Close a single restaurant for a specific period by inserting a holiday directly into the Oppla database. Use restaurants_list to get the restaurant ID first.",
    {
      restaurant_id: z.string().describe("Oppla restaurant UUID"),
      start_date: z.string().describe("Start datetime in format YYYY-MM-DDTHH:mm (e.g. 2026-03-01T18:00)"),
      end_date: z.string().describe("End datetime in format YYYY-MM-DDTHH:mm (e.g. 2026-03-02T11:00). Must be after start_date"),
      reason: z.string().optional().describe("Reason for closure"),
    },
    async ({ restaurant_id, ...params }) => {
      const data = await api.post(`/restaurants/${restaurant_id}/close`, params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "restaurants_reopen_single",
    "Reopen a single restaurant by deleting a specific holiday from the Oppla database.",
    {
      holiday_id: z.string().describe("Oppla holiday UUID to delete"),
    },
    async ({ holiday_id }) => {
      const data = await api.delete(`/restaurants/holidays/${holiday_id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
