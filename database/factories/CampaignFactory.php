<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'group_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'target_count' => fake()->randomElement([1000, 10000, 100000]),
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function ended(): static
    {
        return $this->state(['status' => 'ended']);
    }
}
