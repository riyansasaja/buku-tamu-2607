<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkUnit;
use App\Support\WhatsAppNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $sort = in_array($request->string('sort')->toString(), ['name', 'employee_no', 'is_active', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $search = $request->string('q')->toString();

        $employees = Employee::query()
            ->with(['workUnit', 'position'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('employee_no', 'like', '%'.$search.'%')
                        ->orWhereHas('workUnit', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('position', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(in_array($request->string('status')->toString(), ['active', 'inactive'], true), function (Builder $query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.index', compact('employees', 'sort', 'direction'));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('admin.employees.form', [
            'employee' => new Employee,
            'workUnits' => WorkUnit::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'positions' => Position::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        Employee::query()->create($request->validated());

        return redirect()->route('admin.employees.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('admin.employees.form', [
            'employee' => $employee,
            'workUnits' => WorkUnit::query()
                ->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $employee->work_unit_id))
                ->orderBy('sort_order')->orderBy('name')->get(),
            'positions' => Position::query()
                ->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $employee->position_id))
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('admin.employees.index')->with('success', 'Pegawai berhasil diperbarui.');
    }

    public function status(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($validated['is_active'] && ! WhatsAppNumber::isValid($employee->notification_contact)) {
            return back()->withErrors(['is_active' => 'Pegawai harus memiliki nomor WhatsApp valid sebelum diaktifkan.']);
        }
        $employee->update(['is_active' => $validated['is_active']]);

        return back()->with('success', 'Status pegawai berhasil diperbarui.');
    }
}
