<?php

namespace App\Http\Controllers\Admin;

use App\Data\SurveyFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SurveyReportRequest;
use App\Models\Employee;
use App\Models\WorkUnit;
use App\Services\SurveyReportService;
use Illuminate\View\View;

class SurveyReportController extends Controller
{
    public function __invoke(SurveyReportRequest $request, SurveyReportService $reports): View
    {
        $filters = SurveyFilters::fromValidated($request->validated());
        $summary = $reports->summary($filters);
        $invitations = $reports->query($filters)->with(['visit.employee.workUnit', 'response'])
            ->latest('sent_at')->paginate($filters->perPage)->withQueryString();
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);
        $workUnits = WorkUnit::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('admin.surveys.index', compact('filters', 'summary', 'invitations', 'employees', 'workUnits'));
    }
}
