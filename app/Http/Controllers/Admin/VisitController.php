<?php

namespace App\Http\Controllers\Admin;

use App\Data\VisitFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VisitFilterRequest;
use App\Models\Employee;
use App\Models\Visit;
use App\Services\VisitFilterService;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(VisitFilterRequest $request, VisitFilterService $filterService): View
    {
        $filters = VisitFilters::fromValidated($request->validated());
        $visits = $filterService->apply(
            Visit::query()->with(['employee', 'notificationDeliveries']),
            $filters,
        )
            ->latest('arrived_at')
            ->paginate($filters->perPage)
            ->withQueryString();
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.visits.index', compact('visits', 'employees', 'filters'));
    }

    public function show(Visit $visit): View
    {
        $visit->load(['employee.workUnit', 'employee.position', 'notificationDeliveries']);
        $photoUrl = URL::temporarySignedRoute(
            'api.v1.visits.photo',
            now()->addMinutes((int) config('api.photo_url_minutes')),
            ['visit' => $visit->id],
        );

        return view('admin.visits.show', compact('visit', 'photoUrl'));
    }
}
