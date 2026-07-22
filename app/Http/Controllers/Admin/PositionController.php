<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Position::class);

        $items = Position::query()
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
            'title' => 'Jabatan',
            'singular' => 'jabatan',
            'routePrefix' => 'admin.positions',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Position::class);

        return view('admin.references.form', [
            'item' => new Position,
            'title' => 'Tambah Jabatan',
            'singular' => 'jabatan',
            'routePrefix' => 'admin.positions',
        ]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        Position::query()->create($request->validated());

        return redirect()->route('admin.positions.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Position $position): View
    {
        $this->authorize('update', $position);

        return view('admin.references.form', [
            'item' => $position,
            'title' => 'Edit Jabatan',
            'singular' => 'jabatan',
            'routePrefix' => 'admin.positions',
        ]);
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()->route('admin.positions.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function status(Request $request, Position $position): RedirectResponse
    {
        $this->authorize('update', $position);
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $position->update(['is_active' => $validated['is_active']]);

        return back()->with('success', 'Status jabatan berhasil diperbarui.');
    }
}
