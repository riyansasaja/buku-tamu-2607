<?php

namespace App\Services;

use App\Data\VisitFilters;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

class VisitFilterService
{
    /** @param Builder<Visit> $query
     * @return Builder<Visit>
     */
    public function apply(Builder $query, VisitFilters $filters, bool $includeStatus = true): Builder
    {
        return $query
            ->when($filters->from !== null, fn (Builder $query) => $query->where('arrived_at', '>=', $filters->from))
            ->when($filters->to !== null, fn (Builder $query) => $query->where('arrived_at', '<=', $filters->to))
            ->when($includeStatus && $filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status))
            ->when($filters->employeeId !== null, fn (Builder $query) => $query->where('employee_id', $filters->employeeId))
            ->when($filters->search !== null, function (Builder $query) use ($filters): void {
                $search = $filters->search;
                $query->where(fn (Builder $query) => $query
                    ->where('guest_name', 'like', "%{$search}%")
                    ->orWhere('visit_number', 'like', "%{$search}%"));
            });
    }
}
