@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Perpanjangan Masa Simpan - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]">
    <!-- DELPHI TOP TITLE & ACTIONS -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <a href="{{ route('destructions.index') }}" title="Kembali ke Retention Expiry" class="p-[4px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[3px] border border-slate-300 dark:border-slate-700 transition">
                <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
            </a>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <i data-lucide="clock" class="w-[15px] h-[15px] text-purple-600 dark:text-purple-400"></i>
                    <span>Pengajuan Perpanjangan Masa Simpan Dokumen</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Ajukan perpanjangan masa retensi dokumen arsip yang masih dibutuhkan operasional.
                </p>
            </div>
        </div>

        <div>
            <a href="{{ route('destructions.extend_print', $archive) }}" target="_blank" class="px-[10px] py-[3px] bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-amber-700 dark:text-amber-400 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px] rounded-[3px] transition flex items-center gap-[6px] shadow-2xs">
                <i data-lucide="printer" class="w-[12px] h-[12px]"></i>
                <span>Cetak Form (F9)</span>
            </a>
        </div>
    </div>

    <!-- DELPHI TGROUPBOX: INFORMASI BERKAS -->
    <div class="bg-purple-500/10 border border-purple-500/30 rounded-[4px] p-[10px] space-y-[6px] font-mono text-[11px] shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 block">Informasi Berkas Dokumen</span>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-[8px]">
            <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                <span class="text-[9px] font-bold text-slate-500 block uppercase">NO. BOX ARSIP</span>
                <span class="font-extrabold text-amber-600 dark:text-amber-400">{{ $archive->box_number ?? 'DRAFT' }}</span>
            </div>
            <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                <span class="text-[9px] font-bold text-slate-500 block uppercase">JUDUL BERKAS</span>
                <span class="font-bold text-slate-900 dark:text-white truncate block">{{ $archive->title }}</span>
            </div>
            <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                <span class="text-[9px] font-bold text-slate-500 block uppercase">DEPARTEMEN</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $archive->department->name }} ({{ $archive->department->code }})</span>
            </div>
            <div class="p-[6px] rounded-[3px] bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                <span class="text-[9px] font-bold text-slate-500 block uppercase">MASA SIMPAN SAAT INI</span>
                <span class="font-bold text-purple-700 dark:text-purple-300">{{ $archive->retention_years }} Thn (Exp: {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }})</span>
            </div>
        </div>
    </div>

    <!-- DELPHI FORM CONTAINER (TGroupBox) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[12px] shadow-xs space-y-[10px] font-sans">
        <form action="{{ route('destructions.extend_store', $archive) }}" method="POST" enctype="multipart/form-data" class="space-y-[10px]">
            @csrf

            <!-- Additional Years -->
            <div class="flex flex-col gap-[3px]">
                <label for="additional_years" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Tambahan Masa Simpan (Tahun) <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center gap-[8px]">
                    <select name="additional_years" id="additional_years" required class="w-[140px] px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 transition font-bold">
                        <option value="1">1 Tahun</option>
                        <option value="2">2 Tahun</option>
                        <option value="3">3 Tahun</option>
                        <option value="4">4 Tahun</option>
                        <option value="5">5 Tahun (Maks)</option>
                    </select>
                    <span class="text-[11px] text-slate-500 font-mono">Maksimal perpanjangan simpan 5 tahun dari periode dokumen.</span>
                </div>
            </div>

            <!-- Extension Reason -->
            <div class="flex flex-col gap-[3px]">
                <label for="extension_reason" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Alasan Perpanjangan Masa Simpan <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="extension_reason" 
                    id="extension_reason" 
                    rows="3" 
                    required 
                    placeholder="Jelaskan alasan dokumen masih harus disimpan (misal: audit perpajakan belum selesai, sengketa hukum, verifikasi supplier)..." 
                    class="w-full p-[6px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 transition"
                >{{ old('extension_reason', $archive->extension_reason) }}</textarea>
            </div>

            <!-- Scan Extension Form Upload -->
            <div class="flex flex-col gap-[3px]">
                <label for="scan_extension_form" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Upload Scan Formulir Perpanjangan Masa Simpan (PDF/Image)
                </label>
                <input 
                    type="file" 
                    name="scan_extension_form" 
                    id="scan_extension_form" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full px-[8px] py-[3px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-700 dark:text-slate-300"
                >
                @if($archive->scan_extension_form)
                    <span class="text-[10px] text-emerald-600 font-bold font-mono mt-[2px] block">File scan perpanjangan sebelumnya sudah tersimpan.</span>
                @endif
            </div>

            <!-- Actions -->
            <div class="pt-[8px] border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-[6px]">
                <a href="{{ route('destructions.index') }}" class="px-[12px] h-[28px] rounded-[3px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono font-bold text-[11px] transition flex items-center">
                    Batal
                </a>
                <button type="submit" class="px-[12px] h-[28px] rounded-[3px] bg-purple-600 hover:bg-purple-500 text-white font-mono font-bold text-[11px] border border-purple-700 shadow-2xs transition flex items-center gap-[6px]">
                    <i data-lucide="check" class="w-[12px] h-[12px]"></i>
                    <span>Submit Perpanjangan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
