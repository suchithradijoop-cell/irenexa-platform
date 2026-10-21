<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'provider' => 'fake',
            'provider_customer_id' => 'cus_'.fake()->unique()->bothify('????####'),
            'provider_subscription_id' => 'sub_'.fake()->unique()->bothify('????####'),
            'current_period_ends_at' => now()->addMonth(),
            'canceled_at' => null,
        ];
    }
}
