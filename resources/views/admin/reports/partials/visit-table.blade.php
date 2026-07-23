<table class="detail">
    <colgroup>
        <col style="width: 3%"><col style="width: 10%"><col style="width: 9%"><col style="width: 11%">
        <col style="width: 15%"><col style="width: 13%"><col style="width: 17%"><col style="width: 8%"><col style="width: 14%">
    </colgroup>
    <thead><tr><th class="center">No.</th><th>Nomor kunjungan</th><th>Waktu (WITA)</th><th>Nama tamu</th><th>Alamat</th><th>Pegawai tujuan</th><th>Maksud kedatangan</th><th>Status</th><th>Alasan</th></tr></thead>
    <tbody>
        @forelse ($rows as $visit)
            <tr>
                <td class="center">{{ $offset + $loop->iteration }}</td>
                <td>{{ $visit->visit_number }}</td>
                <td>{{ $visit->arrived_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }}</td>
                <td>{{ $visit->guest_name }}</td>
                <td>{{ $visit->address }}</td>
                <td>{{ $visit->employee->name }}<br>{{ $visit->employee->workUnit->name }} - {{ $visit->employee->position->name }}</td>
                <td>{{ $visit->visit_purpose }}</td>
                <td class="status">{{ match ($visit->status->value) { 'accepted' => 'Diterima', 'rejected' => 'Ditolak', default => 'Belum diputuskan' } }}</td>
                <td>{{ $visit->decision_reason ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="empty">Tidak ada kunjungan yang sesuai dengan filter laporan.</td></tr>
        @endforelse
    </tbody>
</table>
