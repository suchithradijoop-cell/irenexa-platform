<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rate limiter counters live in the cache too — same reason as
        // CachingTest: the 'array' store isn't reset between tests unless
        // we do it ourselves.
        Cache::flush();
    }

    public function test_login_endpoint_is_rate_limited_after_five_attempts_per_minute(): void
    {
        $payload = ['email' => 'nobody@example.com', 'password' => 'wrong-password'];

        // The 'login' limiter (AppServiceProvider) allows 5 attempts per
        // minute per IP. The first 5 should be rejected normally for bad
        // credentials (401/422, not 429) — the limiter hasn't tripped yet.
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', $payload);
            $this->assertNotEquals(429, $response->status());
        }

        // The 6th attempt within the same minute must be blocked by the
        // limiter itself, before the login logic even runs.
        $sixth = $this->postJson('/api/v1/login', $payload);
        $sixth->assertStatus(429);
    }

    public function test_api_routes_are_rate_limited_per_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        // The 'api' limiter allows 60 requests per minute per user. 60
        // should all succeed; the 61st must be blocked.
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/v1/contacts');
            $this->assertNotEquals(429, $response->status());
        }

        $overLimit = $this->getJson('/api/v1/contacts');
        $overLimit->assertStatus(429);
    }
}
