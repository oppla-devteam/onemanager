<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Delivery;
use App\Models\PartnerIncident;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\PartnerIncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerIncidentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIncidentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PartnerIncidentService::class);
    }

    public function test_report_delay_creates_incident(): void
    {
        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create(['client_id' => $client->id]);
        $delivery = Delivery::factory()->create(['client_id' => $client->id]);
        $user = $this->createUser();

        // Create global settings so the threshold check works
        PartnerProtectionSettings::factory()->global()->create();

        $incident = $this->service->reportDelay(
            restaurantId: $restaurant->id,
            deliveryId: $delivery->id,
            delayMinutes: 15,
            reportedByUserId: $user->id,
            description: 'Rider arrived 15 minutes late'
        );

        $this->assertInstanceOf(PartnerIncident::class, $incident);
        $this->assertEquals($restaurant->id, $incident->restaurant_id);
        $this->assertEquals($delivery->id, $incident->delivery_id);
        $this->assertEquals(PartnerIncident::TYPE_DELAY, $incident->incident_type);
        $this->assertEquals(15, $incident->delay_minutes);
        $this->assertEquals(PartnerIncident::STATUS_PENDING, $incident->status);
        $this->assertEquals($user->id, $incident->reported_by_user_id);
    }

    public function test_report_forgotten_item_creates_incident(): void
    {
        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create(['client_id' => $client->id]);
        $delivery = Delivery::factory()->create(['client_id' => $client->id]);

        PartnerProtectionSettings::factory()->global()->create();

        $result = $this->service->reportForgottenItem(
            restaurantId: $restaurant->id,
            deliveryId: $delivery->id,
            description: 'Missing dessert from order'
        );

        $this->assertArrayHasKey('incident', $result);
        $this->assertArrayHasKey('penalty', $result);
        $this->assertInstanceOf(PartnerIncident::class, $result['incident']);
        $this->assertEquals(PartnerIncident::TYPE_FORGOTTEN_ITEM, $result['incident']->incident_type);
        $this->assertEquals(PartnerIncident::STATUS_PENDING, $result['incident']->status);
        $this->assertNotNull($result['penalty']);
    }

    public function test_resolve_incident_updates_status(): void
    {
        $restaurant = Restaurant::factory()->create();
        $incident = PartnerIncident::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => PartnerIncident::STATUS_PENDING,
        ]);
        $user = $this->createUser();

        PartnerProtectionSettings::factory()->global()->create();

        $resolved = $this->service->resolveIncident(
            incidentId: $incident->id,
            resolvedByUserId: $user->id,
            resolutionNotes: 'Issue was resolved with the partner',
        );

        $this->assertEquals(PartnerIncident::STATUS_RESOLVED, $resolved->status);
        $this->assertEquals($user->id, $resolved->resolved_by_user_id);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertEquals('Issue was resolved with the partner', $resolved->resolution_notes);
    }
}
