<?php

namespace Tests\Unit\Models;

use App\Models\PartnerIncident;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_belongs_to_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();
        $incident = PartnerIncident::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->assertInstanceOf(Restaurant::class, $incident->restaurant);
        $this->assertEquals($restaurant->id, $incident->restaurant->id);
    }

    public function test_is_pending_returns_true_for_pending(): void
    {
        $incident = PartnerIncident::factory()->create([
            'status' => PartnerIncident::STATUS_PENDING,
        ]);

        $this->assertTrue($incident->isPending());
        $this->assertFalse($incident->isResolved());
    }

    public function test_is_resolved_returns_true_for_resolved(): void
    {
        $incident = PartnerIncident::factory()->create([
            'status' => PartnerIncident::STATUS_RESOLVED,
        ]);

        $this->assertTrue($incident->isResolved());
        $this->assertFalse($incident->isPending());
    }

    public function test_scope_pending_filters_correctly(): void
    {
        PartnerIncident::factory()->create(['status' => PartnerIncident::STATUS_PENDING]);
        PartnerIncident::factory()->create(['status' => PartnerIncident::STATUS_PENDING]);
        PartnerIncident::factory()->create(['status' => PartnerIncident::STATUS_RESOLVED]);

        $pending = PartnerIncident::pending()->get();

        $this->assertCount(2, $pending);
        $pending->each(function ($incident) {
            $this->assertEquals(PartnerIncident::STATUS_PENDING, $incident->status);
        });
    }

    public function test_scope_for_restaurant_filters_correctly(): void
    {
        $restaurant = Restaurant::factory()->create();
        $otherRestaurant = Restaurant::factory()->create();

        PartnerIncident::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
        PartnerIncident::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $incidents = PartnerIncident::forRestaurant($restaurant->id)->get();

        $this->assertCount(2, $incidents);
        $incidents->each(function ($incident) use ($restaurant) {
            $this->assertEquals($restaurant->id, $incident->restaurant_id);
        });
    }
}
