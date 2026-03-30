import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { OneManagerAPI } from "../api-client.js";

export function registerOnboardingTools(server: McpServer, api: OneManagerAPI) {
  server.tool(
    "onboarding_step1_client_partner",
    "Step 1: Create client (titolare) and partner account. Syncs the partner to Oppla immediately, which triggers the invite email with password reset link. Provide either full owner details OR an existing_client_id to reuse an existing client. Partner details (referent_*) are always required.",
    {
      // New owner fields
      nome: z.string().optional().describe("Owner first name"),
      cognome: z.string().optional().describe("Owner last name"),
      email: z.string().optional().describe("Owner email"),
      telefono: z.string().optional().describe("Owner phone number"),
      ragione_sociale: z.string().optional().describe("Company name (ragione sociale)"),
      piva: z.string().optional().describe("VAT number (partita IVA)"),
      codice_fiscale: z.string().optional().describe("Fiscal code"),
      indirizzo: z.string().optional().describe("Street address"),
      citta: z.string().optional().describe("City"),
      provincia: z.string().optional().describe("Province code (2 chars, e.g. MI, RM)"),
      cap: z.string().optional().describe("Postal code (5 digits)"),
      nazione: z.string().optional().describe("Country code (default: IT)"),
      pec: z.string().optional().describe("PEC email"),
      sdi_code: z.string().optional().describe("SDI code for electronic invoicing"),
      // Or use existing client
      existing_client_id: z.number().optional().describe("Use an existing client ID instead of creating a new one"),
      // Partner fields (always required)
      referent_nome: z.string().describe("Partner first name"),
      referent_cognome: z.string().describe("Partner last name"),
      referent_telefono: z.string().describe("Partner phone number"),
      referent_email: z.string().describe("Partner email - will receive Oppla invite"),
    },
    async (params) => {
      const data = await api.post("/onboarding/step-1-client-partner", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "onboarding_check_stripe",
    "Step 2: Check if the partner has completed Stripe Connect onboarding on Oppla. Returns the current stripe_confirmed status for the session.",
    {
      session_id: z.number().describe("Onboarding session ID from step 1"),
    },
    async (params) => {
      const data = await api.get(`/onboarding/step-2-stripe-status/${params.session_id}`);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "onboarding_confirm_stripe",
    "Step 2: Manually confirm that the partner has completed Stripe Connect onboarding on Oppla. Call this when you have verified that the partner's Stripe setup is complete.",
    {
      session_id: z.number().describe("Onboarding session ID from step 1"),
    },
    async (params) => {
      const data = await api.post("/onboarding/step-2-stripe-confirm", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "onboarding_step3_restaurant",
    "Step 3: Create the restaurant, configure delivery and fees, sync to Oppla, and finalize onboarding. This is the final step - generates the contract automatically. Requires Stripe Connect to be confirmed (or pass skip_stripe_check=true to override).",
    {
      session_id: z.number().describe("Onboarding session ID"),
      // Restaurant
      nome: z.string().describe("Restaurant name"),
      category: z.string().optional().describe("Restaurant category (e.g. pizzeria, sushi, etc.)"),
      description: z.string().optional().describe("Restaurant description"),
      telefono: z.string().describe("Restaurant phone number"),
      indirizzo: z.string().describe("Restaurant street address"),
      citta: z.string().describe("Restaurant city"),
      provincia: z.string().describe("Province code (2 chars)"),
      cap: z.string().describe("Postal code (5 digits)"),
      zone: z.string().optional().describe("Zone name"),
      // Delivery
      delivery_management: z.enum(["autonomous", "oppla"]).describe("'oppla' = OPPLA manages deliveries with riders, 'autonomous' = restaurant manages own deliveries"),
      delivery_zones: z.array(z.number()).optional().describe("Array of delivery zone IDs (only for 'oppla' mode). Use onboarding_delivery_zones to see available zones"),
      autonomous_zones: z.array(z.object({
        zone_name: z.string(),
        price: z.number(),
      })).optional().describe("Custom delivery zones (only for 'autonomous' mode)"),
      // Fees
      best_price: z.boolean().describe("Whether to enable best price option (lower monthly fee, higher per-order fees)"),
      activation_fee: z.number().optional().describe("One-time activation fee in EUR (default: 150.00)"),
      // Cover (optional)
      logo_url: z.string().optional().describe("URL of the restaurant logo image"),
      foto_url: z.string().optional().describe("URL of the restaurant photo/background image"),
      cover_opacity: z.number().optional().describe("Overlay opacity 0-100 (default: 50)"),
      // Override
      skip_stripe_check: z.boolean().optional().describe("Skip Stripe Connect confirmation check (default: false)"),
    },
    async (params) => {
      const data = await api.post("/onboarding/step-3-restaurant-finalize", params);
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );

  server.tool(
    "onboarding_delivery_zones",
    "Get all available delivery zones for configuring onboarding step 3 (restaurant creation).",
    {},
    async () => {
      const data = await api.get("/onboarding/delivery-zones");
      return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
    }
  );
}
