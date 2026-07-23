@extends('layouts.app')
@section('title', ($user->exists ? 'Edit' : 'Tambah').' Admin · '.config('app.name'))
@section('content')
<div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 lg:px-12">
    <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-sky-300">← Kembali</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $user->exists ? 'Edit Admin' : 'Tambah Admin' }}</h1>
    <p class="mt-2 text-slate-400">{{ $user->exists ? 'Perbarui identitas dan kontak admin.' : 'Password akan dibuat sendiri oleh admin baru melalui tautan WhatsApp.' }}</p>
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mt-8 space-y-6 rounded-3xl border border-white/10 bg-white/[0.05] p-6 sm:p-8">@csrf @if($user->exists) @method('PUT') @endif
        @foreach ([['name','Nama lengkap','text','name'],['email','Email login','email','email'],['whatsapp','Nomor WhatsApp','tel','tel']] as [$name,$label,$type,$autocomplete])
        <div><label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($name, $user->{$name}) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 outline-none focus:border-sky-400">@error($name)<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror</div>
        @endforeach
        @if($user->exists)
            <input type="hidden" name="is_active" value="{{ $user->is_active ? 1 : 0 }}"><div class="rounded-xl border border-white/10 p-4"><strong>Status: {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</strong><small class="mt-1 block text-slate-400">Ubah status melalui tombol pada daftar pengguna agar aturan keamanan tetap diterapkan.</small></div>
        @else
            <label class="flex gap-3 rounded-xl border border-white/10 p-4"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))><span><strong>Admin aktif</strong><small class="block text-slate-400">Akses baru tersedia setelah aktivasi password selesai.</small></span></label>
        @endif
        <div class="flex justify-end"><button class="min-h-11 rounded-xl bg-sky-400 px-5 font-semibold text-slate-950">{{ $user->exists ? 'Simpan Perubahan' : 'Simpan dan Kirim Undangan' }}</button></div>
    </form>
</div>
@endsection
