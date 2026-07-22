@extends('layouts.app')

@section('title', 'Pegawai · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Master Data</p>
                <h1 class="mt-2 text-3xl font-bold text-white">Pegawai</h1>
                <p class="mt-2 text-slate-400">Kelola pegawai yang dapat menjadi tujuan kunjungan.</p>
            </div>
            <a href="{{ route('admin.employees.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-400 px-5 py-2.5 font-semibold text-slate-950 hover:bg-sky-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">Tambah Pegawai</a>
        </div>

        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 lg:grid-cols-[minmax(220px,1fr)_160px_160px_150px_auto]">
            <label class="sr-only" for="q">Cari pegawai</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Nama, NIP, unit, atau jabatan" class="min-h-11 rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20">
            <select name="status" aria-label="Filter status" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <select name="sort" aria-label="Urut berdasarkan" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                <option value="name" @selected($sort === 'name')>Nama</option>
                <option value="employee_no" @selected($sort === 'employee_no')>NIP</option>
                <option value="is_active" @selected($sort === 'is_active')>Status</option>
                <option value="created_at" @selected($sort === 'created_at')>Tanggal dibuat</option>
            </select>
            <select name="direction" aria-label="Arah urutan" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                <option value="asc" @selected($direction === 'asc')>Naik</option>
                <option value="desc" @selected($direction === 'desc')>Turun</option>
            </select>
            <button class="min-h-11 rounded-xl border border-white/10 bg-white/10 px-5 font-semibold text-white hover:bg-white/15">Terapkan</button>
        </form>

        <div class="mt-6 space-y-4 md:hidden">
            @forelse ($employees as $employee)
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-white">{{ $employee->name }}</h2>
                            <p class="mt-1 text-sm text-slate-400">{{ $employee->employee_no ?: 'NIP belum diisi' }}</p>
                        </div>
                        <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-300/10 text-emerald-200' => $employee->is_active, 'bg-slate-500/15 text-slate-300' => ! $employee->is_active])>{{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-slate-500">Unit Kerja</dt><dd class="mt-1 text-slate-200">{{ $employee->workUnit->name }}</dd></div>
                        <div><dt class="text-slate-500">Jabatan</dt><dd class="mt-1 text-slate-200">{{ $employee->position->name }}</dd></div>
                    </dl>
                    <div class="mt-5 flex gap-2">
                        <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg border border-white/10 text-sm font-semibold text-slate-200 hover:bg-white/10">Edit</a>
                        <form method="POST" action="{{ route('admin.employees.status', $employee) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $employee->is_active ? 0 : 1 }}">
                            <button class="min-h-10 w-full rounded-lg border border-white/10 text-sm font-semibold text-slate-200 hover:bg-white/10">{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-white/10 p-10 text-center text-slate-400">Belum ada pegawai yang sesuai.</div>
            @endforelse
        </div>

        <div class="mt-6 hidden overflow-x-auto rounded-2xl border border-white/10 bg-white/[0.04] md:block">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400">
                    <tr><th class="px-5 py-4">Pegawai</th><th class="px-5 py-4">Unit Kerja</th><th class="px-5 py-4">Jabatan</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-white">{{ $employee->name }}</p><p class="mt-1 text-slate-500">{{ $employee->employee_no ?: 'NIP belum diisi' }}</p></td>
                            <td class="px-5 py-4 text-slate-300">{{ $employee->workUnit->name }}</td>
                            <td class="px-5 py-4 text-slate-300">{{ $employee->position->name }}</td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-300/10 text-emerald-200' => $employee->is_active, 'bg-slate-500/15 text-slate-300' => ! $employee->is_active])>{{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.employees.edit', $employee) }}" class="rounded-lg border border-white/10 px-3 py-2 font-semibold text-slate-200 hover:bg-white/10">Edit</a><form method="POST" action="{{ route('admin.employees.status', $employee) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $employee->is_active ? 0 : 1 }}"><button class="rounded-lg border border-white/10 px-3 py-2 font-semibold text-slate-200 hover:bg-white/10">{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Belum ada pegawai yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $employees->links() }}</div>
    </div>
@endsection
