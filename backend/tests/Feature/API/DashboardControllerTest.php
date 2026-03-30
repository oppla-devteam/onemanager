<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_unified_dashboard(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/dashboard/unified');

        $response->assertStatus(200);
    }

    public function test_user_can_get_economic_kpis(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/dashboard/economic-kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'kpis',
            ]);
    }
}
