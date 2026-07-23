<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Detail Tamu · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\BuiltAsset::url('resources/css/app.css') }}">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-3xl px-5 py-8 sm:px-8 sm:py-12">
        <header class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">PTA Manado</p>
            <h1 class="mt-2 text-3xl font-bold text-white">Detail Tamu</h1>
            <p class="mt-2 text-slate-400">Mohon periksa informasi berikut sebelum memberikan keputusan.</p>
        </header>

        <article class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.05] shadow-2xl shadow-black/20">
            <img src="{{ $photoUrl }}" alt="Foto {{ $visit->guest_name }}" class="aspect-[4/3] w-full bg-slate-900 object-cover sm:aspect-[16/8]">
            <div class="p-6 sm:p-8">
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nama tamu</dt><dd class="mt-2 text-lg font-semibold text-white">{{ $visit->guest_name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Waktu kedatangan</dt><dd class="mt-2 text-slate-200">{{ $visit->arrived_at->timezone('Asia/Makassar')->translatedFormat('d F Y, H:i') }} WITA</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alamat</dt><dd class="mt-2 whitespace-pre-line text-slate-200">{{ $visit->address }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Maksud kedatangan</dt><dd class="mt-2 whitespace-pre-line text-slate-200">{{ $visit->visit_purpose }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai tujuan</dt><dd class="mt-2 text-slate-200">{{ $visit->employee->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unit / Jabatan</dt><dd class="mt-2 text-slate-200">{{ $visit->employee->workUnit->name }} · {{ $visit->employee->position->name }}</dd></div>
                </dl>

                <div class="mt-8 border-t border-white/10 pt-8">
                    <h2 class="text-lg font-bold text-white">Keputusan kunjungan</h2>
                    <p class="mt-2 text-sm text-amber-200">Keputusan pertama bersifat final dan tidak dapat diubah.</p>
                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-rose-300/20 bg-rose-300/10 p-3 text-sm text-rose-100" role="alert">Keputusan belum disimpan. Periksa kembali alasan penolakan.</div>
                    @endif

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <form method="POST" action="{{ route('decisions.store', ['token' => request()->route('token')]) }}">
                            @csrf
                            <input type="hidden" name="decision" value="accepted">
                            <button type="submit" class="min-h-12 w-full rounded-xl bg-emerald-400 px-5 font-bold text-emerald-950 transition hover:bg-emerald-300 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-300">Terima Tamu</button>
                        </form>

                        <details @if($errors->has('decision_reason')) open @endif class="group rounded-xl border border-rose-300/20 bg-rose-300/[0.06] open:sm:col-span-2">
                            <summary class="flex min-h-12 cursor-pointer list-none items-center justify-center rounded-xl px-5 font-bold text-rose-200 transition hover:bg-rose-300/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-rose-300">Tolak Tamu</summary>
                            <form method="POST" action="{{ route('decisions.store', ['token' => request()->route('token')]) }}" class="border-t border-rose-300/15 p-4 sm:p-5">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <label for="decision_reason" class="block text-sm font-semibold text-slate-200">Alasan penolakan</label>
                                <textarea id="decision_reason" name="decision_reason" required minlength="3" maxlength="500" rows="4" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-rose-300 focus:ring-2 focus:ring-rose-300/20" placeholder="Tuliskan alasan agar dapat disampaikan kepada petugas."></textarea>
                                @error('decision_reason') <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p> @enderror
                                <button type="submit" class="mt-4 min-h-11 w-full rounded-xl bg-rose-400 px-5 font-bold text-rose-950 transition hover:bg-rose-300">Konfirmasi Penolakan</button>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        </article>
        <p class="mt-6 text-center text-xs text-slate-500">Tautan ini bersifat rahasia. Jangan teruskan kepada pihak lain.</p>
    </main>
</body>
</html>
