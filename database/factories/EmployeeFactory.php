<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'work_unit_id' => WorkUnit::factory(),
            'position_id' => Position::factory(),
            'employee_no' => fake()->boolean(80) ? fake()->unique()->numerify('198###########') : null,
            'name' => fake()->name(),
            'notification_contact' => fake()->optional()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
