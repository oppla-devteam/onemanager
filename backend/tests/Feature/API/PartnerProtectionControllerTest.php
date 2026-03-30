<?php

namespace Tests\Feature\API;

use App\Models\Client;
use App\Models\Delivery;
use App\Models\PartnerIncident;
use App\Models\PartnerPenalty;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerProtectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_incidents(): void
    {
        $this->actingAsUser();

        PartnerIncident::factory()->count(3)->create();

        $response = $this->getJson('/api/partner-protection/incidents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_user_can_report_delay_incident(): void
    {
        $this->actingAsUser();

        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create(['client_id' => $client->id]);
        $delivery = Delivery::factory()->create(['client_id' => $client->id]);

        PartnerProtectionSettings::factory()->global()->create();

        $response = $this->postJson('/api/partner-protection/incidents/delay', [
            'restaurant_id' => $restaurant->id,
            'delivery_id' => $delivery->id,
            'delay_minutes' => 20,
            'description' => 'Rider arrived late',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('partner_incidents', [
            'restaurant_id' => $restaurant->id,
            'delivery_id' => $delivery->id,
            'incident_type' => PartnerIncident::TYPE_DELAY,
            'delay_minutes' => 20,
        ]);
    }

    public function test_user_can_resolve_incident(): void
    {
        $this->actingAsUser();

        $restaurant = Restaurant::factory()->create();
        $incident = PartnerIncident::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => PartnerIncident::STATUS_PENDING,
        ]);

        PartnerProtectionSettings::factory()->global()->create();

        $response = $this->putJson("/api/partner-protection/incidents/{$incident->id}/resolve", [
            'resolution_notes' => 'Resolved after contacting partner',
            'waive_penalty' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('partner_incidents', [
            'id' => $incident->id,
            'status' => PartnerIncident::STATUS_RESOLVED,
        ]);
    }

    public function test_user_can_list_penalties(): void
    {
        $this->actingAsUser();

        PartnerPenalty::factory()->count(3)->create();

        $response = $this->getJson('/api/partner-protection/penalties');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_user_can_waive_penalty(): void
    {
        $this->actingAsUser();

        $penalty = PartnerPenalty::factory()->create([
            'billing_status' => PartnerPenalty::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/partner-protection/penalties/{$penalty->id}/waive");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('partner_penalties', [
            'id' => $penalty->id,
            'billing_status' => PartnerPenalty::STATUS_WAIVED,
        ]);
    }
}
