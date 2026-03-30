import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerMenuTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "menus_list",
    "List restaurant menu items with filters.",
    {
      restaurant_id: z.number().optional().describe("Filter by restaurant ID"),
      search: z.string().optional().describe("Search by item name"),
      category: z.string().optional().describe("Filter by category"),
      page: z.number().optional().describe("Page number"),
      per_page: z.number().optional().describe("Items per page"),
    },
    async (params) => {
      const data = await api.get("/menus", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "menus_create",
    "Create a new menu item for a restaurant.",
    {
      restaurant_id: z.number().describe("Restaurant ID"),
      name: z.string().describe("Item name"),
      price: z.number().describe("Item price"),
      category: z.string().optional().describe("Category"),
      description: z.string().optional().describe("Item description"),
      is_available: z.boolean().optional().describe("Availability status"),
    },
    async (params) => {
      const data = await api.post("/menus", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "menus_update",
    "Update an existing menu item.",
    {
      id: z.number().describe("Menu item ID"),
      name: z.string().optional().describe("Item name"),
      price: z.number().optional().describe("Price"),
      category: z.string().optional().describe("Category"),
      description: z.string().optional().describe("Description"),
      is_available: z.boolean().optional().describe("Availability"),
    },
    async ({ id, ...fields }) => {
      const data = await api.put(`/menus/${id}`, fields);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
