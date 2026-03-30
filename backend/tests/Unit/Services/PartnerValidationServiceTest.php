<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use App\Services\PartnerValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartnerValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PartnerValidationService::class);
    }

    public function test_validate_order_passes_for_active_partner(): void
    {
        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'client_id' => $client->id,
            'partner_status' => 'active',
            'is_active' => true,
        ]);

        PartnerProtectionSettings::factory()->global()->create();

        $result = $this->service->validateOrder(
            restaurantId: $restaurant->id,
        );

        // Active partner should not have partner_status errors
        // (may have time slot errors if no slots configured, but not partner errors)
        $partnerErrors = array_filter($result['errors'], function ($error) {
            return str_contains($error, 'sospeso');
        });

        $this->assertEmpty($partnerErrors);
    }

    public function test_validate_order_fails_for_suspended_partner(): void
    {
        $client = Client::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'client_id' => $client->id,
            'partner_status' => 'suspended',
            'partner_suspension_reason' => 'Too many incidents',
        ]);

        PartnerProtectionSettings::factory()->global()->create();

        $result = $this->service->validateOrder(
            restaurantId: $restaurant->id,
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        // Should contain a suspension-related error
        $hasSuspensionError = collect($result['errors'])->contains(function ($error) {
            return str_contains($error, 'sospeso');
        });

        $this->assertTrue($hasSuspensionError);
    }
}
