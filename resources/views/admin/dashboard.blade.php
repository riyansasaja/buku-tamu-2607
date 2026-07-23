@extends('layouts.app')

@section('title', 'Dashboard Admin · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Ringkasan pelayanan</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Dashboard Kunjungan</h1>
                <p class="mt-3 text-slate-300">Selamat datang, <span class="font-semibold text-white">{{ auth()->user()->name }}</span>.</p>
                <p class="mt-2 inline-flex rounded-full border border-sky-300/20 bg-sky-300/10 px-3 py-1 text-sm font-semibold text-sky-200">
                    {{ $usesCurrentYearDefault ? 'Akumulasi tahun berjalan '.$filters->from?->year : 'Periode khusus' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">Seluruh waktu dan batas tanggal menggunakan zona waktu Asia/Makassar.</p>
            </div>
            <form method="GET" class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-[1fr_1fr_auto]">
                <div><label for="date_from" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal awal</label><input id="date_from" name="date_from" type="date" value="{{ $filters->fromDate() }}" required class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"></div>
                <div><label for="date_to" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal akhir</label><input id="date_to" name="date_to" type="date" value="{{ $filters->toDate() }}" required class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3 text-white outline-none focus:border-sky-400"></div>
                <button class="min-h-11 self-end rounded-xl bg-sky-400 px-5 font-semibold text-slate-950 hover:bg-sky-300">Terapkan</button>
            </form>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-300/20 bg-rose-300/10 p-4 text-sm text-rose-100" role="alert">Filter tidak valid. Periksa kembali rentang tanggal.</div>
        @endif

        @php
            $period = ['date_from' => $filters->fromDate(), 'date_to' => $filters->toDate()];
            $cards = [
                ['label' => 'Total Kunjungan', 'value' => $summary['total'], 'status' => null, 'class' => 'border-sky-300/20 bg-sky-300/[0.08] text-sky-200'],
                ['label' => 'Diterima', 'value' => $summary['accepted'], 'status' => 'accepted', 'class' => 'border-emerald-300/20 bg-emerald-300/[0.08] text-emerald-200'],
                ['label' => 'Ditolak', 'value' => $summary['rejected'], 'status' => 'rejected', 'class' => 'border-rose-300/20 bg-rose-300/[0.08] text-rose-200'],
                ['label' => 'Belum Diputuskan', 'value' => $summary['pending'], 'status' => 'pending', 'class' => 'border-amber-300/20 bg-amber-300/[0.08] text-amber-200'],
            ];
        @endphp

        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistik kunjungan">
            @foreach ($cards as $card)
                <a href="{{ route('admin.visits.index', array_filter([...$period, 'status' => $card['status']])) }}" class="rounded-2xl border p-5 transition hover:-translate-y-0.5 hover:bg-white/[0.1] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300 {{ $card['class'] }}">
                    <p class="text-sm font-semibold">{{ $card['label'] }}</p>
                    <p data-testid="dashboard-{{ $card['status'] ?? 'total' }}" class="mt-3 text-4xl font-bold text-white">{{ number_format($card['value']) }}</p>
                    <p class="mt-3 text-xs text-slate-400">Lihat daftar dalam periode ini →</p>
                </a>
            @endforeach
        </section>

        <section class="mt-8 rounded-3xl border border-white/10 bg-white/[0.04] p-5 sm:p-7">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><h2 class="text-xl font-bold text-white">Kunjungan terbaru</h2><p class="mt-1 text-sm text-slate-400">Periode {{ $filters->from?->translatedFormat('d M Y') }}–{{ $filters->to?->translatedFormat('d M Y') }}.</p></div>
                <a href="{{ route('admin.visits.index', $period) }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">Lihat semua</a>
            </div>
            <div class="mt-5 grid gap-3">
                @forelse ($recentVisits as $visit)
                    <a href="{{ route('admin.visits.show', $visit) }}" class="grid gap-3 rounded-xl border border-white/10 bg-slate-950/30 p-4 transition hover:border-sky-300/30 sm:grid-cols-[1fr_auto] sm:items-center">
                        <div><p class="font-semibold text-white">{{ $visit->guest_name }}</p><p class="mt-1 text-sm text-slate-400">{{ $visit->visit_number }} · Tujuan {{ $visit->employee->name }}</p></div>
                        <div class="text-sm text-slate-300 sm:text-right"><p>{{ $visit->arrived_at->timezone('Asia/Makassar')->format('H:i') }} WITA</p><p class="mt-1 text-xs uppercase text-slate-500">{{ $visit->status->value }}</p></div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-white/10 p-8 text-center text-slate-400">Belum ada kunjungan pada periode ini.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
