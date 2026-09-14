@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan - DMS PT Indraco')

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('destructions.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Retention Expiry
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="clock" class="w-7 h-7 text-purple-600 dark:text-purple-400"></i>
                Pengajuan Perpanjangan Masa Simpan Dokumen
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">Jika dokumen masih dibutuhkan oleh Departemen, ajukan perpanjangan masa simpan dilengkapi alasan dan scan formulir.</p>
        </div>

        <a href="{{ route('destructions.extend_print', $archive) }}" target="_blank" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-amber-600 dark:text-amber-400 border border-slate-200 dark:border-slate-800 font-bold text-xs rounded-xl transition flex items-center gap-2">
            <i data-lucide="printer" class="w-4 h-4"></i> Cetak Form Perpanjangan
        </a>
    </div>

    <!-- Archive Summary Box -->
    <div class="bg-purple-500/10 border border-purple-500/30 rounded-3xl p-6 shadow-sm space-y-3">
        <span class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 block">Informasi Berkas Dokumen</span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-medium">
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">No. Box Arsip:</span>
                <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-sm">{{ $archive->box_number }}</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Judul Berkas:</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->title }}</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Departemen:</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->department->name }} ({{ $archive->department->code }})</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Masa Simpan Saat Ini:</span>
                <span class="font-bold text-purple-700 dark:text-purple-300 text-sm">{{ $archive->retention_years }} Tahun (Expiry: {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }})</span>
            </div>
        </div>
    </div>

    <!-- Extension Form Card -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
        <form action="{{ route('destructions.extend_store', $archive) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Additional Years -->
            <div>
                <label for="additional_years" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                    Tambahan Masa Simpan (Tahun) <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <select name="additional_years" id="additional_years" required class="w-36 px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition font-bold">
                        <option value="1">1 Tahun</option>
                        <option value="2">2 Tahun</option>
                        <option value="3">3 Tahun</option>
                        <option value="4">4 Tahun</option>
                        <option value="5">5 Tahun (Maks)</option>
                    </select>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Maksimal total simpan 5 tahun dari periode dokumen.</span>
                </div>
            </div>

            <!-- Extension Reason -->
            <div>
                <label for="extension_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                    Alasan Perpanjangan Masa Simpan <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="extension_reason" 
                    id="extension_reason" 
                    rows="4" 
                    required 
                    placeholder="Jelaskan secara rinci alasan dokumen ini masih harus disimpan (misal: audit perpajakan belum selesai, sengketa legal, audit kualifikasi supplier, dsb)..." 
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition font-medium"
                >{{ old('extension_reason', $archive->extension_reason) }}</textarea>
            </div>

            <!-- Scan Extension Form Upload -->
            <div>
                <label for="scan_extension_form" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                    Upload Scan Formulir Perpanjangan Masa Simpan (PDF/Image)
                </label>
                <input 
                    type="file" 
                    name="scan_extension_form" 
                    id="scan_extension_form" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-700 dark:text-slate-300 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-500/20 file:text-purple-700 dark:file:text-purple-400 hover:file:bg-purple-500/30"
                >
                @if($archive->scan_extension_form)
                    <span class="text-xs text-emerald-600 font-semibold mt-1 block">File scan perpanjangan sebelumnya sudah terupload.</span>
                @endif
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('destructions.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs sm:text-sm shadow-lg shadow-purple-500/20 transition">
                    Submit Perpanjangan Masa Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
