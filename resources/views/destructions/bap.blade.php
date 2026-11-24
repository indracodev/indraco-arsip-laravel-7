{{--
    Berita Acara Pemusnahan Arsip (Laravel 7 destructions/bap.blade.php): same paper, text and
    signature blocks; physical units in public/css/pages/retention-print.css. Always light (no prefs.js).
    Access: DestructionLogPolicy@view (findings #5).
--}}
@php
    /** @var \App\Models\DestructionLog $log */
    $archive = $log->archive;
    $approvalField = filled($log->approval_file) ? 'approval_file' : 'scan_approval_destruction';
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara Pemusnahan (BAP) - {{ $log->bap_number }}</title>
    @foreach (['css/tokens.css', 'css/base.css', 'css/components.css', 'css/pages/retention-print.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
    @endforeach
    <link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
</head>
<body>
<main class="rp-page">
    <div class="rp-bar cluster no-print">
        <x-button variant="primary" onclick="window.print()">Cetak Dokumen BAP Ini</x-button>
        <x-link-button :href="route('destructions.index', ['tab' => 'destructions'])">Kembali ke Modul Pemusnahan &rarr;</x-link-button>
    </div>

    <article class="rp">
        <div class="rp__head">
            <div class="rp__brand">
                <img class="rp__logo" src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="PT Indraco Logo">
                <div>
                    <h1>PT INDRACO</h1>
                    <span class="rp__sub">Gudang &amp; Depo Manajemen Arsip Perusahaan</span>
                    <span class="rp__addr">Jl. Raya Surabaya - Sidoarjo, Gedangan, Kab. Sidoarjo, Jawa Timur</span>
                </div>
            </div>
            <span class="rp__tag">CONFIDENTIAL</span>
        </div>

        <div class="rp__title">
            <h2>BERITA ACARA PEMUSNAHAN ARSIP (BAP)</h2>
            <p>Nomor BAP: {{ $log->bap_number }}</p>
        </div>

        <p>Pada hari ini, <strong>{{ $log->destruction_date?->isoFormat('dddd, D MMMM YYYY') }}</strong>, bertempat di area Gudang Arsip PT Indraco, telah dilaksanakan pemusnahan dokumen/arsip perusahaan yang telah melampaui batas masa simpan (retention expiry) sesuai dengan ketentuan dan prosedur standar operasional (SOP) pengelolaan arsip PT Indraco.</p>

        <div>
            <h3 class="rp__h3">Rincian Dokumen Yang Dimusnahkan:</h3>
            <table class="rp__table">
                <thead><tr><th>No. Box Arsip</th><th>Judul / Nama Berkas</th><th>Departemen</th><th>Periode Dokumen</th><th>Masa Simpan</th></tr></thead>
                <tbody>
                    <tr>
                        <td class="rp__mono rp__bold">{{ $archive->box_number }}</td>
                        <td class="rp__bold">{{ $archive->title }}</td>
                        <td>{{ $archive->department->name ?? '' }} ({{ $archive->department->code ?? '' }})</td>
                        <td>{{ $archive->period_text }}</td>
                        <td>{{ \App\Services\RetentionCalculator::archiveDuration($archive) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rp__box">
            <div><span class="rp__label">Metode Pemusnahan Fisik:</span><span class="rp__value">{{ $log->method }}</span></div>
            <div><span class="rp__label">Catatan Pelaksanaan &amp; Saksi:</span><span>{{ $log->notes ?? 'Pemusnahan berjalan sesuai SOP.' }}</span></div>
            <div class="rp__links no-print">
                @if (filled($log->getAttribute($approvalField)))
                    <a href="{{ route('files.preview', ['source' => 'destruction', 'id' => $log->id, 'field' => $approvalField]) }}" target="_blank" rel="noopener">Lihat Scan Approval Pemusnahan</a>
                @endif
                <a href="{{ route('files.preview', ['source' => 'destruction', 'id' => $log->id, 'field' => 'certificate_file']) }}" target="_blank" rel="noopener">Lihat Lampiran Scan BAP / Dokumentasi</a>
            </div>
        </div>

        <p>Demikian Berita Acara Pemusnahan (BAP) ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan dicatat secara permanen dalam Log Audit Trail Sistem DMS PT Indraco.</p>

        <div class="rp__signs">
            @foreach ([
                ['Dipelajari &amp; Diajukan Oleh,', 'Kurator Gudang Arsip', $log->proposedBy->name ?? 'Specialist Gudang'],
                ['Disetujui Oleh,', 'Head of Department', $log->departmentApprovedBy->name ?? $log->approvedBy->name ?? 'PIC Departemen'],
                ['Mengetahui &amp; Disaksikan,', 'Super Admin / Management', 'Direksi PT Indraco'],
            ] as [$caption, $role, $name])
                <div>
                    <span class="rp__label">{!! $caption !!}<br><strong>{{ $role }}</strong></span>
                    <div class="rp__sign-name">{{ $name }}</div>
                    <div class="rp__sign-date">Tgl. ....................</div>
                </div>
            @endforeach
        </div>

        <div class="rp__foot">Dicetak otomatis dari Sistem Document Management System (DMS) PT Indraco pada {{ now()->format('d/m/Y H:i:s') }}</div>
    </article>
</main>
</body>
</html>
