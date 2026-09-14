<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pemusnahan (BAP) - {{ $destructionLog->bap_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .print-card { border: none !important; shadow: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center">

    <!-- Control Header -->
    <div class="w-full max-w-4xl flex items-center justify-between mb-6 no-print">
        <a href="{{ route('destructions.index') }}" class="text-xs text-amber-400 font-semibold hover:underline flex items-center gap-1">
            &larr; Kembali ke Modul Pemusnahan
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-lg transition flex items-center gap-2">
            Cetak Dokumen BAP Ini
        </button>
    </div>

    <!-- Official BAP Document Container -->
    <div class="print-card w-full max-w-4xl bg-white text-slate-900 rounded-3xl p-8 sm:p-12 shadow-2xl space-y-8 border border-slate-200">
        
        <!-- Header Brand & Letterhead -->
        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-6">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo-indraco-est.png') }}" alt="PT Indraco Logo" class="h-14 w-auto object-contain">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">PT INDRACO</h1>
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-widest block">Gudang & Depo Manajemen Arsip Perusahaan</span>
                    <span class="text-[11px] text-slate-500">Jl. Raya Surabaya - Sidoarjo, Gedangan, Kab. Sidoarjo, Jawa Timur</span>
                </div>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-slate-100 text-slate-800 text-xs font-bold font-mono rounded border border-slate-300">CONFIDENTIAL</span>
            </div>
        </div>

        <!-- Document Title -->
        <div class="text-center space-y-1">
            <h2 class="text-xl font-extrabold uppercase tracking-wide text-slate-900 underline underline-offset-4">
                BERITA ACARA PEMUSNAHAN ARSIP (BAP)
            </h2>
            <p class="text-sm font-mono text-slate-700 font-bold">Nomor BAP: {{ $destructionLog->bap_number }}</p>
        </div>

        <!-- Introductory Paragraph -->
        <div class="text-sm text-slate-800 leading-relaxed space-y-3">
            <p>
                Pada hari ini, <span class="font-bold">{{ \Carbon\Carbon::parse($destructionLog->destruction_date)->isoFormat('dddd, D MMMM YYYY') }}</span>, bertempat di area Gudang Arsip PT Indraco, telah dilaksanakan pemusnahan dokumen/arsip perusahaan yang telah melampaui batas masa simpan (retention expiry) sesuai dengan ketentuan dan prosedur standar operasional (SOP) pengelolaan arsip PT Indraco.
            </p>
        </div>

        <!-- Destroyed Archive Details Table -->
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Rincian Dokumen Yang Dimusnahkan:</h3>
            <table class="w-full text-left border-collapse border border-slate-300 text-xs">
                <thead class="bg-slate-100 text-slate-800 font-bold">
                    <tr>
                        <th class="border border-slate-300 p-2.5">No. Box Arsip</th>
                        <th class="border border-slate-300 p-2.5">Judul / Nama Berkas</th>
                        <th class="border border-slate-300 p-2.5">Departemen</th>
                        <th class="border border-slate-300 p-2.5">Periode Dokumen</th>
                        <th class="border border-slate-300 p-2.5">Masa Simpan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-mono font-bold">{{ $destructionLog->archive->box_number }}</td>
                        <td class="border border-slate-300 p-2.5 font-bold">{{ $destructionLog->archive->title }}</td>
                        <td class="border border-slate-300 p-2.5">{{ $destructionLog->archive->department->name }} ({{ $destructionLog->archive->department->code }})</td>
                        <td class="border border-slate-300 p-2.5">{{ $destructionLog->archive->period_text }}</td>
                        <td class="border border-slate-300 p-2.5">{{ $destructionLog->archive->retention_years }} Tahun</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Method & Notes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <span class="text-slate-500 font-semibold block">Metode Pemusnahan Fisik:</span>
                <span class="font-bold text-slate-900 text-sm">{{ $destructionLog->method }}</span>
            </div>
            <div>
                <span class="text-slate-500 font-semibold block">Catatan Pelaksanaan & Saksi:</span>
                <span class="font-medium text-slate-800">{{ $destructionLog->notes ?? 'Pemusnahan berjalan sesuai SOP.' }}</span>
            </div>
            @if($destructionLog->scan_approval_destruction || $destructionLog->certificate_file)
            <div class="sm:col-span-2 border-t border-slate-200 pt-2 flex flex-wrap gap-4 no-print">
                @if($destructionLog->scan_approval_destruction)
                <a href="{{ asset('storage/' . $destructionLog->scan_approval_destruction) }}" target="_blank" class="text-xs font-bold text-amber-700 underline">
                    Lihat Scan Approval Pemusnahan
                </a>
                @endif
                @if($destructionLog->certificate_file)
                <a href="{{ asset('storage/' . $destructionLog->certificate_file) }}" target="_blank" class="text-xs font-bold text-rose-700 underline">
                    Lihat Lampiran Scan BAP / Dokumentasi
                </a>
                @endif
            </div>
            @endif
        </div>

        <!-- Closing Statement -->
        <p class="text-sm text-slate-800 leading-relaxed">
            Demikian Berita Acara Pemusnahan (BAP) ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan dicatat secara permanen dalam Log Audit Trail Sistem DMS PT Indraco.
        </p>

        <!-- Signature Block -->
        <div class="pt-8 grid grid-cols-3 gap-4 text-center text-xs">
            <!-- Signature 1 -->
            <div class="space-y-12">
                <span class="text-slate-600 block">Dipelajari & Diajukan Oleh,<br><strong>Kurator Gudang Arsip</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">{{ $destructionLog->proposedBy->name ?? 'Specialist Gudang' }}</span>
                </div>
            </div>

            <!-- Signature 2 -->
            <div class="space-y-12">
                <span class="text-slate-600 block">Disetujui Oleh,<br><strong>Head of Department</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">{{ $destructionLog->departmentApprovedBy->name ?? $destructionLog->approvedBy->name ?? 'PIC Departemen' }}</span>
                </div>
            </div>

            <!-- Signature 3 -->
            <div class="space-y-12">
                <span class="text-slate-600 block">Mengetahui & Disaksikan,<br><strong>Super Admin / Management</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">Direksi PT Indraco</span>
                </div>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-200 text-center text-[10px] text-slate-400">
            Dicetak otomatis dari Sistem Document Management System (DMS) PT Indraco pada {{ date('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
