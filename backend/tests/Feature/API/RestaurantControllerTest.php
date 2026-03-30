<?php

namespace Tests\Feature\API;

use App\Models\Client;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_unassigned_restaurants(): void
    {
        $this->actingAsUser();

        // Create restaurants: some unassigned, some assigned
        Restaurant::factory()->count(2)->create([
            'client_id' => null,
            'is_active' => true,
        ]);
        Restaurant::factory()->create(); // assigned to auto-created client

        $response = $this->getJson('/api/restaurants/unassigned');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'total',
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('total'));
    }

    public function test_user_can_assign_restaurant_to_client(): void
    {
        $this->actingAsUser();

        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create(['client_id' => null]);

        $response = $this->postJson('/api/restaurants/assign', [
            'client_id' => $client->id,
            'restaurant_ids' => [$restaurant->id],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'client_id' => $client->id,
        ]);
    }
}
