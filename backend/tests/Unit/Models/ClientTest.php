<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_has_many_invoices(): void
    {
        $client = Client::factory()->create();

        Invoice::factory()->count(3)->create([
            'client_id' => $client->id,
        ]);

        $this->assertCount(3, $client->invoices);
        $this->assertInstanceOf(Invoice::class, $client->invoices->first());
    }

    public function test_client_has_many_restaurants(): void
    {
        $client = Client::factory()->create();

        Restaurant::factory()->count(2)->create([
            'client_id' => $client->id,
        ]);

        $this->assertCount(2, $client->restaurants);
        $this->assertInstanceOf(Restaurant::class, $client->restaurants->first());
    }

    public function test_scope_active_filters_active_clients(): void
    {
        Client::factory()->create(['status' => 'active']);
        Client::factory()->create(['status' => 'active']);
        Client::factory()->create(['status' => 'inactive']);

        $activeClients = Client::active()->get();

        $this->assertCount(2, $activeClients);
        $activeClients->each(function ($client) {
            $this->assertEquals('active', $client->status);
        });
    }

    public function test_scope_partner_oppla_filters_partners(): void
    {
        Client::factory()->create(['type' => 'partner_oppla']);
        Client::factory()->create(['type' => 'partner_oppla']);
        Client::factory()->create(['type' => 'cliente_extra']);

        $partners = Client::partnerOppla()->get();

        $this->assertCount(2, $partners);
        $partners->each(function ($client) {
            $this->assertEquals('partner_oppla', $client->type);
        });
    }

    public function test_is_partner_oppla_returns_correct_value(): void
    {
        $partner = Client::factory()->create(['type' => 'partner_oppla']);
        $nonPartner = Client::factory()->create(['type' => 'cliente_extra']);

        $this->assertTrue($partner->isPartnerOppla());
        $this->assertFalse($nonPartner->isPartnerOppla());
    }
}
