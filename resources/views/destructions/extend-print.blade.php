{{--
    Formulir Permohonan Perpanjangan Masa Simpan Dokumen of one box (Laravel 7
    destructions/print_extension.blade.php): same paper and text; physical units in
    public/css/pages/retention-print.css. Always light. Access: ArchivePolicy@view (findings #5).
--}}
@php
    // Laravel 7 WarehouseLocation::full_location
    $loc = $archive->location;
    $location = $loc ? 'Gudang '.trim((string) preg_replace('/^(Gudang\s+)+/i', '', trim($loc->warehouse->name ?? 'Gudang'))).' - '
        .($loc->room_sector ? "[{$loc->room_sector}] " : '').$loc->rack_code.($loc->shelf_code ? " / {$loc->shelf_code}" : '') : 'Gudang';
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulir Perpanjangan Masa Simpan - {{ $archive->box_number }}</title>
    @foreach (['css/tokens.css', 'css/base.css', 'css/components.css', 'css/pages/retention-print.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
    @endforeach
    <link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
</head>
<body>
<main class="rp-page">
    <div class="rp-bar cluster no-print">
        <x-button variant="primary" onclick="window.print()">Cetak Formulir Perpanjangan</x-button>
        <x-link-button :href="route('destructions.index', ['tab' => 'extensions'])">Kembali ke Retention Expiry &rarr;</x-link-button>
    </div>

    <article class="rp">
        <div class="rp__head">
            <div class="rp__brand">
                <img class="rp__logo" src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="PT Indraco Logo">
                <div>
                    <h1>PT INDRACO</h1>
                    <span class="rp__sub">Gudang &amp; Depo Manajemen Arsip Perusahaan</span>
                </div>
            </div>
            <span class="rp__tag rp__tag--ext">RETENTION EXTENSION FORM</span>
        </div>

        <div class="rp__title">
            <h2>FORMULIR PERMOHONAN PERPANJANGAN MASA SIMPAN DOKUMEN</h2>
            <p>No. Box Container: {{ $archive->box_number }}</p>
        </div>

        <div>
            <h3 class="rp__h3">Metadata Berkas Dokumen:</h3>
            <table class="rp__table">
                <tbody>
                    <tr><td class="rp__key">Perusahaan / Entitas</td><td class="rp__bold">{{ $archive->company_name ?? 'PT Indraco' }}</td></tr>
                    <tr><td class="rp__key">Departemen Pemilik</td><td class="rp__bold">{{ $archive->department->name ?? '' }} ({{ $archive->department->code ?? '' }})</td></tr>
                    <tr><td class="rp__key">Judul / Nama Berkas Arsip</td><td class="rp__bold">{{ $archive->title }}</td></tr>
                    <tr><td class="rp__key">Jenis Dokumen &amp; Periode</td><td>{{ $archive->document_type ?? 'UMUM' }} | {{ $archive->period_text }} ({{ $archive->getAttribute('period_yy_mm') }})</td></tr>
                    <tr><td class="rp__key">Masa Simpan Awal</td><td>{{ \App\Services\RetentionCalculator::archiveDuration($archive) }} (Expiry: {{ $archive->retention_expiry_date?->format('M Y') ?? '-' }})</td></tr>
                    <tr><td class="rp__key">Lokasi Fisik Gudang</td><td class="rp__mono">{{ $location }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="rp__box rp__box--one">
            <span class="rp__h3">Alasan Pengajuan Perpanjangan Masa Simpan:</span>
            <p class="rp__reason">{{ $archive->getAttribute('extension_reason') ?? '(Belum diisi - isi secara tertulis di formulir cetak ini)' }}</p>
        </div>

        <div class="rp__signs">
            @foreach ([
                ['Diajukan Oleh,', 'PIC / Pemohon Departemen', auth()->user()->name],
                ['Disetujui Oleh,', 'Head of Department', '( Tanda Tangan & Nama )'],
                ['Diterima & Diperbarui Oleh,', 'PIC Gudang Arsip', '( Tanda Tangan & Nama )'],
            ] as [$caption, $role, $name])
                <div>
                    <span class="rp__label">{{ $caption }}<br><strong>{{ $role }}</strong></span>
                    <div class="rp__sign-name">{{ $name }}</div>
                    <div class="rp__sign-date">Tgl. ....................</div>
                </div>
            @endforeach
        </div>

        <div class="rp__foot">Dicetak dari Sistem DMS PT Indraco pada {{ now()->format('d/m/Y H:i:s') }}</div>
    </article>
</main>
</body>
</html>
