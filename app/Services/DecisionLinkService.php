<?php

namespace App\Services;

use App\Data\IssuedDecisionLink;
use App\Enums\VisitStatus;
use App\Models\Visit;
use App\Models\VisitDecisionToken;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DecisionLinkService
{
    public function issue(Visit $visit): IssuedDecisionLink
    {
        if ($visit->status !== VisitStatus::Pending) {
            throw new DomainException('Tautan hanya dapat dibuat untuk kunjungan pending.');
        }

        $plainToken = Str::random(64);
        $decisionToken = DB::transaction(function () use ($visit, $plainToken): VisitDecisionToken {
            VisitDecisionToken::query()->where('visit_id', $visit->id)->delete();

            return VisitDecisionToken::query()->create([
                'visit_id' => $visit->id,
                'token_hash' => hash('sha256', $plainToken),
            ]);
        });

        return new IssuedDecisionLink(
            $decisionToken,
            $plainToken,
            route('decisions.show', ['token' => $plainToken]),
        );
    }

    public function resolve(string $plainToken): ?VisitDecisionToken
    {
        if (strlen($plainToken) !== 64 || preg_match('/^[A-Za-z0-9]+$/', $plainToken) !== 1) {
            return null;
        }

        return VisitDecisionToken::query()
            ->with(['visit.employee.workUnit', 'visit.employee.position'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->whereHas('visit', fn ($query) => $query->where('status', VisitStatus::Pending->value))
            ->first();
    }
}
