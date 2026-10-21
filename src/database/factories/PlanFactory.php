<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'price_cents' => 4900,
            'currency' => 'USD',
            'interval' => 'month',
            'max_contacts' => 10000,
            'provider_price_id' => null,
            'is_active' => true,
        ];
    }
}
