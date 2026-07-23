<?php

namespace App\Services;

use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Jobs\SendGuestSurveyWhatsApp;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class SurveyInvitationService
{
    public function schedule(int $visitId): ?SurveyInvitation
    {
        $visit = Visit::query()->find($visitId);
        if ($visit === null || $visit->status !== VisitStatus::Accepted || $visit->decided_at === null) {
            return null;
        }

        $scheduledAt = $visit->decided_at->addHours(max(1, (int) config('survey.delay_hours', 3)));
        $invitation = SurveyInvitation::query()->firstOrCreate(
            ['visit_id' => $visit->id],
            ['status' => SurveyInvitationStatus::Scheduled, 'scheduled_at' => $scheduledAt],
        );

        if ($invitation->wasRecentlyCreated) {
            SendGuestSurveyWhatsApp::dispatch($invitation->id)->delay($scheduledAt);
        }

        return $invitation;
    }

    public function resolve(string $plainToken): ?SurveyInvitation
    {
        return SurveyInvitation::query()
            ->with('visit')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('status', SurveyInvitationStatus::Sent)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->whereHas('visit', fn ($query) => $query->where('status', VisitStatus::Accepted))
            ->first();
    }

    public function submit(string $plainToken, int $rating, ?string $comment): bool
    {
        return DB::transaction(function () use ($plainToken, $rating, $comment): bool {
            $invitation = SurveyInvitation::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->first();
            if ($invitation === null || $invitation->status !== SurveyInvitationStatus::Sent || $invitation->used_at !== null || $invitation->revoked_at !== null) {
                return false;
            }

            $visit = Visit::query()->whereKey($invitation->visit_id)->lockForUpdate()->first();
            if ($visit === null || $visit->status !== VisitStatus::Accepted) {
                return false;
            }

            SurveyResponse::query()->create([
                'survey_invitation_id' => $invitation->id,
                'visit_id' => $visit->id,
                'rating' => $rating,
                'comment' => filled($comment) ? trim((string) $comment) : null,
                'submitted_at' => now(),
            ]);
            $invitation->update(['status' => SurveyInvitationStatus::Used, 'used_at' => now()]);

            return true;
        }, 3);
    }
}
