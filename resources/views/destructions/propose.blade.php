@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Eksekusi BAP Pemusnahan - DMS PT Indraco')

@section('content')
<div class="space-y-[10px]">
    <!-- DELPHI TOP TITLE & BREADCRUMB -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <a href="{{ route('destructions.index') }}" title="Kembali ke Retention Expiry" class="p-[4px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[3px] border border-slate-300 dark:border-slate-700 transition">
                <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
            </a>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <i data-lucide="file-x" class="w-[15px] h-[15px] text-rose-600 dark:text-rose-400"></i>
                    <span>Eksekusi Berita Acara Pemusnahan Dokumen (BAP)</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    Status arsip akan diubah ke "Destroyed" dan kapasitas slot rak gudang dikosongkan secara otomatis.
                </p>
            </div>
        </div>
    </div>

    <!-- DELPHI TGROUPBOX: INFORMASI BERKAS -->
    <div class="bg-rose-500/10 border border-rose-500/30 rounded-[4px] p-[10px] space-y-[6px] font-mono text-[11px] shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 block">Informasi Berkas Yang Akan Dimusnahkan</span>
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
                <span class="text-[9px] font-bold text-slate-500 block uppercase">LOKASI RAK GUDANG</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $archive->location->full_location ?? 'Gudang' }}</span>
            </div>
        </div>
    </div>

    <!-- DELPHI FORM CONTAINER (TGroupBox) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[12px] shadow-xs space-y-[10px] font-sans">
        <form action="{{ route('destructions.store', $archive) }}" method="POST" enctype="multipart/form-data" class="space-y-[10px]">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-[8px]">
                <!-- BAP Number -->
                <div class="flex flex-col gap-[3px]">
                    <label for="bap_number" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Nomor Berita Acara (BAP) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="bap_number" 
                        id="bap_number" 
                        value="{{ old('bap_number', $autoBapNumber) }}" 
                        required 
                        class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-amber-600 dark:text-amber-400 font-bold focus:outline-none focus:border-rose-500 transition"
                    >
                </div>

                <!-- Destruction Date -->
                <div class="flex flex-col gap-[3px]">
                    <label for="destruction_date" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Tanggal Pelaksanaan Pemusnahan <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="destruction_date" 
                        id="destruction_date" 
                        value="{{ old('destruction_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium"
                    >
                </div>
            </div>

            <!-- Method -->
            <div class="flex flex-col gap-[3px]">
                <label for="method" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Metode Fisik Pemusnahan <span class="text-rose-500">*</span>
                </label>
                <select name="method" id="method" required class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition">
                    <option value="Pencacahan Mesin Industrial Paper Shredder">Pencacahan Mesin Industrial Paper Shredder</option>
                    <option value="Pembakaran Standard Suhu Tinggi">Pembakaran Standard Suhu Tinggi (Incinerator)</option>
                    <option value="Peleburan Bahan Kimia & Daur Ulang">Peleburan Bahan Kimia & Daur Ulang Industri</option>
                </select>
            </div>

            <!-- Notes -->
            <div class="flex flex-col gap-[3px]">
                <label for="notes" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Catatan Pelaksanaan & Saksi Pemusnahan
                </label>
                <textarea 
                    name="notes" 
                    id="notes" 
                    rows="2" 
                    placeholder="Contoh: Pemusnahan disaksikan oleh perwakilan tim Manajemen, Tim Legal, & PIC Departemen Keuangan..." 
                    class="w-full p-[6px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 transition"
                >{{ old('notes') }}</textarea>
            </div>

            <!-- Scan Approval & Certificate File Upload -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
                <div class="flex flex-col gap-[3px]">
                    <label for="scan_approval_destruction" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Scan Formulir Approval (Image/PDF)
                    </label>
                    <input 
                        type="file" 
                        name="scan_approval_destruction" 
                        id="scan_approval_destruction" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-[8px] py-[3px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-700 dark:text-slate-300"
                    >
                </div>

                <div class="flex flex-col gap-[3px]">
                    <label for="certificate_file" class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Scan BAP / Foto Fisik Pelaksanaan (Image/PDF)
                    </label>
                    <input 
                        type="file" 
                        name="certificate_file" 
                        id="certificate_file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-[8px] py-[3px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] font-mono text-slate-700 dark:text-slate-300"
                    >
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-[8px] border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-[6px]">
                <a href="{{ route('destructions.index') }}" class="px-[12px] h-[28px] rounded-[3px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono font-bold text-[11px] transition flex items-center">
                    Batal
                </a>
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengesahkan pemusnahan berkas arsip ini? Tindakan ini permanen.')" class="px-[12px] h-[28px] rounded-[3px] bg-rose-600 hover:bg-rose-500 text-white font-mono font-bold text-[11px] border border-rose-700 shadow-2xs transition flex items-center gap-[6px]">
                    <i data-lucide="check" class="w-[12px] h-[12px]"></i>
                    <span>Sahkan BAP Pemusnahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
