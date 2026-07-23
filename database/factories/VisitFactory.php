<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\Employee;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Visit> */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visit_number' => 'BTM-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'employee_id' => Employee::factory(),
            'guest_name' => fake()->name(),
            'address' => fake()->address(),
            'guest_whatsapp' => '628'.fake()->numerify('##########'),
            'visit_purpose' => fake()->sentence(),
            'photo_path' => 'visits/'.now()->format('Y/m').'/'.Str::uuid().'.jpg',
            'photo_mime_type' => 'image/jpeg',
            'status' => VisitStatus::Pending,
            'decision_reason' => null,
            'decided_at' => null,
            'arrived_at' => now(),
            'idempotency_key_hash' => hash('sha256', Str::uuid()->toString()),
            'request_fingerprint' => hash('sha256', Str::random(32)),
        ];
    }
}
