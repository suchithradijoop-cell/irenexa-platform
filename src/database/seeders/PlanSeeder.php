<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * The plan catalogue is reference data the app cannot run without, so it is
 * a seeder that is safe to run repeatedly (updateOrCreate by the stable
 * key), in every environment:  php artisan db:seed --class=PlanSeeder
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['key' => 'free', 'name' => 'Free', 'price_cents' => 0, 'max_contacts' => 100],
            ['key' => 'pro', 'name' => 'Pro', 'price_cents' => 4900, 'max_contacts' => 10000],
            // null = unlimited
            ['key' => 'business', 'name' => 'Business', 'price_cents' => 14900, 'max_contacts' => null],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['key' => $plan['key']],
                $plan + ['currency' => 'USD', 'interval' => 'month', 'is_active' => true],
            );
        }
    }
}
