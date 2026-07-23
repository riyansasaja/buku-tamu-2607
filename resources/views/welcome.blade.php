<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Fondasi aplikasi Buku Tamu PTA Manado.">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\BuiltAsset::url('resources/css/app.css') }}">
    <script type="module" src="{{ \App\Support\BuiltAsset::url('resources/js/app.js') }}" defer></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-96 bg-gradient-to-b from-sky-500/20 to-transparent"></div>
        <div aria-hidden="true" class="absolute -left-32 top-48 -z-10 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-3 px-5 py-5 sm:px-8 lg:px-12">
                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-sky-400 text-slate-950 shadow-lg shadow-sky-500/20" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 3h8a2 2 0 0 1 2 2v16l-5-3-5 3V5a2 2 0 0 1 2-2Z" />
                        <path d="M10 8h4M10 12h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-300">PTA Manado</p>
                    <p class="font-semibold text-white">Buku Tamu Digital</p>
                </div>
            </div>
        </header>

        <main class="mx-auto flex max-w-7xl flex-1 items-center px-5 py-16 sm:px-8 sm:py-24 lg:px-12 lg:py-32">
            <div class="grid w-full items-center gap-14 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,.9fr)]">
                <section aria-labelledby="hero-title" class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-sm font-medium text-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        Fondasi sistem siap dikembangkan
                    </span>
                    <h1 id="hero-title" class="mt-7 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Pelayanan tamu yang tercatat, jelas, dan terhubung.
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                        Buku Tamu PTA Manado akan mendokumentasikan kunjungan dan menghubungkan tamu dengan pegawai tujuan melalui layanan yang aman dan terukur.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('health') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-sky-400 px-5 py-3 font-semibold text-slate-950 transition hover:bg-sky-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                            Periksa status layanan
                        </a>
                        <span class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-center text-sm font-medium text-slate-300">
                            Laravel 13 · MySQL · Tailwind CSS
                        </span>
                    </div>
                </section>

                <aside aria-label="Kapabilitas fondasi" class="rounded-3xl border border-white/10 bg-white/[0.06] p-5 shadow-2xl shadow-black/20 backdrop-blur sm:p-7">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">Fondasi aplikasi</p>
                    <ul class="mt-6 space-y-4">
                        @foreach ([
                            ['API & Web Admin', 'Satu fondasi Laravel untuk client mobile dan dashboard admin.'],
                            ['Data Privat', 'Penyimpanan internal disiapkan untuk melindungi foto tamu.'],
                            ['Siap Dipantau', 'Health check memisahkan status aplikasi dan database.'],
                        ] as [$title, $description])
                            <li class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <span class="mt-1 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-sky-400/15 text-sky-300" aria-hidden="true">✓</span>
                                <div>
                                    <p class="font-semibold text-white">{{ $title }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-400">{{ $description }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </aside>
            </div>
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-6 text-sm text-slate-500 sm:px-8 lg:px-12">
                Pengadilan Tinggi Agama Manado · Zona waktu Asia/Makassar
            </div>
        </footer>
    </div>
</body>
</html>
