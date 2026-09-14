<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Box Arsip Custom - DMS PT Indraco</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Base Print & Screen Styles -->
    <style id="print-dynamic-style">
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .sticker-card {
                border: 2px solid #000 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800" x-data="labelPrinter()">

    <!-- NO-PRINT TOOLBAR & CUSTOMIZER PANEL -->
    <div class="no-print bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Left Title & Counter -->
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-500 text-slate-950 rounded-xl font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-base font-extrabold tracking-tight text-white flex items-center gap-2">
                            Cetak Label Box Container Arsip
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ count($archives) }} Label
                            </span>
                        </h1>
                        <p class="text-xs text-slate-400 font-medium">Sesuaikan ukuran kertas label, layout grid, dan elemen informasi sebelum mencetak.</p>
                    </div>
                </div>

                <!-- Right Quick Actions -->
                <div class="flex items-center gap-2">
                    <button @click="toggleSettings = !toggleSettings" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span x-text="toggleSettings ? 'Sembunyikan Pengaturan' : 'Pengaturan Label'"></span>
                    </button>

                    <button onclick="window.print()" class="px-5 py-2 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Sekarang (Ctrl+P)
                    </button>

                    <a href="{{ route('archives.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                        Tutup
                    </a>
                </div>
            </div>

            <!-- EXPANDABLE CUSTOMIZER SETTINGS -->
            <div x-show="toggleSettings" x-transition class="mt-4 pt-4 border-t border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                
                <!-- 1. Preset Size Selector -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">1. Presets Ukuran Label</label>
                    <select x-model="presetSize" @change="applyPreset()" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 font-medium focus:border-amber-500 focus:outline-none">
                        <option value="100x75">Standard Box Label (100 mm × 75 mm)</option>
                        <option value="150x100">Kontainer Besar (150 mm × 100 mm)</option>
                        <option value="80x50">Medium Label (80 mm × 50 mm)</option>
                        <option value="60x40">Compact Label (60 mm × 40 mm)</option>
                        <option value="50x25">Mini Shelf Label (50 mm × 25 mm)</option>
                        <option value="custom">-- Custom Dimensions (Bebas) --</option>
                    </select>
                </div>

                <!-- 2. Custom Dimensions (Mm) -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">2. Dimensi Custom (Kertas Label)</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 block">Lebar (mm):</span>
                            <input type="number" x-model.number="widthMm" @input="updateStyles()" min="30" max="300" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 text-xs font-mono font-bold text-center">
                        </div>
                        <span class="text-slate-500 font-bold pt-3">×</span>
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 block">Tinggi (mm):</span>
                            <input type="number" x-model.number="heightMm" @input="updateStyles()" min="20" max="300" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 text-xs font-mono font-bold text-center">
                        </div>
                    </div>
                </div>

                <!-- 3. Typography Scale & Layout Grid -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">3. Skala Font & Grid Layout</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Ukuran Font:</span>
                            <select x-model="fontScale" @change="updateStyles()" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 font-medium">
                                <option value="sm">Kecil (Small)</option>
                                <option value="md">Normal (Medium)</option>
                                <option value="lg">Besar (Large)</option>
                            </select>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block">Grid Layout:</span>
                            <select x-model="gridCols" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-1.5 font-medium">
                                <option value="1">1 Label / Baris</option>
                                <option value="2">2 Kolom Grid</option>
                                <option value="3">3 Kolom Grid</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 4. Toggle Elements Checkboxes -->
                <div class="space-y-1.5">
                    <label class="font-extrabold uppercase text-[10px] tracking-wider text-amber-400 block">4. Elemen yang Ditampilkan</label>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[11px] text-slate-300">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showLogo" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Logo & Company
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showQr" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            QR Code
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showLocation" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Lokasi Gudang
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showPeriod" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Periode & Expiry
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer col-span-2">
                            <input type="checkbox" x-model="showDescription" class="rounded border-slate-700 text-amber-500 focus:ring-0">
                            Deskripsi Ringkas Berkas
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MAIN PRINTABLE CONTAINER -->
    <div class="p-6 min-h-[calc(100vh-80px)] flex flex-col items-center">

        <div :class="{
            'grid gap-6 w-full max-w-7xl justify-items-center': true,
            'grid-cols-1': gridCols == 1,
            'grid-cols-1 md:grid-cols-2': gridCols == 2,
            'grid-cols-1 md:grid-cols-3': gridCols == 3
        }">
            @foreach($archives as $item)
            <!-- Printable Sticker Card Item -->
            <div class="sticker-card bg-white border-4 border-slate-900 rounded-2xl p-5 shadow-2xl relative overflow-hidden flex flex-col justify-between transition-all"
                 :style="`width: ${widthMm}mm; min-height: ${heightMm}mm; box-sizing: border-box;`">

                <!-- Header: Logo & Company -->
                <div x-show="showLogo" class="flex items-center justify-between border-b-2 border-slate-900 pb-2 mb-2">
                    <div>
                        <span class="font-black tracking-widest text-amber-600 uppercase block" :class="fontScale == 'sm' ? 'text-[9px]' : (fontScale == 'lg' ? 'text-xs' : 'text-[10px]')">
                            LABEL CONTAINER ARSIP
                        </span>
                        <h2 class="font-extrabold text-slate-900 tracking-tight" :class="fontScale == 'sm' ? 'text-sm' : (fontScale == 'lg' ? 'text-2xl' : 'text-lg')">
                            {{ $item->company_name ?? 'PT INDRACO' }}
                        </h2>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-slate-500 block uppercase" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">DEPARTEMEN</span>
                        <span class="font-black text-slate-900 uppercase" :class="fontScale == 'sm' ? 'text-xs' : (fontScale == 'lg' ? 'text-base' : 'text-sm')">
                            {{ $item->department->code ?? 'GEN' }}
                        </span>
                    </div>
                </div>

                <!-- Box Code & QR Code Section -->
                <div class="bg-slate-50 p-3 rounded-xl border-2 border-slate-900 text-center space-y-1 my-1">
                    <span class="font-extrabold uppercase tracking-wider text-slate-500 block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[10px]'">
                        NOMOR KODE CONTAINER / BOX ARSIP
                    </span>
                    
                    <div class="font-black font-mono tracking-wider text-slate-950" :class="fontScale == 'sm' ? 'text-lg' : (fontScale == 'lg' ? 'text-3xl' : 'text-xl')">
                        {{ $item->box_number ?? 'BOX-PENDING-VERIFY' }}
                    </div>

                    <!-- QR Code Display -->
                    <div x-show="showQr" class="flex justify-center items-center pt-1">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($item->box_number ?? 'DRAFT') }}" 
                             alt="QR Code" 
                             class="border border-slate-300 rounded p-1 bg-white"
                             :class="fontScale == 'sm' ? 'w-14 h-14' : (fontScale == 'lg' ? 'w-24 h-24' : 'w-16 h-16')">
                    </div>
                </div>

                <!-- Metadata Grid -->
                <div class="grid grid-cols-2 gap-2 my-1" :class="fontScale == 'sm' ? 'text-[10px]' : (fontScale == 'lg' ? 'text-sm' : 'text-xs')">
                    <!-- Location -->
                    <div x-show="showLocation" class="border border-slate-300 p-2 rounded-lg bg-slate-50">
                        <span class="font-bold text-slate-500 uppercase block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">LOKASI RAK GUDANG</span>
                        <span class="font-extrabold text-slate-900 block" :class="fontScale == 'sm' ? 'text-xs' : 'text-sm'">
                            {{ $item->location->full_location ?? 'BELUM CHECK-IN' }}
                        </span>
                    </div>

                    <!-- Document Type -->
                    <div class="border border-slate-300 p-2 rounded-lg bg-slate-50">
                        <span class="font-bold text-slate-500 uppercase block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">JENIS DOKUMEN</span>
                        <span class="font-extrabold text-slate-900 block" :class="fontScale == 'sm' ? 'text-xs' : 'text-sm'">
                            {{ $item->document_type ?? 'UMUM' }}
                        </span>
                    </div>

                    <!-- Period -->
                    <div x-show="showPeriod" class="border border-slate-300 p-2 rounded-lg bg-slate-50">
                        <span class="font-bold text-slate-500 uppercase block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">PERIODE (YY-MM)</span>
                        <span class="font-extrabold text-amber-700 block" :class="fontScale == 'sm' ? 'text-xs' : 'text-sm'">
                            {{ $item->period_yy_mm ?? ($item->period_start_date ? \Carbon\Carbon::parse($item->period_start_date)->format('y-m') : '-') }}
                        </span>
                    </div>

                    <!-- Retention & Expiry -->
                    <div x-show="showPeriod" class="border border-slate-300 p-2 rounded-lg bg-slate-50">
                        <span class="font-bold text-slate-500 uppercase block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">MASA SIMPAN & EXPIRY</span>
                        <span class="font-extrabold text-rose-700 block" :class="fontScale == 'sm' ? 'text-xs' : 'text-sm'">
                            {{ $item->retention_years }} Thn ({{ $item->retention_expiry_date ? \Carbon\Carbon::parse($item->retention_expiry_date)->format('M Y') : '-' }})
                        </span>
                    </div>
                </div>

                <!-- Archive Description -->
                <div x-show="showDescription" class="border-t-2 border-slate-900 pt-2 text-xs">
                    <span class="font-bold text-slate-500 uppercase block" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">DESKRIPSI RINGKAS BERKAS:</span>
                    <p class="font-bold text-slate-900 line-clamp-2 mt-0.5" :class="fontScale == 'sm' ? 'text-[10px]' : 'text-xs'">
                        {{ $item->title }}
                    </p>
                </div>

                <!-- Label Footer -->
                <div class="flex justify-between items-center text-slate-400 font-mono border-t border-slate-200 pt-1.5 mt-1" :class="fontScale == 'sm' ? 'text-[8px]' : 'text-[9px]'">
                    <span>TGL: {{ $item->created_at ? $item->created_at->format('d/m/Y') : date('d/m/Y') }}</span>
                    <span>D-ARSIP PT INDRACO</span>
                </div>
            </div>
            @endforeach
        </div>

    </div>

    <!-- Alpine JS Label Printer Controller -->
    <script>
        function labelPrinter() {
            return {
                toggleSettings: true,
                presetSize: '100x75',
                widthMm: 100,
                heightMm: 75,
                gridCols: 1,
                fontScale: 'md',
                showLogo: true,
                showQr: true,
                showLocation: true,
                showPeriod: true,
                showDescription: true,

                init() {
                    this.applyPreset();
                },

                applyPreset() {
                    if (this.presetSize === '100x75') {
                        this.widthMm = 100;
                        this.heightMm = 75;
                    } else if (this.presetSize === '150x100') {
                        this.widthMm = 150;
                        this.heightMm = 100;
                    } else if (this.presetSize === '80x50') {
                        this.widthMm = 80;
                        this.heightMm = 50;
                    } else if (this.presetSize === '60x40') {
                        this.widthMm = 60;
                        this.heightMm = 40;
                    } else if (this.presetSize === '50x25') {
                        this.widthMm = 50;
                        this.heightMm = 25;
                    }
                    this.updateStyles();
                },

                updateStyles() {
                    const styleElem = document.getElementById('print-dynamic-style');
                    styleElem.innerHTML = `
                        @media print {
                            .no-print {
                                display: none !important;
                            }
                            body {
                                background: white !important;
                                padding: 0 !important;
                                margin: 0 !important;
                            }
                            @page {
                                size: ${this.widthMm}mm ${this.heightMm}mm;
                                margin: 0;
                            }
                            .sticker-card {
                                width: ${this.widthMm}mm !important;
                                height: ${this.heightMm}mm !important;
                                border: 2px solid #000 !important;
                                border-radius: 0 !important;
                                box-shadow: none !important;
                                margin: 0 auto !important;
                                page-break-inside: avoid !important;
                                page-break-after: always !important;
                                box-sizing: border-box !important;
                            }
                        }
                    `;
                }
            }
        }
    </script>
</body>
</html>
