@extends('layouts.app')

@section('title', $title.' · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 lg:px-12">
        <a href="{{ route($routePrefix.'.index') }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">← Kembali ke {{ $singular }}</a>
        <h1 class="mt-4 text-3xl font-bold text-white">{{ $title }}</h1>

        <form method="POST" action="{{ $item->exists ? route($routePrefix.'.update', $item) : route($routePrefix.'.store') }}" class="mt-8 space-y-6 rounded-3xl border border-white/10 bg-white/[0.05] p-6 sm:p-8">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-200">Nama {{ ucfirst($singular) }}</label>
                <input id="name" name="name" value="{{ old('name', $item->name) }}" required autofocus class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20">
                @error('name') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-semibold text-slate-200">Urutan Tampil</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $item->sort_order ?? 0) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 text-white outline-none focus:border-sky-400">
                @error('sort_order') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-3 rounded-xl border border-white/10 bg-slate-950/40 p-4">
                <input type="hidden" name="is_active" value="0">
                <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $item->exists ? $item->is_active : true)) class="mt-1 h-4 w-4 rounded border-slate-600 bg-slate-900 text-sky-400 focus:ring-sky-400">
                <span><span class="block font-semibold text-white">Aktif</span><span class="mt-1 block text-sm text-slate-400">Referensi aktif dapat dipilih untuk pegawai.</span></span>
            </label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route($routePrefix.'.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/10 px-5 font-semibold text-slate-200 hover:bg-white/10">Batal</a>
                <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-400 px-5 font-semibold text-slate-950 hover:bg-sky-300">Simpan</button>
            </div>
        </form>
    </div>
@endsection
