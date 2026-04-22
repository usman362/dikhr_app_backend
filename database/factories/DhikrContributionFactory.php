<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DhikrContribution> */
class DhikrContributionFactory extends Factory
{
    protected $model = DhikrContribution::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'count' => fake()->numberBetween(1, 1000),
        ];
    }
}
