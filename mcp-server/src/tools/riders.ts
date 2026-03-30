import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerRiderTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "riders_list",
    "List all riders from the Tookan fleet management system. Shows status, current location, and assignment.",
    {
      status: z.string().optional().describe("Filter by rider status (online, offline, busy)"),
      team_id: z.number().optional().describe("Filter by team ID"),
    },
    async (params) => {
      const data = await api.get("/riders", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_get",
    "Get detailed information about a specific rider including current location and performance stats.",
    {
      id: z.number().describe("Rider ID (fleet_id)"),
    },
    async ({ id }) => {
      const data = await api.get(`/riders/${id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_realtime",
    "Get real-time positions and status of all active riders. Returns GPS coordinates and current task info.",
    {},
    async () => {
      const data = await api.get("/riders/realtime");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_tasks",
    "Get all delivery tasks assigned to a specific rider.",
    {
      id: z.number().describe("Rider ID (fleet_id)"),
    },
    async ({ id }) => {
      const data = await api.get(`/riders/${id}/tasks`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_unassigned_tasks",
    "Get all unassigned delivery tasks waiting to be assigned to a rider.",
    {},
    async () => {
      const data = await api.get("/riders/unassigned-tasks");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_teams",
    "Get rider teams. Teams are used to group riders by area or shift.",
    {},
    async () => {
      const data = await api.get("/riders/teams");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "riders_notify",
    "Send a push notification to one or more riders via Tookan. Use this to communicate with riders in the field.",
    {
      fleet_ids: z.array(z.string()).describe("Array of rider fleet IDs to notify"),
      message: z.string().min(4).max(160).describe("Notification message (4-160 characters)"),
    },
    async (params) => {
      const data = await api.post("/riders/notify", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
