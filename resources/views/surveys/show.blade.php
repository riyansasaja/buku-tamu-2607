<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive">
    <title>Survei Kepuasan · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\BuiltAsset::url('resources/css/app.css') }}">
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 py-10 text-slate-100 antialiased">
<main class="w-full max-w-xl rounded-3xl border border-white/10 bg-white/[0.05] p-6 shadow-2xl shadow-black/20 sm:p-10">
    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">PTA Manado</p>
    <h1 class="mt-2 text-3xl font-bold text-white">Survei Kepuasan</h1>
    <p class="mt-3 text-slate-400">Bagaimana pengalaman pelayanan Anda? Penilaian ini hanya dapat dikirim satu kali.</p>
    <form method="POST" action="{{ route('surveys.store', ['token' => request()->route('token')]) }}" class="mt-8">
        @csrf
        <fieldset><legend class="text-sm font-semibold text-slate-200">Penilaian <span class="text-rose-300">*</span></legend>
            <div class="mt-4 text-center">
                <div class="star-rating gap-1 sm:gap-2" role="radiogroup" aria-label="Pilih penilaian satu sampai lima bintang">
                    @foreach(range(5, 1) as $rating)
                        <input id="rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" required @checked(old('rating') == $rating)>
                        <label for="rating-{{ $rating }}" title="{{ $rating }} bintang"><span aria-hidden="true">★</span><span class="sr-only">{{ $rating }} bintang</span></label>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-slate-500">Klik bintang sesuai tingkat kepuasan Anda.</p>
            </div>
            @error('rating')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
        </fieldset>
        <div class="mt-6"><label for="comment" class="text-sm font-semibold text-slate-200">Komentar atau saran <span class="font-normal text-slate-500">(opsional)</span></label><textarea id="comment" name="comment" maxlength="1000" rows="5" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-sky-300 focus:ring-2 focus:ring-sky-300/20" placeholder="Tuliskan saran untuk membantu kami meningkatkan pelayanan.">{{ old('comment') }}</textarea>@error('comment')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror</div>
        <button class="mt-6 min-h-12 w-full rounded-xl bg-sky-300 px-5 font-bold text-slate-950 transition hover:bg-sky-200">Kirim Penilaian</button>
    </form>
</main>
</body></html>
