<?php

namespace Tests\Feature\API;

use App\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_deliveries(): void
    {
        $this->actingAsUser();

        Delivery::factory()->count(3)->create();

        $response = $this->getJson('/api/deliveries');

        $response->assertStatus(200);
    }
}
