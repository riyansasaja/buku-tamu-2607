<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Administrasi Buku Tamu PTA Manado.">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-96 bg-gradient-to-b from-sky-500/20 to-transparent"></div>
        <div aria-hidden="true" class="absolute -left-32 top-48 -z-10 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-3 px-5 py-5 sm:px-8 lg:px-12">
                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-sky-400 text-slate-950 shadow-lg shadow-sky-500/20" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 3h8a2 2 0 0 1 2 2v16l-5-3-5 3V5a2 2 0 0 1 2-2Z" />
                            <path d="M10 8h4M10 12h4" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-sky-300">PTA Manado</span>
                        <span class="block font-semibold text-white">Buku Tamu Digital</span>
                    </span>
                </a>
            </div>
        </header>

        <main>
            @yield('content')
        </main>
    </div>
</body>
</html>
