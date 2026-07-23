@extends('layouts.app')

@section('title', 'Detail Kunjungan · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 lg:px-12">
        <a href="{{ route('admin.visits.index') }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">← Kembali ke kunjungan</a>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-sky-300">{{ $visit->visit_number }}</p><h1 class="mt-2 text-3xl font-bold text-white">{{ $visit->guest_name }}</h1></div>
            <span class="w-fit rounded-full bg-sky-300/10 px-3 py-1 text-sm font-semibold text-sky-200">{{ strtoupper($visit->status->value) }}</span>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[320px_1fr]">
            <img src="{{ $photoUrl }}" alt="Foto {{ $visit->guest_name }}" class="aspect-[4/3] w-full rounded-2xl border border-white/10 bg-slate-900 object-cover">
            <dl class="grid gap-5 rounded-2xl border border-white/10 bg-white/[0.04] p-6 sm:grid-cols-2">
                <div><dt class="text-xs uppercase tracking-wider text-slate-500">Waktu</dt><dd class="mt-2 text-slate-200">{{ $visit->arrived_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-slate-500">Pegawai tujuan</dt><dd class="mt-2 text-slate-200">{{ $visit->employee->name }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wider text-slate-500">Alamat</dt><dd class="mt-2 text-slate-200">{{ $visit->address }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wider text-slate-500">Maksud</dt><dd class="mt-2 text-slate-200">{{ $visit->visit_purpose }}</dd></div>
                @if ($visit->decision_reason)<div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wider text-slate-500">Alasan keputusan</dt><dd class="mt-2 text-slate-200">{{ $visit->decision_reason }}</dd></div>@endif
            </dl>
        </div>

        <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.04] p-6">
            <h2 class="text-xl font-bold text-white">Pengiriman WhatsApp</h2>
            <div class="mt-5 grid gap-4">
                @forelse ($visit->notificationDeliveries->sortBy('created_at') as $delivery)
                    <article class="grid gap-3 rounded-xl border border-white/10 bg-slate-950/40 p-4 sm:grid-cols-3">
                        <div><p class="text-xs uppercase tracking-wider text-slate-500">Jenis</p><p class="mt-1 font-semibold text-white">{{ str($delivery->type->value)->replace('_', ' ')->title() }}</p></div>
                        <div><p class="text-xs uppercase tracking-wider text-slate-500">Status</p><p class="mt-1 text-slate-200">{{ strtoupper($delivery->status->value) }} · {{ $delivery->attempts }} percobaan</p></div>
                        <div><p class="text-xs uppercase tracking-wider text-slate-500">Terakhir</p><p class="mt-1 text-slate-200">{{ $delivery->sent_at?->timezone('Asia/Makassar')->format('d-m-Y H:i:s') ?? $delivery->last_attempt_at?->timezone('Asia/Makassar')->format('d-m-Y H:i:s') ?? 'Belum diproses' }}</p>@if($delivery->error_code)<p class="mt-1 text-xs text-rose-300">{{ $delivery->error_code }}</p>@endif</div>
                    </article>
                @empty
                    <p class="text-slate-400">Belum ada delivery WhatsApp untuk kunjungan ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
