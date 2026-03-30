<?php

namespace Tests\Feature\API;

use App\Models\Rider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_riders(): void
    {
        $this->actingAsUser();

        Rider::factory()->count(3)->create();

        $response = $this->getJson('/api/riders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_unauthenticated_user_cannot_list_riders(): void
    {
        $response = $this->getJson('/api/riders');

        $response->assertStatus(401);
    }
}
