<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The 'array' cache driver (config in phpunit.xml) lives in memory
        // for the lifetime of the test process, not just one test method —
        // RefreshDatabase resets the database between tests, but nothing
        // resets the cache automatically. Without this, a key written by
        // one test could leak into and silently corrupt the next one.
        Cache::flush();
    }

    public function test_second_request_for_the_same_page_is_served_from_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Contact::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        // First call: cache miss, reads from the database, caches the result.
        $first = $this->getJson('/api/v1/contacts');
        $first->assertOk();
        $this->assertCount(3, $first->json('data'));

        // Delete every contact directly, bypassing the cached repository
        // entirely, so the database can no longer answer "3 contacts."
        Contact::query()->delete();

        // Second call: if this were hitting the database, it would now
        // return 0 contacts. Getting 3 back proves the response came from
        // Redis (well — the 'array' store in tests), not a fresh query.
        $second = $this->getJson('/api/v1/contacts');
        $second->assertOk();
        $this->assertCount(3, $second->json('data'));
    }

    public function test_creating_a_contact_invalidates_the_cached_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Contact::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        // Cache page 1 with 2 contacts.
        $before = $this->getJson('/api/v1/contacts');
        $this->assertCount(2, $before->json('data'));

        // A write happens — this must bust the cache tag, not just add a
        // row that the still-cached page never gets to see.
        $this->postJson('/api/v1/contacts', [
            'name' => 'New Contact',
            'email' => 'new.contact@example.com',
        ])->assertCreated();

        $after = $this->getJson('/api/v1/contacts');

        // 3, not 2 — proves create() flushed the tag rather than leaving
        // the old cached page (Lesson 11.3's version, before Lesson 11.4)
        // silently stale for the next 5 minutes.
        $this->assertCount(3, $after->json('data'));
    }

    public function test_cached_pages_are_isolated_per_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        Contact::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
        Contact::factory()->count(5)->create(['tenant_id' => $tenantB->id]);

        // Tenant A caches its own page 1 (2 contacts).
        Sanctum::actingAs($userA);
        $responseA = $this->getJson('/api/v1/contacts');
        $this->assertCount(2, $responseA->json('data'));

        // Tenant B must get its own 5 contacts — not Tenant A's cached 2.
        // This is the cache-layer version of the TenantScope test in
        // ApiContractTest: proving isolation holds at every layer that
        // touches tenant data, not just the database.
        Sanctum::actingAs($userB);
        $responseB = $this->getJson('/api/v1/contacts');
        $this->assertCount(5, $responseB->json('data'));
    }
}
