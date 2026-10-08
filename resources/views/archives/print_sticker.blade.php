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
            grid-template-columns: repeat(3, 1fr);
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

            .a5-label-container {
                display: none !important;
            }

            body.print-a5 .label {
                display: none !important;
            }

            body.print-a5 .a5-label-container {
                display: block !important;
                width: 200mm !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                page-break-after: always !important;
                border: 2px solid #000 !important;
                box-shadow: none !important;
                box-sizing: border-box !important;
            }

            body.print-a5 .container {
                width: 200mm !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav>
            <a href="{{ $isSingle && $singleArchive ? route('archives.show', $singleArchive->id) : route('archives.index') }}"
                class="btn">&larr; Kembali</a>
            <button type="button" onclick="toggleFormat()" id="btn-toggle-format" class="btn">📋 Format: Stiker 10x10</button>
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

                $isAllocated = !empty($archive->warehouse_location_id) || !empty($archive->location_id);
                $whName = $archive->location && $archive->location->warehouse ? ($archive->location->warehouse->name ?: $archive->location->warehouse->code) : ($archive->location->room_sector ?? null);
                $rackCode = $archive->location ? $archive->location->rack_code : null;
                $slotLabel = $archive->rackSlot ? ("Sap {$archive->rackSlot->sap_level}, {$archive->rackSlot->layer_label} Slot {$archive->rackSlot->slot_number}") : null;
                $effectivePeriod = $archive->effective_periode ?? ($archive->periode_doc ?? ($archive->periode ?? ''));
            @endphp

            <div class="label">
                <div class="label-header">
                    <div style="display: flex; flex-direction: column; height: 100%;">
                        <img src="{{ asset('images/logo_indraco.png') }}" alt="Logo INDRACO" width="200" height="auto" style="max-height: 28px; object-fit: contain; object-position: left;">
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

            <!-- FORMAT DETAIL A5 (TB 30g) -->
            <div class="a5-label-container" id="a5-container-{{ $archive->id }}" style="display: none; background: #fff; border: 2px solid #000; padding: 16px; border-radius: 4px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box; margin-bottom: 20px;">
                <!-- 1. Header Box: Logo Indraco (Left) & Label Box TB 30g (Right) -->
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 8px;">
                    <div>
                        <img src="{{ asset('images/logo_indraco.png') }}" alt="Logo INDRACO" style="max-height: 32px; width: auto; object-fit: contain;">
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 900; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; display: block;">LABEL BOX</span>
                        <span style="font-family: monospace; font-size: 10px; font-weight: 900; padding: 2px 8px; background: #000; color: #fff; border-radius: 4px;">UKURAN TB 30g</span>
                    </div>
                </div>

                <!-- 2. Main Grid: Left Column (Metadata & Isi Dokumen) vs Right Column (No Gudang & No Rak) -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div style="border-right: 2px solid #000; padding-right: 12px; font-family: monospace; font-size: 12px;">
                        <div style="border-bottom: 1px solid #ccc; padding-bottom: 8px; line-height: 1.6;">
                            <div><strong>Dept:</strong> {{ $archive->department->name ?? 'Departemen' }}
                                @if($archive->subDepartment)
                                    / {{ $archive->subDepartment->name }}
                                @endif
                            </div>
                            <div><strong>Tgl. Penyerahan:</strong> {{ $archive->tgl_penyerahan ? \Carbon\Carbon::parse($archive->tgl_penyerahan)->format('d/m/Y') : date('d/m/Y') }}</div>
                            <div><strong>Periode Dokumen:</strong> {{ $effectivePeriod }}</div>
                            <div><strong>Masa Simpan:</strong> {{ $archive->retention_duration_label }}</div>
                        </div>
                        <div style="margin-top: 8px;">
                            <div style="font-weight: bold; font-size: 10px; text-transform: uppercase; color: #555;">Isi Dokumen:</div>
                            <div style="font-family: sans-serif; font-size: 12px; margin-top: 4px; white-space: pre-line;">{{ $archive->content_description ?? $archive->title }}</div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="border: 2px solid #000; background: #f8fafc; padding: 6px; text-align: center; border-radius: 4px;">
                            <div style="font-size: 9px; font-family: monospace; font-weight: bold; text-transform: uppercase; color: #666;">KODE BOX</div>
                            <div style="font-family: monospace; font-weight: 900; font-size: 14px;">{{ $archive->box_number ?? 'DRAFT-BOX' }}</div>
                        </div>

                        <div style="border: 2px solid #000; padding: 8px; text-align: center; border-radius: 4px; background: #fff;">
                            <div style="font-size: 10px; font-family: monospace; font-weight: 900; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 2px; margin-bottom: 4px;">NOMOR GUDANG</div>
                            <div style="font-family: monospace; font-weight: 900; font-size: 15px;">
                                @if($isAllocated && $whName)
                                    <span>{{ $whName }}</span>
                                @else
                                    <span style="color: #999; font-size: 11px;">[ Diisi PIC Gudang ]</span>
                                @endif
                            </div>
                        </div>

                        <div style="border: 2px solid #000; padding: 8px; text-align: center; border-radius: 4px; background: #fff;">
                            <div style="font-size: 10px; font-family: monospace; font-weight: 900; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 2px; margin-bottom: 4px;">NOMOR RAK</div>
                            <div style="font-family: monospace; font-weight: 900; font-size: 15px;">
                                @if($isAllocated && $rackCode)
                                    <span>{{ $rackCode }}</span>
                                    @if($slotLabel)
                                        <div style="font-size: 10px; color: #b45309;">({{ $slotLabel }})</div>
                                    @endif
                                @else
                                    <span style="color: #999; font-size: 11px;">[ Diisi PIC Gudang ]</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Form Footer Bar -->
                <div style="display: flex; justify-content: space-between; border-top: 2px solid #000; padding-top: 6px; margin-top: 8px; font-family: monospace; font-size: 9px; color: #666;">
                    <span>STATUS: <strong style="color: #000; text-transform: uppercase;">{{ $isAllocated ? 'FINAL (TERALOKASI GUDANG)' : 'DRAFT / PRA-GUDANG (MENUNGGU VERIFIKASI)' }}</strong></span>
                    <span>FORM A5 - PT INDRACO</span>
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

        function toggleFormat() {
            const isA5 = document.body.classList.toggle('mode-a5');
            document.body.classList.toggle('print-a5', isA5);
            const btn = document.getElementById('btn-toggle-format');
            const a5Containers = document.querySelectorAll('.a5-label-container');
            const labels = document.querySelectorAll('.label');
            const container = document.querySelector('.container');

            if (isA5) {
                btn.innerText = '📋 Format: Form A5';
                labels.forEach(el => el.style.display = 'none');
                a5Containers.forEach(el => el.style.display = 'block');
                if (container) container.style.width = '200mm';
            } else {
                btn.innerText = '📋 Format: Stiker 10x10';
                labels.forEach(el => el.style.display = 'flex');
                a5Containers.forEach(el => el.style.display = 'none');
                if (container) container.style.width = '100mm';
            }
        }
    </script>
</body>

</html>
