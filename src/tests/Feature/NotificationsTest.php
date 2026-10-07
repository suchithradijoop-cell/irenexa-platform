<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DealCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function userIn(Tenant $tenant, UserRole $role = UserRole::Member, bool $email = true): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'notify_by_email' => $email,
        ]);
    }

    private function convertALeadAs(User $user, Tenant $tenant): void
    {
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'company_name' => 'Acme',
            'deal_title' => 'Big deal',
            'deal_amount' => 9000,
        ])->assertCreated();
    }

    public function test_only_admins_of_the_same_tenant_are_notified(): void
    {
        Notification::fake();

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->userIn($tenantA, UserRole::Admin);
        $memberA = $this->userIn($tenantA, UserRole::Member);
        $adminB = $this->userIn($tenantB, UserRole::Admin);

        $this->convertALeadAs($memberA, $tenantA);

        Notification::assertSentTo($adminA, DealCreatedNotification::class);

        // A member of the same tenant, and an admin of ANOTHER tenant,
        // must both be left out. The second one is the isolation test
        // for the one explicit tenant filter in NotifyAdminsOfDealCreated.
        Notification::assertNotSentTo($memberA, DealCreatedNotification::class);
        Notification::assertNotSentTo($adminB, DealCreatedNotification::class);
    }

    public function test_email_is_skipped_when_the_user_opted_out_but_the_inbox_is_kept(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $optedOut = $this->userIn($tenant, UserRole::Admin, email: false);
        $actor = $this->userIn($tenant);

        $this->convertALeadAs($actor, $tenant);

        Notification::assertSentTo(
            $optedOut,
            DealCreatedNotification::class,
            fn ($notification, array $channels) => $channels === ['database'],
        );
    }

    public function test_inbox_lists_counts_and_marks_notifications_as_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->userIn($tenant);

        // Real delivery (no fake): the queued notification runs inline
        // on the sync queue driver and writes the database row.
        $user->notify(new DealCreatedNotification(1, 'First', 100.0));
        $user->notify(new DealCreatedNotification(2, 'Second', 200.0));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 2);

        $list = $this->getJson('/api/v1/notifications')->assertOk();
        $this->assertCount(2, $list->json('data'));
        $this->assertArrayNotHasKey('type', $list->json('data.0'));

        $id = $list->json('data.0.id');

        $this->postJson("/api/v1/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.read', true);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread', 1);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 1);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread', 0);
    }

    public function test_a_user_cannot_read_or_mark_another_users_notification(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->userIn($tenant);
        $intruder = $this->userIn($tenant);

        $owner->notify(new DealCreatedNotification(1, 'Private', 100.0));
        $id = $owner->notifications()->firstOrFail()->id;

        Sanctum::actingAs($intruder);

        // Same tenant, different user: 404, and the owner's notification
        // is left untouched.
        $this->postJson("/api/v1/notifications/{$id}/read")
            ->assertNotFound()
            ->assertJson(['message' => 'Resource not found.']);

        $this->assertNull($owner->notifications()->firstOrFail()->read_at);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
