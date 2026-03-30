<?php

namespace Tests\Unit\Models;

use App\Models\Rider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_name_attribute_returns_full_name(): void
    {
        $rider = Rider::factory()->create([
            'first_name' => 'Marco',
            'last_name' => 'Rossi',
        ]);

        $this->assertEquals('Marco Rossi', $rider->name);
    }

    public function test_scope_available_filters_available_riders(): void
    {
        Rider::factory()->create(['status' => 'available']);
        Rider::factory()->create(['status' => 'available']);
        Rider::factory()->create(['status' => 'offline']);
        Rider::factory()->create(['status' => 'busy']);

        $available = Rider::available()->get();

        $this->assertCount(2, $available);
        $available->each(function ($rider) {
            $this->assertEquals('available', $rider->status);
        });
    }

    public function test_scope_offline_filters_offline_riders(): void
    {
        Rider::factory()->create(['status' => 'offline']);
        Rider::factory()->create(['status' => 'available']);
        Rider::factory()->create(['status' => 'offline']);

        $offline = Rider::offline()->get();

        $this->assertCount(2, $offline);
        $offline->each(function ($rider) {
            $this->assertEquals('offline', $rider->status);
        });
    }

    public function test_is_stale_returns_true_when_old(): void
    {
        $staleRider = Rider::factory()->create([
            'last_synced_at' => now()->subMinutes(20),
        ]);
        $freshRider = Rider::factory()->create([
            'last_synced_at' => now()->subMinutes(2),
        ]);
        $neverSyncedRider = Rider::factory()->create([
            'last_synced_at' => null,
        ]);

        $this->assertTrue($staleRider->isStale(10));
        $this->assertFalse($freshRider->isStale(10));
        $this->assertTrue($neverSyncedRider->isStale(10));
    }
}
