<?php

namespace App\Services;

use App\Data\VisitFilters;
use App\Enums\VisitStatus;
use App\Models\Visit;

class VisitDashboardService
{
    public function __construct(private readonly VisitFilterService $filterService) {}

    /** @return array{total: int, accepted: int, rejected: int, pending: int} */
    public function summary(VisitFilters $filters): array
    {
        $result = $this->filterService->apply(Visit::query(), $filters)
            ->selectRaw(
                'COUNT(*) AS total, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS accepted, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS rejected, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending',
                [VisitStatus::Accepted->value, VisitStatus::Rejected->value, VisitStatus::Pending->value],
            )->first();

        return [
            'total' => (int) ($result?->getAttribute('total') ?? 0),
            'accepted' => (int) ($result?->getAttribute('accepted') ?? 0),
            'rejected' => (int) ($result?->getAttribute('rejected') ?? 0),
            'pending' => (int) ($result?->getAttribute('pending') ?? 0),
        ];
    }
}
