<?php

namespace Database\Seeders;

use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Models\Employee;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class DevelopmentSurveyReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Seeder laporan survei hanya boleh dijalankan pada environment local.');

            return;
        }

        $employee = Employee::query()->with('workUnit')->first() ?? Employee::factory()->create();
        foreach (range(1, 14) as $index) {
            $number = sprintf('DEMO-SURVEY-REPORT-%02d', $index);
            $visit = Visit::query()->firstOrCreate(['visit_number' => $number], array_merge(Visit::factory()->raw(), [
                'visit_number' => $number, 'employee_id' => $employee->id, 'guest_name' => 'Tamu Evaluasi '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'address' => 'Alamat contoh untuk pemeriksaan laporan survei nomor '.$index.', Kota Manado.',
                'status' => VisitStatus::Accepted, 'decided_at' => now()->subDays($index), 'arrived_at' => now()->subDays($index),
            ]));
            $invitation = SurveyInvitation::query()->updateOrCreate(['visit_id' => $visit->id], [
                'token_hash' => hash('sha256', $number), 'status' => $index % 4 === 0 ? SurveyInvitationStatus::Sent : SurveyInvitationStatus::Used,
                'scheduled_at' => $visit->arrived_at, 'sent_at' => $visit->arrived_at, 'used_at' => $index % 4 === 0 ? null : $visit->arrived_at,
            ]);
            if ($index % 4 === 0) {
                SurveyResponse::query()->where('survey_invitation_id', $invitation->id)->delete();

                continue;
            }
            SurveyResponse::query()->updateOrCreate(['survey_invitation_id' => $invitation->id], [
                'visit_id' => $visit->id, 'rating' => (($index - 1) % 5) + 1,
                'comment' => $index === 13 ? str_repeat('Pelayanan baik dan petugas memberikan penjelasan yang jelas. ', 12) : 'Komentar evaluasi pelayanan nomor '.$index.'.',
                'submitted_at' => $visit->arrived_at,
            ]);
        }
        $this->command?->info('Empat belas data demo laporan survei berhasil disiapkan.');
    }
}
