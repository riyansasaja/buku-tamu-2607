<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkUnitRequest;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkUnitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkUnit::class);

        $items = WorkUnit::query()
            ->withCount('employees')
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->when(in_array($request->string('status')->toString(), ['active', 'inactive'], true), function ($query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.references.index', [
            'items' => $items,
            'title' => 'Unit Kerja',
            'singular' => 'unit kerja',
            'routePrefix' => 'admin.work-units',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WorkUnit::class);

        return view('admin.references.form', [
            'item' => new WorkUnit,
            'title' => 'Tambah Unit Kerja',
            'singular' => 'unit kerja',
            'routePrefix' => 'admin.work-units',
        ]);
    }

    public function store(WorkUnitRequest $request): RedirectResponse
    {
        WorkUnit::query()->create($request->validated());

        return redirect()->route('admin.work-units.index')->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function edit(WorkUnit $workUnit): View
    {
        $this->authorize('update', $workUnit);

        return view('admin.references.form', [
            'item' => $workUnit,
            'title' => 'Edit Unit Kerja',
            'singular' => 'unit kerja',
            'routePrefix' => 'admin.work-units',
        ]);
    }

    public function update(WorkUnitRequest $request, WorkUnit $workUnit): RedirectResponse
    {
        $workUnit->update($request->validated());

        return redirect()->route('admin.work-units.index')->with('success', 'Unit kerja berhasil diperbarui.');
    }

    public function status(Request $request, WorkUnit $workUnit): RedirectResponse
    {
        $this->authorize('update', $workUnit);
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $workUnit->update(['is_active' => $validated['is_active']]);

        return back()->with('success', 'Status unit kerja berhasil diperbarui.');
    }
}
