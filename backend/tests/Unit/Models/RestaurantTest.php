<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\PartnerIncident;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $restaurant->client);
        $this->assertEquals($client->id, $restaurant->client->id);
    }

    public function test_restaurant_has_partner_protection_settings(): void
    {
        $restaurant = Restaurant::factory()->create();
        $settings = PartnerProtectionSettings::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->assertInstanceOf(PartnerProtectionSettings::class, $restaurant->partnerProtectionSettings);
        $this->assertEquals($settings->id, $restaurant->partnerProtectionSettings->id);
    }

    public function test_is_partner_active_returns_true_when_active(): void
    {
        $restaurant = Restaurant::factory()->create(['partner_status' => 'active']);

        $this->assertTrue($restaurant->isPartnerActive());
        $this->assertFalse($restaurant->isPartnerSuspended());
    }

    public function test_is_partner_suspended_returns_true_when_suspended(): void
    {
        $restaurant = Restaurant::factory()->create(['partner_status' => 'suspended']);

        $this->assertTrue($restaurant->isPartnerSuspended());
        $this->assertFalse($restaurant->isPartnerActive());
    }

    public function test_restaurant_has_many_incidents(): void
    {
        $restaurant = Restaurant::factory()->create();

        PartnerIncident::factory()->count(3)->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->assertCount(3, $restaurant->incidents);
        $this->assertInstanceOf(PartnerIncident::class, $restaurant->incidents->first());
    }
}
