{{--
    Box label (Laravel 7 archives/print_sticker.blade.php): sticker 10 x 10 cm with logo, EXP,
    QR (rack letter + slot) and the big rack/slot number, or the "Form A5" (TB 30g) detail sheet.
    Same paper, sizes and texts; values from ArchiveLabelController::label(). Physical units in
    public/css/pages/labels.css; QR drawn by the local qrcode.min.js (as Laravel 7). Always light.
    @var list<array<string, mixed>> $labels
--}}
@php($single = count($labels) === 1 ? $labels[0] : null)
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label Box Gudang 10x10cm - {{ $single ? ($single['box_number'] !== 'DRAFT-BOX' ? $single['box_number'] : $single['title']) : count($labels).' Arsip' }}</title>
    @foreach (['css/tokens.css', 'css/pages/labels.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
    @endforeach
    <link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
    <script src="{{ \App\Support\Asset::url('js/vendor/qrcode.min.js') }}" defer></script>
    <script src="{{ \App\Support\Asset::url('js/pages/label-print.js') }}" defer></script>
</head>
<body class="lbl-body">
<div class="lbl-sheet">
    <nav class="lbl-nav">
        <button type="button" class="lbl-btn" data-label-format>📋 Format: Stiker 10x10</button>
        <button type="button" class="lbl-btn" data-label-print>🖨️ Cetak Label</button>
        <a class="lbl-btn" href="{{ $backUrl }}" data-label-back>&larr; Kembali</a>
    </nav>

    @foreach ($labels as $label)
        <div class="lbl">
            <div class="lbl__head">
                <div class="lbl__brand">
                    <img src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="Logo INDRACO" width="200" class="lbl__logo">
                    <b class="lbl__exp">EXP. : {{ $label['exp'] }}</b>
                </div>
                <div class="lbl__qr" data-code="{{ $label['code'] }}" title="{{ $label['code'] }}"></div>
            </div>
            <div class="lbl__body">
                <div class="lbl__number">{{ $label['letter'] }}<br>{{ $label['slot_no'] }}</div>
            </div>
        </div>

        <div class="a5">
            <div class="a5__head">
                <img src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="Logo INDRACO" class="a5__logo">
                <div class="a5__kind">
                    <span class="a5__kind-title">LABEL BOX</span>
                    <span class="a5__kind-size">UKURAN TB 30g</span>
                </div>
            </div>
            <div class="a5__grid">
                <div class="a5__meta">
                    <div class="a5__facts">
                        <div><strong>Dept:</strong> {{ $label['department'] }}</div>
                        <div><strong>Tgl. Penyerahan:</strong> {{ $label['handover'] }}</div>
                        <div><strong>Periode Dokumen:</strong> {{ $label['period'] }}</div>
                        <div><strong>Masa Simpan:</strong> {{ $label['retention'] }}</div>
                    </div>
                    <div class="a5__contents-label">Isi Dokumen:</div>
                    <div class="a5__contents">{{ $label['contents'] }}</div>
                </div>
                <div class="a5__side">
                    <div class="a5__code">
                        <div class="a5__code-label">KODE BOX</div>
                        <div class="a5__code-value">{{ $label['box_number'] }}</div>
                    </div>
                    <div class="a5__slot">
                        <div class="a5__slot-label">NOMOR GUDANG</div>
                        <div class="a5__slot-value">
                            @if ($label['allocated'] && $label['warehouse'])
                                <span>{{ $label['warehouse'] }}</span>
                            @else
                                <span class="a5__pending">[ Diisi PIC Gudang ]</span>
                            @endif
                        </div>
                    </div>
                    <div class="a5__slot">
                        <div class="a5__slot-label">NOMOR RAK</div>
                        <div class="a5__slot-value">
                            @if ($label['allocated'] && $label['rack'])
                                <span>{{ $label['rack'] }}</span>
                                @if ($label['slot'])
                                    <div class="a5__slot-note">({{ $label['slot'] }})</div>
                                @endif
                            @else
                                <span class="a5__pending">[ Diisi PIC Gudang ]</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="a5__foot">
                <span>STATUS: <strong>{{ $label['allocated'] ? 'FINAL (TERALOKASI GUDANG)' : 'DRAFT / PRA-GUDANG (MENUNGGU VERIFIKASI)' }}</strong></span>
                <span>FORM A5 - PT INDRACO</span>
            </div>
        </div>
    @endforeach
</div>
</body>
</html>
