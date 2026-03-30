<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\PartnerPenalty;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerPenaltyTest extends TestCase
{
    use RefreshDatabase;

    public function test_penalty_belongs_to_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();
        $penalty = PartnerPenalty::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->assertInstanceOf(Restaurant::class, $penalty->restaurant);
        $this->assertEquals($restaurant->id, $penalty->restaurant->id);
    }

    public function test_is_pending_returns_correct_value(): void
    {
        $pendingPenalty = PartnerPenalty::factory()->create([
            'billing_status' => PartnerPenalty::STATUS_PENDING,
        ]);
        $invoicedPenalty = PartnerPenalty::factory()->create([
            'billing_status' => PartnerPenalty::STATUS_INVOICED,
        ]);

        $this->assertTrue($pendingPenalty->isPending());
        $this->assertFalse($invoicedPenalty->isPending());
    }

    public function test_waive_changes_status(): void
    {
        $penalty = PartnerPenalty::factory()->create([
            'billing_status' => PartnerPenalty::STATUS_PENDING,
        ]);

        $penalty->waive();
        $penalty->refresh();

        $this->assertEquals(PartnerPenalty::STATUS_WAIVED, $penalty->billing_status);
    }

    public function test_mark_as_invoiced_sets_invoice_id(): void
    {
        $penalty = PartnerPenalty::factory()->create([
            'billing_status' => PartnerPenalty::STATUS_PENDING,
        ]);
        $invoice = Invoice::factory()->create();

        $penalty->markAsInvoiced($invoice->id);
        $penalty->refresh();

        $this->assertEquals(PartnerPenalty::STATUS_INVOICED, $penalty->billing_status);
        $this->assertEquals($invoice->id, $penalty->invoice_id);
    }
}
