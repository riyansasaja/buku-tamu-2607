@extends('layouts.app')
@section('title', 'Pengguna Admin · '.config('app.name'))
@section('content')
<div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-wider text-sky-300">Akses sistem</p><h1 class="mt-2 text-3xl font-bold">Pengguna Admin</h1></div><a href="{{ route('admin.users.create') }}" class="rounded-xl bg-sky-400 px-5 py-3 font-semibold text-slate-950">Tambah Admin</a></div>
    <form class="mt-7 grid gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-[1fr_180px_auto]"><input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-4"><select name="status" class="min-h-11 rounded-xl border border-white/10 bg-slate-950 px-3"><option value="">Semua status</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select><button class="rounded-xl bg-sky-400 px-5 font-semibold text-slate-950">Terapkan</button></form>
    @if($errors->any())<div class="mt-4 rounded-xl border border-rose-300/20 bg-rose-300/10 p-4 text-rose-200">{{ $errors->first() }}</div>@endif
    <div class="mt-6 grid gap-4">
    @foreach($users as $user)
        @php($delivery = $user->userNotificationDeliveries->first())
        <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 sm:flex sm:items-center sm:justify-between">
            <div><h2 class="font-semibold">{{ $user->name }}</h2><p class="mt-1 text-sm text-slate-400">{{ $user->email }} · WhatsApp ••••{{ substr((string) $user->whatsapp, -4) }}</p><div class="mt-2 flex gap-2 text-xs"><span class="rounded-full px-2 py-1 {{ $user->is_active ? 'bg-emerald-300/10 text-emerald-200':'bg-slate-700 text-slate-300' }}">{{ $user->is_active ? 'Aktif':'Nonaktif' }}</span><span class="rounded-full bg-sky-300/10 px-2 py-1 text-sky-200">{{ $user->activated_at ? 'Sudah aktivasi':'Menunggu aktivasi' }}</span>@if($delivery)<span class="rounded-full bg-white/10 px-2 py-1">WA {{ $delivery->status->value }}</span>@endif</div></div>
            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0"><a href="{{ route('admin.users.edit',$user) }}" class="rounded-lg border border-white/10 px-3 py-2 text-sm">Edit</a>@if(!$user->activated_at)<form method="POST" action="{{ route('admin.users.resend-activation',$user) }}">@csrf<button class="rounded-lg border border-sky-300/30 px-3 py-2 text-sm text-sky-200">Kirim ulang</button></form><form method="POST" action="{{ route('admin.users.destroy',$user) }}" onsubmit="return confirm('Hapus admin yang belum aktivasi ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-300/30 px-3 py-2 text-sm text-rose-200">Hapus</button></form>@endif<form method="POST" action="{{ route('admin.users.status',$user) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $user->is_active ? 0:1 }}"><button class="rounded-lg border border-white/10 px-3 py-2 text-sm">{{ $user->is_active ? 'Nonaktifkan':'Aktifkan' }}</button></form></div>
        </article>
    @endforeach
    </div><div class="mt-6">{{ $users->links() }}</div>
</div>
@endsection
