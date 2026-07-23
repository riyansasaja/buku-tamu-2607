<?php

namespace Database\Seeders;

use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Models\SurveyInvitation;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class DevelopmentSurveyDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Seeder survei demo hanya boleh dijalankan pada environment local.');

            return;
        }

        $token = str_repeat('S', 64);
        $tokenHash = hash('sha256', $token);
        $existingInvitation = SurveyInvitation::query()->where('token_hash', $tokenHash)->first();
        $visit = $existingInvitation?->visit ?? Visit::query()->firstOrCreate(
            ['visit_number' => 'DEMO-SURVEY-001'],
            array_merge(Visit::factory()->raw(), [
                'visit_number' => 'DEMO-SURVEY-001',
                'guest_name' => 'Tamu Demo Survei',
                'status' => VisitStatus::Accepted,
                'decided_at' => now(),
            ]),
        );

        $invitation = $existingInvitation ?? SurveyInvitation::query()->firstOrNew(['visit_id' => $visit->id]);
        $invitation->response()->delete();
        $invitation->fill([
            'token_hash' => $tokenHash,
            'status' => SurveyInvitationStatus::Sent,
            'scheduled_at' => now()->subHours(3),
            'sent_at' => now(),
            'used_at' => null,
            'revoked_at' => null,
        ])->save();

        $this->command?->info(url('/surveys/'.$token));
    }
}
