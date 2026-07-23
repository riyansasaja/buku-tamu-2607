@extends('layouts.app')

@section('title', ($employee->exists ? 'Edit' : 'Tambah').' Pegawai · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 lg:px-12">
        <a href="{{ route('admin.employees.index') }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">← Kembali ke pegawai</a>
        <h1 class="mt-4 text-3xl font-bold text-white">{{ $employee->exists ? 'Edit Pegawai' : 'Tambah Pegawai' }}</h1>

        @if ($workUnits->isEmpty() || $positions->isEmpty())
            <div class="mt-6 rounded-xl border border-amber-300/20 bg-amber-300/10 p-4 text-sm text-amber-100" role="alert">Tambahkan unit kerja dan jabatan aktif sebelum menyimpan pegawai.</div>
        @endif

        <form method="POST" action="{{ $employee->exists ? route('admin.employees.update', $employee) : route('admin.employees.store') }}" class="mt-8 space-y-6 rounded-3xl border border-white/10 bg-white/[0.05] p-6 sm:p-8">
            @csrf
            @if ($employee->exists) @method('PUT') @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-slate-200">Nama Pegawai</label>
                    <input id="name" name="name" value="{{ old('name', $employee->name) }}" required autofocus autocomplete="name" class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20">
                    @error('name') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="employee_no" class="block text-sm font-semibold text-slate-200">NIP/Identifier <span class="font-normal text-slate-500">(opsional)</span></label>
                    <input id="employee_no" name="employee_no" value="{{ old('employee_no', $employee->employee_no) }}" class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400">
                    @error('employee_no') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="work_unit_id" class="block text-sm font-semibold text-slate-200">Unit Kerja</label>
                    <select id="work_unit_id" name="work_unit_id" required class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                        <option value="">Pilih unit kerja</option>
                        @foreach ($workUnits as $workUnit)
                            <option value="{{ $workUnit->id }}" @selected((string) old('work_unit_id', $employee->work_unit_id) === (string) $workUnit->id)>{{ $workUnit->name }}{{ $workUnit->is_active ? '' : ' (nonaktif)' }}</option>
                        @endforeach
                    </select>
                    @error('work_unit_id') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="position_id" class="block text-sm font-semibold text-slate-200">Jabatan</label>
                    <select id="position_id" name="position_id" required class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                        <option value="">Pilih jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) old('position_id', $employee->position_id) === (string) $position->id)>{{ $position->name }}{{ $position->is_active ? '' : ' (nonaktif)' }}</option>
                        @endforeach
                    </select>
                    @error('position_id') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="notification_contact" class="block text-sm font-semibold text-slate-200">Nomor WhatsApp</label>
                    <input id="notification_contact" name="notification_contact" value="{{ old('notification_contact', $employee->notification_contact) }}" inputmode="tel" autocomplete="tel" class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400" placeholder="Contoh: 081234567890">
                    <p class="mt-2 text-xs text-slate-500">Wajib untuk pegawai aktif. Nomor dinormalisasi ke format internasional dan disimpan terenkripsi.</p>
                    @error('notification_contact') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-xl border border-white/10 bg-slate-950/40 p-4">
                <input type="hidden" name="is_active" value="0">
                <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $employee->exists ? $employee->is_active : true)) class="mt-1 h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-400 focus:ring-sky-400">
                <span><span class="block font-semibold text-white">Pegawai aktif</span><span class="mt-1 block text-sm text-slate-400">Hanya pegawai aktif dengan nomor WhatsApp valid yang tersedia sebagai tujuan kunjungan baru.</span></span>
            </label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.employees.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/10 px-5 font-semibold text-slate-200 hover:bg-white/10">Batal</a>
                <button @disabled($workUnits->isEmpty() || $positions->isEmpty()) class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-400 px-5 font-semibold text-slate-950 hover:bg-sky-300 disabled:cursor-not-allowed disabled:opacity-50">Simpan</button>
            </div>
        </form>
    </div>
@endsection
