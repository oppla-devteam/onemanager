<?php

namespace Tests\Feature\API;

use App\Models\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_list_delivery_zones(): void
    {
        // The public index route does NOT require authentication
        // (per routes/api.php: Route::prefix('delivery-zones') outside auth middleware)
        DeliveryZone::create([
            'name' => 'Centro Livorno',
            'city' => 'Livorno',
            'price_ranges' => [],
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/delivery-zones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_authenticated_user_can_create_zone(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/delivery-zones', [
            'name' => 'Zona Nord',
            'city' => 'Milano',
            'description' => 'Zona nord di Milano',
            'is_active' => true,
            'color' => '#ff0000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('delivery_zones', [
            'name' => 'Zona Nord',
            'city' => 'Milano',
        ]);
    }
}
