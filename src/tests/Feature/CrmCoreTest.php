<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_converting_a_lead_creates_company_contact_and_deal_together(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $lead = Lead::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Jane Prospect',
            'email' => 'jane@prospect.test',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Prospect Inc',
            'deal_title' => 'Annual contract',
            'deal_amount' => 12000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('companies', ['name' => 'Prospect Inc', 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('contacts', ['email' => 'jane@prospect.test', 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('deals', ['title' => 'Annual contract', 'tenant_id' => $tenant->id]);

        $this->assertSame(LeadStatus::Qualified, $lead->fresh()->status);
    }

    public function test_a_user_cannot_link_a_contact_to_another_tenants_company(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userInTenantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $companyInTenantB = Company::factory()->create(['tenant_id' => $tenantB->id]);

        Sanctum::actingAs($userInTenantA);

        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'company_id' => $companyInTenantB->id,
        ]);

        // Not 403 (this isn't a permission problem — the user CAN create
        // contacts) and not 500 (nothing crashed) — 422, because the
        // submitted company_id fails validation for this tenant.
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['company_id']);
    }

    public function test_an_activity_can_be_logged_against_a_contact(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/contacts/{$contact->id}/activities", [
            'type' => 'call',
            'content' => 'Discussed renewal timeline.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activities', [
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'type' => 'call',
        ]);
    }

    public function test_contacts_are_tenant_isolated(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Contact::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Tenant A Contact']);
        Contact::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Tenant B Contact']);

        $userInTenantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        Sanctum::actingAs($userInTenantA);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        // Same "data" wrapping note as TenantIsolationTest — Lesson 10.2.
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Tenant A Contact']);
        $response->assertJsonMissing(['name' => 'Tenant B Contact']);
    }
}
