@extends('layouts.app')

@section('title', 'Masuk Admin · '.config('app.name'))

@section('content')
    <div class="mx-auto grid min-h-[calc(100vh-85px)] max-w-7xl place-items-center px-5 py-12 sm:px-8 lg:px-12">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-white/[0.06] shadow-2xl shadow-black/25 backdrop-blur lg:grid-cols-[1fr_1.05fr]">
            <section class="hidden bg-sky-400 p-10 text-slate-950 lg:flex lg:flex-col lg:justify-between" aria-label="Informasi aplikasi">
                <p class="text-sm font-bold uppercase tracking-[0.2em]">Portal Administrasi</p>
                <div>
                    <h1 class="text-4xl font-bold tracking-tight">Kelola kunjungan dengan akses yang aman.</h1>
                    <p class="mt-5 max-w-md leading-7 text-slate-800">Masuk untuk memantau dan mengelola layanan Buku Tamu PTA Manado.</p>
                </div>
                <p class="text-sm font-medium text-slate-800">Akses khusus administrator aktif.</p>
            </section>

            <section class="p-6 sm:p-10 lg:p-12" aria-labelledby="login-title">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Selamat datang</p>
                <h1 id="login-title" class="mt-3 text-3xl font-bold tracking-tight text-white">Masuk sebagai admin</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">Gunakan email dan password administrator Anda.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-200">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 block min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-600 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20" placeholder="admin@contoh.go.id">
                        @error('email')
                            <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-200">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 block min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-600 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20" placeholder="Masukkan password">
                        @error('password')
                            <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-sky-400 px-5 py-3 font-semibold text-slate-950 transition hover:bg-sky-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                        Masuk
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
