<?php

namespace Tests\Feature\API;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_clients(): void
    {
        $this->actingAsUser();

        Client::factory()->count(3)->create();

        $response = $this->getJson('/api/clients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_unauthenticated_user_cannot_list_clients(): void
    {
        $response = $this->getJson('/api/clients');

        $response->assertStatus(401);
    }

    public function test_user_can_create_client(): void
    {
        $this->actingAsUser();

        $clientData = [
            'type' => 'partner_oppla',
            'ragione_sociale' => 'Test Company SRL',
            'email' => 'test@company.it',
            'piva' => 'IT12345678901',
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'phone' => '+39 333 1234567',
            'indirizzo' => 'Via Roma 1',
            'citta' => 'Milano',
            'provincia' => 'MI',
            'cap' => '20100',
        ];

        $response = $this->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
            ->assertJson([
                'ragione_sociale' => 'Test Company SRL',
                'email' => 'test@company.it',
                'type' => 'partner_oppla',
            ]);

        $this->assertDatabaseHas('clients', [
            'ragione_sociale' => 'Test Company SRL',
            'email' => 'test@company.it',
        ]);
    }

    public function test_user_can_update_client(): void
    {
        $this->actingAsUser();

        $client = Client::factory()->create([
            'ragione_sociale' => 'Old Name',
        ]);

        $response = $this->putJson("/api/clients/{$client->id}", [
            'ragione_sociale' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ragione_sociale' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'ragione_sociale' => 'Updated Name',
        ]);
    }

    public function test_user_can_delete_client(): void
    {
        $this->actingAsUser();

        $client = Client::factory()->create();

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }
}
