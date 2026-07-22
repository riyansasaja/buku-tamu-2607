@extends('layouts.app')

@section('title', $title.' · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Data Referensi</p>
                <h1 class="mt-2 text-3xl font-bold text-white">{{ $title }}</h1>
                <p class="mt-2 text-slate-400">Kelola daftar {{ $singular }} yang digunakan pegawai.</p>
            </div>
            <a href="{{ route($routePrefix.'.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-400 px-5 py-2.5 font-semibold text-slate-950 hover:bg-sky-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">Tambah {{ $title }}</a>
        </div>

        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-[1fr_180px_auto]">
            <label class="sr-only" for="q">Cari {{ $singular }}</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama {{ $singular }}" class="min-h-11 rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20">
            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-4 text-white outline-none focus:border-sky-400">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="min-h-11 rounded-xl border border-white/10 bg-white/10 px-5 font-semibold text-white hover:bg-white/15">Terapkan</button>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]">
            @forelse ($items as $item)
                <article class="flex flex-col gap-4 border-b border-white/10 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-white">{{ $item->name }}</h2>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-emerald-300/10 text-emerald-200' => $item->is_active,
                                'bg-slate-500/15 text-slate-300' => ! $item->is_active,
                            ])>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">Urutan {{ $item->sort_order }} · {{ $item->employees_count }} pegawai</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route($routePrefix.'.edit', $item) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg border border-white/10 px-4 text-sm font-semibold text-slate-200 hover:bg-white/10 sm:flex-none">Edit</a>
                        <form method="POST" action="{{ route($routePrefix.'.status', $item) }}" class="flex-1 sm:flex-none">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                            <button class="min-h-10 w-full rounded-lg border border-white/10 px-4 text-sm font-semibold text-slate-200 hover:bg-white/10">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="p-10 text-center text-slate-400">Belum ada data {{ $singular }} yang sesuai.</div>
            @endforelse
        </div>

        <div class="mt-6">{{ $items->links() }}</div>
    </div>
@endsection
