import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerCustomerOrderingTools(
  server: McpServer,
  api: OneManagerAPI
) {
  server.tool(
    "customer_browse_shops",
    "Browse available shops/activities on the platform (restaurants, pharmacies, bars, hardware stores, etc.). Returns name, address, category, and whether they have a menu available.",
    {
      search: z.string().optional().describe("Search by shop name"),
      city: z.string().optional().describe("Filter by city"),
      category: z
        .string()
        .optional()
        .describe(
          "Filter by category (e.g. pizzeria, farmacia, bar, ferramenta)"
        ),
    },
    async (params) => {
      const data = await api.get("/customer/shops", params);
      return {
        content: [
          { type: "text" as const, text: JSON.stringify(data, null, 2) },
        ],
      };
    }
  );

  server.tool(
    "customer_view_menu",
    "View the full menu/catalog of a specific shop. Shows items grouped by category with names, descriptions, prices, and availability for delivery/pickup.",
    {
      restaurant_id: z
        .number()
        .describe("Shop ID (OPPLA ID from customer_browse_shops)"),
      category: z.string().optional().describe("Filter by menu category"),
      delivery_type: z
        .enum(["delivery", "pickup"])
        .optional()
        .describe("Filter items available for delivery or pickup"),
    },
    async ({ restaurant_id, ...params }) => {
      const data = await api.get(
        `/customer/shops/${restaurant_id}/menu`,
        params
      );
      return {
        content: [
          { type: "text" as const, text: JSON.stringify(data, null, 2) },
        ],
      };
    }
  );

  server.tool(
    "customer_place_order",
    "Place an order directly with a shop. Provide the shop, items with quantities, delivery details, and customer info. Prices are calculated server-side from the menu.",
    {
      restaurant_id: z.number().describe("Shop ID (OPPLA ID)"),
      items: z
        .array(
          z.object({
            menu_item_id: z.number().describe("Menu item ID"),
            quantity: z.number().min(1).describe("Quantity"),
            notes: z
              .string()
              .optional()
              .describe("Special instructions for this item"),
          })
        )
        .min(1)
        .describe("Array of items to order"),
      delivery_type: z
        .enum(["delivery", "pickup"])
        .describe("'delivery' for home delivery, 'pickup' for self-collection"),
      customer_name: z.string().describe("Customer full name"),
      customer_phone: z.string().optional().describe("Customer phone number"),
      delivery_address: z
        .string()
        .optional()
        .describe("Delivery address (required for delivery type)"),
      notes: z.string().optional().describe("General order notes"),
    },
    async (params) => {
      const data = await api.post("/customer/orders", params);
      return {
        content: [
          { type: "text" as const, text: JSON.stringify(data, null, 2) },
        ],
      };
    }
  );

  server.tool(
    "customer_track_order",
    "Track the status of an order by order number or OPPLA order ID. Returns current status, timestamps, and order details.",
    {
      order_number: z
        .string()
        .optional()
        .describe("Order number (e.g. ORD-xxx)"),
      oppla_order_id: z
        .number()
        .optional()
        .describe("OPPLA platform order ID"),
    },
    async (params) => {
      const data = await api.get("/customer/orders/track", params);
      return {
        content: [
          { type: "text" as const, text: JSON.stringify(data, null, 2) },
        ],
      };
    }
  );
}
