<?php

namespace Database\Factories;

use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkUnit> */
class WorkUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
