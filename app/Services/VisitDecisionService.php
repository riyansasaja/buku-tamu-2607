<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Events\VisitDecisionRecorded;
use App\Models\AuditLog;
use App\Models\Visit;
use App\Models\VisitDecisionToken;
use Illuminate\Support\Facades\DB;

class VisitDecisionService
{
    public function decide(string $plainToken, VisitStatus $decision, ?string $reason, string $requestId): ?Visit
    {
        if (! in_array($decision, [VisitStatus::Accepted, VisitStatus::Rejected], true)) {
            return null;
        }

        return DB::transaction(function () use ($plainToken, $decision, $reason, $requestId): ?Visit {
            $decisionToken = VisitDecisionToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->first();

            if ($decisionToken === null || $decisionToken->used_at !== null || $decisionToken->revoked_at !== null) {
                return null;
            }

            $visit = Visit::query()->whereKey($decisionToken->visit_id)->lockForUpdate()->first();
            if ($visit === null || $visit->status !== VisitStatus::Pending) {
                return null;
            }

            $visit->update([
                'status' => $decision,
                'decision_reason' => $decision === VisitStatus::Rejected ? $reason : null,
                'decided_at' => now(),
            ]);
            $decisionToken->update(['used_at' => now()]);

            AuditLog::query()->create([
                'actor_type' => 'decision_link',
                'action' => 'visit.decided',
                'auditable_type' => Visit::class,
                'auditable_id' => $visit->id,
                'metadata' => ['status' => $decision->value],
                'request_id' => $requestId,
            ]);

            VisitDecisionRecorded::dispatch($visit->id, $decision);

            return $visit->fresh();
        }, 3);
    }
}
