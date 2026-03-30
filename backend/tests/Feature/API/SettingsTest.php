<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_settings_endpoint(): void
    {
        $this->actingAsUser();

        // Test that the partner protection settings endpoint is accessible
        $response = $this->getJson('/api/partner-protection/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }
}
