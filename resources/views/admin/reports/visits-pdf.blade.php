<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kunjungan PTA Manado</title>
    <style>
        @page { margin: 10mm 10mm 15mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: "DejaVu Sans", sans-serif; font-size: 8px; }
        header { height: 14mm; margin-bottom: 7mm; border-bottom: 2px solid #075985; }
        header .office { color: #075985; font-size: 13px; font-weight: bold; letter-spacing: .5px; }
        header .caption { margin-top: 2px; color: #475569; font-size: 8px; }
        footer { position: fixed; bottom: -10mm; left: 0; right: 0; color: #64748b; font-size: 7px; }
        footer .right { float: right; }
        h1 { margin: 0; color: #0f172a; font-size: 18px; }
        .meta { margin: 3px 0 12px; color: #475569; font-size: 8px; }
        .filters { margin-bottom: 10px; padding: 7px 9px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .filters strong { color: #075985; }
        .summary { width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 5px 0; }
        .summary td { width: 25%; padding: 8px; border: 1px solid #bae6fd; background: #f0f9ff; }
        .summary .label { color: #475569; font-size: 7px; text-transform: uppercase; }
        .summary .value { margin-top: 2px; color: #0c4a6e; font-size: 16px; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.detail thead { display: table-header-group; }
        table.detail tr { page-break-inside: avoid; }
        table.detail th { padding: 6px 4px; border: 1px solid #0c4a6e; background: #075985; color: white; font-size: 7px; text-align: left; }
        table.detail td { padding: 5px 4px; border: 1px solid #cbd5e1; vertical-align: top; line-height: 1.35; overflow-wrap: break-word; }
        table.detail tbody tr:nth-child(even) { background: #f8fafc; }
        .continuation { page-break-before: always; }
        .center { text-align: center; }
        .status { font-weight: bold; }
        .empty { padding: 20px !important; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="office">PENGADILAN TINGGI AGAMA MANADO</div>
        <div class="caption">Sistem Buku Tamu Digital - Laporan Administrasi Kunjungan</div>
    </header>
    <footer>
        <span>Dicetak {{ $generatedAt->translatedFormat('d F Y, H:i') }} WITA</span>
    </footer>

    <main>
        <h1>Laporan Kunjungan Tamu</h1>
        <div class="meta">Periode {{ $filters->from->translatedFormat('d F Y') }} sampai {{ $filters->to->translatedFormat('d F Y') }}</div>

        <div class="filters">
            <strong>Filter:</strong>
            Status {{ $filters->status ? ucfirst($filters->status->value) : 'Semua' }};
            Pegawai tujuan {{ $employee?->name ?? 'Semua' }}.
            Foto dan nomor WhatsApp tamu tidak disertakan dalam laporan ini.
        </div>

        <table class="summary">
            <tr>
                <td><div class="label">Total kunjungan</div><div class="value">{{ $summary['total'] }}</div></td>
                <td><div class="label">Diterima</div><div class="value">{{ $summary['accepted'] }}</div></td>
                <td><div class="label">Ditolak</div><div class="value">{{ $summary['rejected'] }}</div></td>
                <td><div class="label">Belum diputuskan</div><div class="value">{{ $summary['pending'] }}</div></td>
            </tr>
        </table>

        @include('admin.reports.partials.visit-table', ['rows' => $visits->take(5), 'offset' => 0])

        @foreach ($visits->skip(5)->chunk(8) as $chunk)
            <div class="continuation">
                @include('admin.reports.partials.visit-table', ['rows' => $chunk, 'offset' => 5 + ($loop->index * 8)])
            </div>
        @endforeach
    </main>

</body>
</html>
