<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $visits = Visit::query()
            ->with(['employee', 'notificationDeliveries'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('guest_name', 'like', "%{$search}%")
                ->orWhere('visit_number', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->latest('arrived_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.visits.index', compact('visits'));
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
