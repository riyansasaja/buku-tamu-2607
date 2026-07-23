@extends('layouts.app')
@section('title', 'Tautan Tidak Tersedia · '.config('app.name'))
@section('content')
<div class="mx-auto max-w-xl px-5 py-20 text-center"><div class="rounded-3xl border border-white/10 bg-white/[0.05] p-10"><h1 class="text-3xl font-bold">Tautan tidak tersedia</h1><p class="mt-3 text-slate-400">Tautan salah, kedaluwarsa, telah dipakai, atau sudah diganti. Ajukan permintaan reset password baru.</p><a href="{{ route('password.request') }}" class="mt-7 inline-flex rounded-xl bg-sky-400 px-6 py-3 font-semibold text-slate-950">Minta Tautan Baru</a></div></div>
@endsection
