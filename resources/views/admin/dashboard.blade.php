@extends('layouts.app')

@section('title', 'Dashboard Admin · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:px-12 lg:py-16">
        <div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Area terlindungi</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Dashboard Admin</h1>
                <p class="mt-3 text-slate-400">Selamat datang, {{ auth()->user()->name }}.</p>
            </div>
        </div>

        <section class="mt-10 rounded-3xl border border-white/10 bg-white/[0.06] p-6 shadow-xl shadow-black/15 sm:p-8" aria-labelledby="foundation-title">
            <h2 id="foundation-title" class="text-xl font-semibold text-white">Autentikasi admin aktif</h2>
            <p class="mt-3 max-w-2xl leading-7 text-slate-400">Area ini hanya dapat dibuka oleh pengguna dengan peran admin dan status aktif. Modul dashboard operasional akan dibangun pada issue berikutnya.</p>
        </section>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <a href="{{ route('admin.employees.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:border-sky-300/30 hover:bg-white/[0.08] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                <p class="font-semibold text-white">Kelola Pegawai</p>
                <p class="mt-2 text-sm text-slate-400">Tambah, perbarui, dan atur status pegawai.</p>
            </a>
            <a href="{{ route('admin.work-units.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:border-sky-300/30 hover:bg-white/[0.08] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                <p class="font-semibold text-white">Unit Kerja</p>
                <p class="mt-2 text-sm text-slate-400">Kelola referensi unit kerja kantor.</p>
            </a>
            <a href="{{ route('admin.positions.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:border-sky-300/30 hover:bg-white/[0.08] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                <p class="font-semibold text-white">Jabatan</p>
                <p class="mt-2 text-sm text-slate-400">Kelola referensi jabatan pegawai.</p>
            </a>
        </div>
    </div>
@endsection
