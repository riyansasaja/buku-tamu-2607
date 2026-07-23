<?php

namespace App\Http\Controllers\Admin;

use App\Data\VisitFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VisitFilterRequest;
use App\Models\Visit;
use App\Services\VisitDashboardService;
use App\Services\VisitFilterService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        VisitFilterRequest $request,
        VisitDashboardService $dashboard,
        VisitFilterService $filterService,
    ): View {
        $validatedPeriod = $request->safe()->only(['date_from', 'date_to']);
        $usesCurrentYearDefault = ! $request->filled('date_from') && ! $request->filled('date_to');
        $filters = VisitFilters::fromValidatedForCurrentYear($validatedPeriod);
        $summary = $dashboard->summary($filters);
        $recentVisits = $filterService->apply(Visit::query()->with('employee'), $filters, false)
            ->latest('arrived_at')->limit(5)->get();

        return view('admin.dashboard', compact('filters', 'summary', 'recentVisits', 'usesCurrentYearDefault'));
    }
}
