@extends('layouts.app')
@section('title', 'Permintaan Diterima · '.config('app.name'))
@section('content')
<div class="mx-auto max-w-xl px-5 py-20 text-center"><div class="rounded-3xl border border-sky-300/20 bg-sky-300/10 p-10"><h1 class="text-3xl font-bold">Permintaan diterima</h1><p class="mt-3 text-slate-300">Jika akun memenuhi syarat, tautan reset password akan dikirim melalui WhatsApp. Periksa pesan Anda dalam beberapa saat.</p><a href="{{ route('login') }}" class="mt-7 inline-flex rounded-xl bg-sky-400 px-6 py-3 font-semibold text-slate-950">Kembali ke Login</a></div></div>
@endsection
