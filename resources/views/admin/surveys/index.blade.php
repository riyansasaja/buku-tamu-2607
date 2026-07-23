@extends('layouts.app')

@section('title', 'Rekap Survei · '.config('app.name'))

@section('content')
<div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Evaluasi pelayanan</p><h1 class="mt-2 text-3xl font-bold text-white">Rekap Hasil Survei</h1><p class="mt-2 text-slate-400">Periode {{ $filters->from->translatedFormat('d M Y') }}–{{ $filters->to->translatedFormat('d M Y') }} · WITA</p></div>
        <a href="{{ route('admin.reports.surveys.pdf', $filters->query()) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-300 px-5 font-bold text-slate-950 hover:bg-sky-200">Ekspor PDF</a>
    </div>

    @if($errors->any())<div class="mt-5 rounded-xl border border-rose-300/20 bg-rose-300/10 p-4 text-sm text-rose-100">Filter tidak valid. Periksa kembali pilihan Anda.</div>@endif

    <form method="GET" class="mt-7 grid gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-5 sm:grid-cols-2 lg:grid-cols-6">
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="date_from">Tanggal awal</label><input class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="date_from" name="date_from" type="date" value="{{ $filters->fromDate() }}"></div>
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="date_to">Tanggal akhir</label><input class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="date_to" name="date_to" type="date" value="{{ $filters->toDate() }}"></div>
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="rating">Rating</label><select class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="rating" name="rating"><option value="">Semua</option>@foreach(range(1,5) as $rating)<option value="{{ $rating }}" @selected($filters->rating === $rating)>{{ $rating }} bintang</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="response_status">Status respons</label><select class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="response_status" name="response_status"><option value="">Semua</option><option value="responded" @selected($filters->responseStatus === 'responded')>Sudah merespons</option><option value="not_responded" @selected($filters->responseStatus === 'not_responded')>Belum merespons</option></select></div>
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="employee_id">Pegawai</label><select class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="employee_id" name="employee_id"><option value="">Semua</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected($filters->employeeId === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold uppercase text-slate-400" for="work_unit_id">Unit kerja</label><select class="mt-1 min-h-11 w-full rounded-xl border border-white/10 bg-slate-950 px-3" id="work_unit_id" name="work_unit_id"><option value="">Semua</option>@foreach($workUnits as $unit)<option value="{{ $unit->id }}" @selected($filters->workUnitId === $unit->id)>{{ $unit->name }}</option>@endforeach</select></div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-6 lg:justify-end"><a href="{{ route('admin.surveys.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-white/10 px-5 text-slate-300">Reset</a><button class="min-h-11 rounded-xl bg-sky-400 px-5 font-semibold text-slate-950">Terapkan filter</button></div>
    </form>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([['Terkirim',$summary['sent']],['Respons',$summary['responded']],['Belum respons',$summary['not_responded']],['Tingkat respons',number_format($summary['response_rate'],1).'%'],['Rata-rata',$summary['average_rating'] !== null ? number_format($summary['average_rating'],2).' / 5' : '-']] as [$label,$value])
            <div class="rounded-2xl border border-sky-300/20 bg-sky-300/[0.07] p-5"><p class="text-sm text-sky-200">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-white">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="mt-7 rounded-2xl border border-white/10 bg-white/[0.04] p-5"><h2 class="font-bold text-white">Distribusi rating</h2><div class="mt-4 grid grid-cols-5 gap-2">@foreach($summary['distribution'] as $rating => $count)<div class="rounded-xl bg-slate-950/50 p-3 text-center"><p class="text-amber-300">{{ $rating }} ★</p><p class="mt-1 text-xl font-bold">{{ $count }}</p></div>@endforeach</div></section>

    <section class="mt-7 space-y-4">
        @forelse($invitations as $invitation)
            @php($visit = $invitation->visit)
            <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"><div class="flex flex-col gap-2 sm:flex-row sm:justify-between"><div><p class="font-bold text-white">{{ $visit->guest_name }}</p><p class="text-sm text-slate-400">{{ $visit->visit_number }} · {{ $visit->arrived_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA</p></div><span class="h-fit rounded-full px-3 py-1 text-xs font-semibold {{ $invitation->response ? 'bg-emerald-300/10 text-emerald-200' : 'bg-amber-300/10 text-amber-200' }}">{{ $invitation->response ? 'SUDAH MERESPONS' : 'BELUM MERESPONS' }}</span></div><div class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-slate-500">Alamat</p><p class="mt-1 text-slate-200">{{ $visit->address }}</p></div><div><p class="text-slate-500">Tujuan</p><p class="mt-1 text-slate-200">{{ $visit->employee->name }} · {{ $visit->employee->workUnit->name }}</p></div><div><p class="text-slate-500">Rating</p><p class="mt-1 text-amber-300">{{ $invitation->response ? str_repeat('★', $invitation->response->rating).' ('.$invitation->response->rating.'/5)' : '-' }}</p></div><div><p class="text-slate-500">Waktu respons</p><p class="mt-1 text-slate-200">{{ $invitation->response?->submitted_at?->timezone('Asia/Makassar')->format('d-m-Y H:i').' WITA' ?? '-' }}</p></div>@if($invitation->response?->comment)<div class="sm:col-span-2 lg:col-span-4"><p class="text-slate-500">Komentar / saran</p><p class="mt-1 whitespace-pre-line text-slate-200">{{ $invitation->response->comment }}</p></div>@endif</div></article>
        @empty<div class="rounded-2xl border border-dashed border-white/10 p-10 text-center text-slate-400">Belum ada hasil survei yang sesuai dengan filter.</div>@endforelse
    </section>
    <div class="mt-6">{{ $invitations->links() }}</div>
</div>
@endsection
