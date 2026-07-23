<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Keputusan Tersimpan · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\BuiltAsset::url('resources/css/app.css') }}">
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 text-slate-100 antialiased">
    <main class="w-full max-w-lg rounded-3xl border border-white/10 bg-white/[0.05] p-8 text-center sm:p-10">
        <div @class(['mx-auto grid h-16 w-16 place-items-center rounded-2xl text-3xl', 'bg-emerald-300/15 text-emerald-200' => $accepted, 'bg-rose-300/15 text-rose-200' => ! $accepted]) aria-hidden="true">{{ $accepted ? '✓' : '×' }}</div>
        <h1 class="mt-6 text-2xl font-bold text-white">Keputusan berhasil disimpan</h1>
        <p class="mt-3 text-slate-400">{{ $accepted ? 'Tamu telah diterima. Petugas akan memperoleh pemberitahuan untuk mengarahkan tamu.' : 'Penolakan telah dicatat. Petugas akan memperoleh pemberitahuan beserta alasannya.' }}</p>
        <p class="mt-5 text-sm text-slate-500">Tautan ini sudah tidak aktif dan keputusan tidak dapat diubah.</p>
    </main>
</body>
</html>
