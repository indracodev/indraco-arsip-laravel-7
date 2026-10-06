@php
    $singleArchive = $archive ?? (isset($archives) ? $archives->first() : null);
    $isSingle = isset($isSingle) ? $isSingle : (isset($archives) && count($archives) === 1);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Label Kardus Gudang 10x10cm -
        {{ $isSingle && $singleArchive ? (!empty($singleArchive->box_number) ? $singleArchive->box_number : ($singleArchive->title ?? 'Arsip')) : (isset($archives) ? count($archives) . ' Arsip' : 'Arsip') }}
    </title>
    <script src="{{ asset('js/vendor/qrcode.min.js') }}"></script>
    <style>
        @page {
            size: 100mm 100mm;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
        }

        body {
            display: flex;
            justify-content: center;
            font-size: 16px;
            font-family: Arial, Helvetica, sans-serif;
            padding-bottom: 20mm;
        }

        .container {
            width: 100mm;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 5mm;
        }

        nav {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2mm;
            padding: 5mm 0 2mm 0;
        }

        .btn {
            display: grid;
            text-decoration: none;
            text-align: center;
            padding: 8px 16px;
            background: transparent;
            color: black;
            border: solid 1px black;
            font-size: inherit;
            font-weight: normal;
            cursor: pointer;
            transition: .25s ease;
        }

        .btn:hover,
        .btn:focus-within,
        .btn.active {
            background: black;
            color: white;
        }

        .label {
            --label-gutter: 2mm;
            width: 100mm;
            height: 100mm;
            border: solid 1px black;
            display: flex;
            flex-direction: column;
            padding: var(--label-gutter);
            gap: var(--label-gutter);
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .label-header {
            display: flex;
            align-items: flex-start;
            gap: 4mm;
            justify-content: space-between;
            height: 22mm;
        }

        .header-info {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
        }

        .qr-code {
            aspect-ratio: 1/1;
            width: 22mm;
            height: 22mm;
            border: solid 1px grey;
            padding: 1mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            background: white;
        }

        .qr-code img,
        .qr-code canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain;
        }

        .label-body {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            border: solid 1px gray;
            overflow: hidden;
            padding: 2mm;
        }

        .label-number {
            font-size: 120px;
            font-weight: bold;
            text-align: center;
            line-height: .9;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            width: 100%;
            margin: 0 auto;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .container {
                width: 100mm !important;
                margin: 0 auto !important;
                gap: 0 !important;
            }

            nav {
                display: none !important;
            }

            .label {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                margin: 0 auto !important;
                width: 94mm !important;
                height: 94mm !important;
                page-break-inside: avoid !important;
                page-break-after: always !important;
            }

            .label:last-child {
                page-break-after: auto !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav>
            <a href="{{ $isSingle && $singleArchive ? route('archives.show', $singleArchive->id) : route('archives.index') }}"
                class="btn">&larr; Kembali</a>
            <button onclick="window.print()" class="btn">🖨️ Cetak Label</button>
        </nav>

        @foreach ($archives as $archive)
            @php
                $expDate = 'MM-YYYY';
                if (!empty($archive->retention_expiry_date)) {
                    $expDate = \Carbon\Carbon::parse($archive->retention_expiry_date)->format('m-Y');
                } elseif (!empty($archive->period_end_date) && !empty($archive->retention_years)) {
                    $expDate = \Carbon\Carbon::parse($archive->period_end_date)
                        ->addYears($archive->retention_years)
                        ->format('m-Y');
                } elseif (!empty($archive->end_year) && !empty($archive->retention_years)) {
                    $expDate = '12-' . ($archive->end_year + $archive->retention_years);
                }

                // Kode Rak (Huruf) & Nomor Urut Slot (Angka 2 digit) - Sesuai Logika Master Gudang
                $rackLetter = '';
                if ($archive->location && !empty($archive->location->rack_code)) {
                    if (preg_match('/([A-Za-z]+)$/i', $archive->location->rack_code, $m)) {
                        $rackLetter = strtoupper($m[1]);
                    }
                } elseif ($archive->rackSlot && !empty($archive->rackSlot->slot_code) && preg_match('/^([A-Za-z]+)/', $archive->rackSlot->slot_code, $m)) {
                    $rackLetter = strtoupper($m[1]);
                }

                if (empty($rackLetter)) {
                    if (!empty($archive->box_number) && preg_match('/([A-Za-z]{1,3})/i', $archive->box_number, $bm)) {
                        $rackLetter = strtoupper($bm[1]);
                    } else {
                        $letterIdx = intval(($archive->id - 1) / 20) % 26;
                        $rackLetter = chr(65 + $letterIdx);
                    }
                }

                $slotNo = '';
                if ($archive->rackSlot && !empty($archive->rackSlot->slot_number)) {
                    $slotNo = str_pad($archive->rackSlot->slot_number, 2, '0', STR_PAD_LEFT);
                } elseif ($archive->location && !empty($archive->location->shelf_code) && preg_match('/(\d+)/', $archive->location->shelf_code, $m)) {
                    $slotNo = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                }

                if (empty($slotNo)) {
                    if (!empty($archive->box_number) && preg_match('/(\d{1,3})$/', $archive->box_number, $nm)) {
                        $slotNo = str_pad(intval($nm[1]) % 100 ?: 1, 2, '0', STR_PAD_LEFT);
                    } else {
                        $slotNo = str_pad((($archive->id - 1) % 20) + 1, 2, '0', STR_PAD_LEFT);
                    }
                }

                $boxCode = $rackLetter . $slotNo;
            @endphp

            <div class="label">
                <div class="label-header">
                    <div style="display: flex; flex-direction: column; height: 100%;">
                        <img src="{{ asset('images/logo-indraco.png') }}" alt="Logo INDRACO" width="200" height="auto" style="max-height: 28px; object-fit: contain; object-position: left;">
                        {{-- <small style="font-size: 10px; opacity: .5;">*Document Management System</small> --}}
                        <b class="exp" style="margin-top: auto; font-size: 21px; line-height: 1;">EXP. : {{ $expDate }}</b>
                    </div>
                    <div id="qrcode-{{ $archive->id }}" class="qr-code" data-code="{{ $boxCode }}" title="{{ $boxCode }}"></div>
                </div>
                <div class="label-body">
                    <div class="label-number">
                        {{ $rackLetter }}<br>{{ $slotNo }}
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.qr-code').forEach(function(el) {
                var text = el.getAttribute('data-code');
                if (text && typeof QRCode !== "undefined") {
                    new QRCode(el, {
                        text: text,
                        width: 76,
                        height: 76,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        });
    </script>
</body>

</html>
