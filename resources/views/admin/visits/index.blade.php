@extends('layouts.app')

@section('title', 'Kunjungan · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Monitoring</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Kunjungan</h1>
        <p class="mt-2 text-slate-400">Pantau kunjungan dan status pengiriman WhatsApp.</p>

        <form method="GET" class="mt-7 flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 sm:flex-row">
            <label class="sr-only" for="q">Cari kunjungan</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Nama tamu, nomor kunjungan, atau pegawai" class="min-h-11 flex-1 rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400">
            <button class="min-h-11 rounded-xl bg-sky-400 px-5 font-semibold text-slate-950 hover:bg-sky-300">Cari</button>
        </form>

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
