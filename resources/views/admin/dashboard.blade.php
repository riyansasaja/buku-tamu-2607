@extends('layouts.app')

@section('title', 'Dashboard Admin · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:px-12 lg:py-16">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Area terlindungi</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Dashboard Admin</h1>
                <p class="mt-3 text-slate-400">Selamat datang, {{ auth()->user()->name }}.</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 font-semibold text-slate-200 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300 sm:w-auto">
                    Keluar
                </button>
            </form>
        </div>

        <section class="mt-10 rounded-3xl border border-white/10 bg-white/[0.06] p-6 shadow-xl shadow-black/15 sm:p-8" aria-labelledby="foundation-title">
            <h2 id="foundation-title" class="text-xl font-semibold text-white">Autentikasi admin aktif</h2>
            <p class="mt-3 max-w-2xl leading-7 text-slate-400">Area ini hanya dapat dibuka oleh pengguna dengan peran admin dan status aktif. Modul dashboard operasional akan dibangun pada issue berikutnya.</p>
        </section>
    </div>
@endsection
