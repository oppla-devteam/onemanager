<?php

namespace Tests\Feature\API;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_orders(): void
    {
        $this->actingAsUser();

        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_user_can_get_order_stats(): void
    {
        $this->actingAsUser();

        Order::factory()->count(5)->create();

        $response = $this->getJson('/api/orders/stats');

        $response->assertStatus(200);
    }
}
