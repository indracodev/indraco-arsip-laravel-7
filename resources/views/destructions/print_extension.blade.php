<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Perpanjangan Masa Simpan - {{ $archive->box_number }}</title>
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
            &larr; Kembali ke Retention Expiry
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-lg transition flex items-center gap-2">
            Cetak Formulir Perpanjangan
        </button>
    </div>

    <!-- Printable Form Card -->
    <div class="print-card w-full max-w-4xl bg-white text-slate-900 rounded-3xl p-8 sm:p-12 shadow-2xl space-y-8 border border-slate-200">
        
        <!-- Header & Logo -->
        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-6">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo-indraco-est.png') }}" alt="PT Indraco Logo" class="h-14 w-auto object-contain">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">PT INDRACO</h1>
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-widest block">Gudang & Depo Manajemen Arsip Perusahaan</span>
                </div>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-purple-100 text-purple-900 text-xs font-bold font-mono rounded border border-purple-300">RETENTION EXTENSION FORM</span>
            </div>
        </div>

        <!-- Form Title -->
        <div class="text-center space-y-1">
            <h2 class="text-xl font-extrabold uppercase tracking-wide text-slate-900 underline underline-offset-4">
                FORMULIR PERMOHONAN PERPANJANGAN MASA SIMPAN DOKUMEN
            </h2>
            <p class="text-sm font-mono text-slate-700 font-bold">No. Box Container: {{ $archive->box_number }}</p>
        </div>

        <!-- Archive Metadata Grid -->
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Metadata Berkas Dokumen:</h3>
            <table class="w-full text-left border-collapse border border-slate-300 text-xs">
                <tbody>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100 w-1/3">Perusahaan / Entitas</td>
                        <td class="border border-slate-300 p-2.5 font-bold">{{ $archive->company_name ?? 'PT Indraco' }}</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100">Departemen Pemilik</td>
                        <td class="border border-slate-300 p-2.5 font-bold">{{ $archive->department->name }} ({{ $archive->department->code }})</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100">Judul / Nama Berkas Arsip</td>
                        <td class="border border-slate-300 p-2.5 font-bold text-slate-900">{{ $archive->title }}</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100">Jenis Dokumen & Periode</td>
                        <td class="border border-slate-300 p-2.5">{{ $archive->document_type ?? 'UMUM' }} | {{ $archive->period_text }} ({{ $archive->period_yy_mm }})</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100">Masa Simpan Awal</td>
                        <td class="border border-slate-300 p-2.5">{{ $archive->retention_years }} Tahun (Expiry: {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }})</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 p-2.5 font-bold bg-slate-100">Lokasi Fisik Gudang</td>
                        <td class="border border-slate-300 p-2.5 font-mono">{{ $archive->location->full_location ?? 'Gudang' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Extension Reason -->
        <div class="space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <span class="text-xs font-extrabold uppercase text-slate-800 block">Alasan Pengajuan Perpanjangan Masa Simpan:</span>
            <p class="text-xs text-slate-800 whitespace-pre-line leading-relaxed font-medium">
                {{ $archive->extension_reason ?? '(Belum diisi - isi secara tertulis di formulir cetak ini)' }}
            </p>
        </div>

        <!-- Signatures -->
        <div class="pt-8 grid grid-cols-3 gap-4 text-center text-xs">
            <div class="space-y-12">
                <span class="text-slate-600 block">Diajukan Oleh,<br><strong>PIC / Pemohon Departemen</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">{{ auth()->user()->name }}</span>
                </div>
            </div>

            <div class="space-y-12">
                <span class="text-slate-600 block">Disetujui Oleh,<br><strong>Head of Department</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">( Tanda Tangan & Nama )</span>
                </div>
            </div>

            <div class="space-y-12">
                <span class="text-slate-600 block">Diterima & Diperbarui Oleh,<br><strong>PIC Gudang Arsip</strong></span>
                <div class="pt-4 border-b border-slate-400 max-w-[180px] mx-auto">
                    <span class="font-bold text-slate-900 text-sm">( Tanda Tangan & Nama )</span>
                </div>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-200 text-center text-[10px] text-slate-400">
            Dicetak dari Sistem DMS PT Indraco pada {{ date('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
