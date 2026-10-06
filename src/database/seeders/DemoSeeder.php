<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowRule;
use Illuminate\Database\Seeder;

/**
 * Local demo data: one tenant with an admin, a member, a few leads and one
 * workflow rule — enough to exercise lead conversion, queued workflow
 * actions, the audit log and notifications by hand.
 *
 * Run explicitly (it is deliberately NOT called from DatabaseSeeder, so it
 * can never run against production by accident):
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Safe to run more than once. Every demo account uses the password
 * "password" — local development only.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'acme-demo'],
            ['name' => 'Acme Demo Ltd'],
        );

        $this->user($tenant, 'admin@acme-demo.test', 'Demo Admin', UserRole::Admin);
        $this->user($tenant, 'member@acme-demo.test', 'Demo Member', UserRole::Member);

        if (Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->doesntExist()) {
            Lead::factory()->count(3)->create(['tenant_id' => $tenant->id]);
        }

        $ruleExists = WorkflowRule::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('trigger', 'lead.converted')
            ->exists();

        if (! $ruleExists) {
            WorkflowRule::factory()->create([
                'tenant_id' => $tenant->id,
                'trigger' => 'lead.converted',
                'conditions' => null,
                'action' => 'create_activity',
                'action_config' => ['content' => 'Follow up with the new customer'],
                'is_active' => true,
            ]);
        }
    }

    private function user(Tenant $tenant, string $email, string $name, UserRole $role): void
    {
        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        // role is intentionally not mass-assignable (Phase 7), so the
        // factory (which bypasses fillable) is the right tool here.
        User::factory()->create([
            'name' => $name,
            'email' => $email,
            'tenant_id' => $tenant->id,
            'role' => $role,
        ]);
    }
}
