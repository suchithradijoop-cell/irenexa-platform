<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The API contract of GET /api/v1/contacts/search. The suite runs on SQLite
 * with SEARCH_DRIVER=mysql, so these exercise the LIKE fallback; the
 * FULLTEXT SQL itself is checked live (see Lesson 16.3) and the Meilisearch
 * path in MeilisearchSearchTest.
 */
class ContactSearchTest extends TestCase
{
    use RefreshDatabase;

    private function contact(Tenant $tenant, string $name, string $email): Contact
    {
        return Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'job_title' => null,
        ]);
    }

    private function signedInTenant(): Tenant
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->create(['tenant_id' => $tenant->id]));

        return $tenant;
    }

    public function test_it_finds_contacts_by_name_and_by_email_part(): void
    {
        $tenant = $this->signedInTenant();
        $this->contact($tenant, 'John Smith', 'john@acme.test');
        $this->contact($tenant, 'Jane Doe', 'jane@globex.test');

        $this->getJson('/api/v1/contacts/search?q=joh')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Smith');

        $this->getJson('/api/v1/contacts/search?q=globex')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Jane Doe');
    }

    public function test_every_word_must_match(): void
    {
        $tenant = $this->signedInTenant();
        $this->contact($tenant, 'John Smith', 'john@acme.test');
        $this->contact($tenant, 'John Brown', 'jb@other.test');

        $this->getJson('/api/v1/contacts/search?q=john smith')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Smith');
    }

    public function test_it_never_returns_another_tenants_contacts(): void
    {
        $tenant = $this->signedInTenant();
        $this->contact($tenant, 'John Mine', 'mine@acme.test');

        $other = Tenant::factory()->create();
        $this->contact($other, 'John Foreign', 'foreign@other.test');

        $this->getJson('/api/v1/contacts/search?q=john')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Mine');
    }

    public function test_input_made_only_of_operators_returns_an_empty_page_not_an_error(): void
    {
        $this->signedInTenant();

        $this->getJson('/api/v1/contacts/search?q=%2B-*')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_query_is_validated(): void
    {
        $this->signedInTenant();

        $this->getJson('/api/v1/contacts/search')
            ->assertUnprocessable()->assertJsonValidationErrors('q');

        $this->getJson('/api/v1/contacts/search?q=a')
            ->assertUnprocessable()->assertJsonValidationErrors('q');

        $this->getJson('/api/v1/contacts/search?q[]=john')
            ->assertUnprocessable()->assertJsonValidationErrors('q');

        $this->getJson('/api/v1/contacts/search?q=john&per_page=500')
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/contacts/search?q=john')->assertUnauthorized();
    }
}
