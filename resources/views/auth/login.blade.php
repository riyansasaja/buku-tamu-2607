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
                        <div class="flex items-center justify-between gap-3"><label for="password" class="block text-sm font-semibold text-slate-200">Password</label><a href="{{ route('password.request') }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">Lupa password?</a></div>
                        <div class="relative mt-2">
                            <input id="password" name="password" type="password" required autocomplete="current-password" class="block min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 py-3 pl-4 pr-12 text-white outline-none transition placeholder:text-slate-600 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20" placeholder="Masukkan password">
                            <button type="button" data-password-toggle data-target="password" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-xl text-slate-400 transition hover:text-sky-300 focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-sky-300">
                                <svg data-password-visible="false" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-password-visible="true" viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a15 15 0 0 1-2.1 2.8M6.6 6.6C4 8.3 2.5 12 2.5 12s3.5 6 9.5 6a9.5 9.5 0 0 0 3.4-.6M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </div>
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
