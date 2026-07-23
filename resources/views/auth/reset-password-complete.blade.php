@extends('layouts.app')
@section('title', 'Password Diperbarui · '.config('app.name'))
@section('content')
<div class="mx-auto max-w-xl px-5 py-20 text-center"><div class="rounded-3xl border border-emerald-300/20 bg-emerald-300/10 p-10"><h1 class="text-3xl font-bold">Password berhasil diperbarui</h1><p class="mt-3 text-slate-300">Seluruh sesi lama telah diakhiri. Silakan masuk menggunakan password baru.</p><a href="{{ route('login') }}" class="mt-7 inline-flex rounded-xl bg-sky-400 px-6 py-3 font-semibold text-slate-950">Masuk ke Admin</a></div></div>
@endsection
