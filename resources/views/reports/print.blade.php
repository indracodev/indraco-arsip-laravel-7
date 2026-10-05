<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['title'] }} - PT INDRACO</title>
    
    <!-- Tailwind CSS (Local / CDN) -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .report-sheet {
            background: #ffffff;
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 24px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border-radius: 6px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .report-sheet {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }

        .report-table th {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .report-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 10.5px;
        }

        .report-table tr:nth-child(even) td {
            background-color: #f8fafc !important;
        }
    </style>
</head>
<body class="p-3 sm:p-6">

    <!-- FLOATING TOP BAR ACTION (SCREEN ONLY) -->
    <div class="no-print max-w-[1100px] mx-auto mb-3 bg-slate-900 text-white px-4 py-2 rounded-lg shadow flex items-center justify-between font-mono text-xs">
        <div class="flex items-center gap-2">
            <span class="p-1 bg-amber-500/20 text-amber-400 border border-amber-500/40 rounded">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
            </span>
            <span class="font-bold">Pratinjau Laporan PDF - PT INDRACO</span>
        </div>
        
        <div class="flex items-center gap-2">
            <button onclick="window.close()" type="button" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded border border-slate-700 transition flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                <span>Kembali</span>
            </button>
            <button onclick="window.print()" type="button" class="px-3.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded border border-amber-600 transition flex items-center gap-1.5 shadow">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak / Simpan PDF (Ctrl+P)</span>
            </button>
        </div>
    </div>

    <!-- MAIN REPORT SHEET CONTAINER (A4 PRINTABLE) -->
    <div class="report-sheet">
        
        <!-- SIMPLE HEADER: LOGO (25% SIZE) + TITLE + PERIODE PRINT -->
        <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-300">
            <!-- Left: Small Logo & Title -->
            <div class="flex items-center gap-3">
                <!-- Logo 25% size (height ~20px) -->
                <img src="{{ asset('images/logo-indraco.png') }}" alt="Logo Indraco" class="h-5 sm:h-6 w-auto object-contain shrink-0">
                <div class="border-l border-slate-300 pl-3">
                    <h1 class="text-xs sm:text-sm font-bold text-slate-900 uppercase font-mono tracking-wide leading-none">
                        {{ $report['title'] }}
                    </h1>
                </div>
            </div>

            <!-- Right: Periode Print -->
            <div class="text-right font-mono text-[10px] text-slate-500">
                <span>Periode Print: <strong class="text-slate-700">{{ $printedAt }}</strong></span>
            </div>
        </div>

        <!-- REPORT DATA TABLE -->
        <div class="overflow-x-auto mb-3">
            <table class="report-table w-full text-left border-collapse">
                <thead>
                    <tr>
                        @foreach($report['columns'] as $col)
                        <th class="{{ $loop->first ? 'text-center w-10' : '' }}">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row as $key => $val)
                        <td class="{{ $key === 'no' ? 'text-center font-mono font-bold text-slate-500' : '' }}">
                            @if(in_array($val, ['Aktif', 'Kosong (0%)', 'Optimal']))
                                <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">{{ $val }}</span>
                            @elseif(in_array($val, ['Penuh (100%)', 'Kritis (>=95%)', 'Terkunci (Lock)']))
                                <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-rose-100 text-rose-800 border border-rose-300">{{ $val }}</span>
                            @elseif(in_array($val, ['Hampir Penuh (>80%)', 'Padat (80-94%)', 'Perlu Pemantauan']))
                                <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-amber-100 text-amber-800 border border-amber-300">{{ $val }}</span>
                            @else
                                {{ $val }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($report['columns']) }}" class="text-center py-6 text-slate-500 font-mono text-xs">
                            Tidak ada rekaman data untuk laporan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- SIMPLE FOOTER: EXACTLY AS REQUESTED IN GAMBAR 3 -->
        <div class="pt-2 border-t border-slate-300 text-[10px] text-slate-600 font-mono">
            <span>&copy; {{ date('Y') }} PT INDRACO JAYA PERKASA - Dokumen Sistem Terpusat Halaman 1 dari 1</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        @if($autoPrint)
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
        @endif
    </script>
</body>
</html>
