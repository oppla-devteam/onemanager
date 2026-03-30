<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function actingAsUser(array $attributes = []): User
    {
        $user = $this->createUser($attributes);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }
}
