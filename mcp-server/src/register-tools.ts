import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { OneManagerAPI } from "./api-client.js";
import { registerDashboardTools } from "./tools/dashboard.js";
import { registerOrderTools } from "./tools/orders.js";
import { registerClientTools } from "./tools/clients.js";
import { registerPartnerTools } from "./tools/partners.js";
import { registerInvoiceTools } from "./tools/invoices.js";
import { registerPaymentTools } from "./tools/payments.js";
import { registerDeliveryTools } from "./tools/deliveries.js";
import { registerRiderTools } from "./tools/riders.js";
import { registerContractTools } from "./tools/contracts.js";
import { registerTaskTools } from "./tools/tasks.js";
import { registerCrmTools } from "./tools/crm.js";
import { registerRestaurantTools } from "./tools/restaurants.js";
import { registerMenuTools } from "./tools/menus.js";
import { registerSupplierTools } from "./tools/suppliers.js";
import { registerDeliveryZoneTools } from "./tools/delivery-zones.js";
import { registerAccountingTools } from "./tools/accounting.js";
import { registerPartnerProtectionTools } from "./tools/partner-protection.js";
import { registerReportTools } from "./tools/reports.js";
import { registerOpplaTools } from "./tools/oppla.js";
import { registerOnboardingTools } from "./tools/onboarding.js";
import { registerCustomerOrderingTools } from "./tools/customer-ordering.js";

type ToolGroup = (server: McpServer, api: OneManagerAPI) => void;

/**
 * Wrap McpServer.tool() so that any error thrown inside a tool callback
 * is returned as an MCP error response with full details, instead of the
 * SDK's generic "Error occurred during tool execution" message.
 */
function wrapToolWithErrorHandling(server: McpServer): void {
  const originalTool: any = server.tool.bind(server);
  (server as any).tool = function (...args: any[]) {
    const lastIdx = args.length - 1;
    const cb = args[lastIdx];
    if (typeof cb === "function") {
      args[lastIdx] = async (...cbArgs: any[]) => {
        try {
          return await cb(...cbArgs);
        } catch (error: any) {
          const message = error instanceof Error ? error.message : String(error);
          return {
            content: [{ type: "text" as const, text: `Error: ${message}` }],
            isError: true,
          };
        }
      };
    }
    return (originalTool as Function).apply(null, args);
  };
}

/**
 * Tool groups mapped to a key name for role-based filtering.
 */
const toolGroups: Record<string, ToolGroup> = {
  dashboard: registerDashboardTools,
  orders: registerOrderTools,
  clients: registerClientTools,
  partners: registerPartnerTools,
  invoices: registerInvoiceTools,
  payments: registerPaymentTools,
  deliveries: registerDeliveryTools,
  riders: registerRiderTools,
  contracts: registerContractTools,
  tasks: registerTaskTools,
  crm: registerCrmTools,
  restaurants: registerRestaurantTools,
  menus: registerMenuTools,
  suppliers: registerSupplierTools,
  deliveryZones: registerDeliveryZoneTools,
  accounting: registerAccountingTools,
  partnerProtection: registerPartnerProtectionTools,
  reports: registerReportTools,
  oppla: registerOpplaTools,
  onboarding: registerOnboardingTools,
  customerOrdering: registerCustomerOrderingTools,
};

/**
 * Which tool groups each role can access.
 * Roles not listed here get ALL tools (admin, super-admin).
 */
const roleToolAccess: Record<string, string[]> = {
  "rider-manager": [
    "dashboard",
    "orders",
    "deliveries",
    "riders",
    "menus",
    "restaurants",
    "customerOrdering",
  ],
};

/**
 * Register tools on an McpServer instance, filtered by user role.
 * Admin/super-admin get all tools. Other roles get only their allowed groups.
 */
export function registerAllTools(
  server: McpServer,
  api: OneManagerAPI,
  userRole?: string
): void {
  wrapToolWithErrorHandling(server);
  const allowedGroups = userRole ? roleToolAccess[userRole] : undefined;

  for (const [group, registerFn] of Object.entries(toolGroups)) {
    if (!allowedGroups || allowedGroups.includes(group)) {
      registerFn(server, api);
    }
  }
}

/**
 * Create a fully configured McpServer with all tools registered.
 */
export function createMcpServer(sanctumToken?: string): McpServer {
  const server = new McpServer({
    name: "onemanager",
    version: "1.0.0",
  });
  const api = new OneManagerAPI(sanctumToken);
  registerAllTools(server, api);
  return server;
}
