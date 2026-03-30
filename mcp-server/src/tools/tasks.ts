import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerTaskTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "task_boards_list",
    "List all task boards. Each board contains lists, and each list contains tasks (Kanban-style).",
    {},
    async () => {
      const data = await api.get("/task-boards");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "task_boards_create",
    "Create a new task board.",
    {
      name: z.string().describe("Board name"),
      description: z.string().optional().describe("Board description"),
    },
    async (params) => {
      const data = await api.post("/task-boards", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "tasks_list",
    "List tasks with filters across all boards or a specific board.",
    {
      board_id: z.number().optional().describe("Filter by board ID"),
      task_list_id: z.number().optional().describe("Filter by list ID"),
      status: z.string().optional().describe("Filter by status"),
      assigned_to: z.number().optional().describe("Filter by assigned user ID"),
      priority: z.string().optional().describe("Filter by priority (low, medium, high, urgent)"),
    },
    async (params) => {
      const data = await api.get("/tasks", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "tasks_create",
    "Create a new task in a specific list.",
    {
      task_list_id: z.number().describe("List ID to add the task to"),
      title: z.string().describe("Task title"),
      description: z.string().optional().describe("Task description"),
      priority: z.enum(["low", "medium", "high", "urgent"]).optional().describe("Task priority"),
      assigned_to: z.number().optional().describe("User ID to assign the task to"),
      due_date: z.string().optional().describe("Due date (YYYY-MM-DD)"),
      tags: z.string().optional().describe("Comma-separated tags"),
    },
    async (params) => {
      const data = await api.post("/tasks", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "tasks_update",
    "Update a task: change status, assignment, priority, due date, etc.",
    {
      id: z.number().describe("Task ID"),
      title: z.string().optional().describe("Task title"),
      description: z.string().optional().describe("Task description"),
      status: z.string().optional().describe("Task status"),
      priority: z.enum(["low", "medium", "high", "urgent"]).optional().describe("Priority"),
      assigned_to: z.number().optional().describe("Assigned user ID"),
      due_date: z.string().optional().describe("Due date (YYYY-MM-DD)"),
      task_list_id: z.number().optional().describe("Move to different list"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/tasks/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
