<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'title' => fake()->catchPhrase(),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'stage' => DealStage::Prospecting,
        ];
    }
}
