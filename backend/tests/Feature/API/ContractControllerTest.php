<?php

namespace Tests\Feature\API;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_contracts(): void
    {
        $this->actingAsUser();

        Contract::factory()->count(3)->create();

        $response = $this->getJson('/api/contracts');

        $response->assertStatus(200);
    }

    public function test_user_can_create_contract(): void
    {
        $user = $this->actingAsUser();

        $client = Client::factory()->create();

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'title' => 'Contratto di servizio delivery',
            'contract_type' => 'servizio',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'duration_months' => 12,
            'value' => 1200.00,
            'currency' => 'EUR',
            'billing_frequency' => 'monthly',
            'auto_renew' => true,
        ]);

        $response->assertStatus(201);
    }
}
