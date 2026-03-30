<?php

namespace Tests\Feature\API;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_invoices(): void
    {
        $this->actingAsUser();

        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_user_can_create_invoice(): void
    {
        $this->actingAsUser();

        $client = Client::factory()->create();

        $response = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'type' => 'ordinaria',
            'data_emissione' => '2026-02-01',
            'data_scadenza' => '2026-03-01',
            'items' => [
                [
                    'description' => 'Servizio delivery mensile',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'vat_rate' => 22,
                ],
            ],
        ]);

        $response->assertStatus(201);
    }

    public function test_user_can_get_invoice_stats(): void
    {
        $this->actingAsUser();

        Invoice::factory()->count(5)->create();

        $response = $this->getJson('/api/invoices-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'by_status',
            ]);
    }

    public function test_user_can_bulk_update_dates(): void
    {
        $this->actingAsUser();

        // Create invoices that are NOT sent to FIC or SDI (so they can be updated)
        $invoices = Invoice::factory()->count(2)->create([
            'fic_document_id' => null,
            'sdi_sent_at' => null,
        ]);

        $invoiceIds = $invoices->pluck('id')->toArray();

        $response = $this->postJson('/api/invoices/bulk-update-dates', [
            'invoice_ids' => $invoiceIds,
            'new_date' => '2026-03-01',
            'update_due_date' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated_count',
                    'total_requested',
                ],
            ]);
    }
}
