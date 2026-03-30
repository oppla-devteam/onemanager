<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_payments(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200);
    }

    public function test_user_can_get_payment_stats(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/payments-stats');

        $response->assertStatus(200);
    }
}
