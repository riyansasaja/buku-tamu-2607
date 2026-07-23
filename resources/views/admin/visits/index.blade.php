@extends('layouts.app')

@section('title', 'Kunjungan · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Monitoring</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Kunjungan</h1>
        <p class="mt-2 text-slate-400">Pantau kunjungan dan status pengiriman WhatsApp.</p>

        <form method="GET" class="mt-7 grid gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="sm:col-span-2 lg:col-span-2"><label for="q" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pencarian</label><input id="q" name="q" value="{{ $filters->search }}" placeholder="Nama atau nomor kunjungan" class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400"></div>
            <div><label for="date_from" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal awal</label><input id="date_from" name="date_from" type="date" value="{{ $filters->fromDate() }}" class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"></div>
            <div><label for="date_to" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal akhir</label><input id="date_to" name="date_to" type="date" value="{{ $filters->toDate() }}" class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"></div>
            <div><label for="status" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status</label><select id="status" name="status" class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"><option value="">Semua</option>@foreach (\App\Enums\VisitStatus::cases() as $status)<option value="{{ $status->value }}" @selected($filters->status === $status)>{{ ucfirst($status->value) }}</option>@endforeach</select></div>
            <div><label for="employee_id" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pegawai</label><select id="employee_id" name="employee_id" class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"><option value="">Semua</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected($filters->employeeId === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
            <div class="flex flex-wrap gap-3 sm:col-span-2 lg:col-span-6 lg:justify-end">
                <a href="{{ route('admin.reports.visits.pdf', array_filter(['date_from' => $filters->fromDate(), 'date_to' => $filters->toDate(), 'status' => $filters->status?->value, 'employee_id' => $filters->employeeId])) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 font-semibold text-emerald-100 hover:bg-emerald-300/20">Unduh PDF</a>
                <a href="{{ route('admin.visits.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/10 px-5 font-semibold text-slate-300 hover:bg-white/10">Reset</a>
                <button class="min-h-11 rounded-xl bg-sky-400 px-5 font-semibold text-slate-950 hover:bg-sky-300">Terapkan Filter</button>
            </div>
        </form>

        <p class="mt-3 text-xs text-slate-500">Laporan PDF mengikuti periode, status, dan pegawai yang dipilih. Foto serta nomor WhatsApp tamu tidak disertakan.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-300/20 bg-rose-300/10 p-4 text-sm text-rose-100" role="alert">Filter tidak valid. Periksa tanggal, status, atau pegawai yang dipilih.</div>
        @endif

        <div class="mt-6 grid gap-4">
            @forelse ($visits as $visit)
                <a href="{{ route('admin.visits.show', $visit) }}" class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:border-sky-300/30 hover:bg-white/[0.07] sm:grid-cols-[1fr_auto] sm:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-white">{{ $visit->guest_name }}</h2><span class="rounded-full bg-slate-700/60 px-2.5 py-1 text-xs text-slate-300">{{ $visit->visit_number }}</span></div>
                        <p class="mt-2 text-sm text-slate-400">Tujuan: {{ $visit->employee->name }} · {{ $visit->arrived_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-sky-300/10 px-3 py-1 text-xs font-semibold text-sky-200">{{ strtoupper($visit->status->value) }}</span>
                        @foreach ($visit->notificationDeliveries as $delivery)
                            <span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-300/10 text-emerald-200' => $delivery->status->value === 'sent', 'bg-amber-300/10 text-amber-200' => in_array($delivery->status->value, ['pending', 'processing']), 'bg-rose-300/10 text-rose-200' => $delivery->status->value === 'failed'])>WA {{ $delivery->status->value }}</span>
                        @endforeach
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-white/10 p-10 text-center text-slate-400">Belum ada kunjungan yang sesuai.</div>
            @endforelse
        </div>

        <div class="mt-6">{{ $visits->links() }}</div>
    </div>
@endsection
