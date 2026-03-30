<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($client->id, $invoice->client->id);
    }

    public function test_invoice_has_many_items(): void
    {
        $invoice = Invoice::factory()->create();

        InvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->assertCount(3, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }

    public function test_scope_attiva_filters_active_invoices(): void
    {
        Invoice::factory()->create(['type' => 'attiva']);
        Invoice::factory()->create(['type' => 'attiva']);
        Invoice::factory()->create(['type' => 'passiva']);

        $activeInvoices = Invoice::attiva()->get();

        $this->assertCount(2, $activeInvoices);
        $activeInvoices->each(function ($invoice) {
            $this->assertEquals('attiva', $invoice->type);
        });
    }

    public function test_scope_pagata_filters_paid_invoices(): void
    {
        Invoice::factory()->create(['payment_status' => 'pagata']);
        Invoice::factory()->create(['payment_status' => 'pagata']);
        Invoice::factory()->create(['payment_status' => 'emessa']);

        $paidInvoices = Invoice::pagata()->get();

        $this->assertCount(2, $paidInvoices);
        $paidInvoices->each(function ($invoice) {
            $this->assertEquals('pagata', $invoice->payment_status);
        });
    }

    public function test_mark_as_paid_updates_status(): void
    {
        $invoice = Invoice::factory()->create([
            'payment_status' => 'emessa',
            'data_pagamento' => null,
        ]);

        $paymentDate = now()->toDateString();
        $invoice->markAsPaid($paymentDate, 'bonifico');

        $invoice->refresh();

        $this->assertEquals('pagata', $invoice->payment_status);
        $this->assertNotNull($invoice->data_pagamento);
        $this->assertEquals('bonifico', $invoice->payment_method);
    }
}
