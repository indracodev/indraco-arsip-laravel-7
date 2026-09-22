@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Form Eksekusi BAP Pemusnahan - DMS PT Indraco')

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-6"
     x-data="{
         hasApprovalFile: false,
         onApprovalChange(e) {
             this.hasApprovalFile = e.target.files && e.target.files.length > 0;
         }
     }">
    <!-- Header -->
    <div>
        <a href="{{ route('destructions.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Retention Expiry
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i data-lucide="file-x" class="w-7 h-7 text-rose-600 dark:text-rose-400"></i>
            Eksekusi Berita Acara Pemusnahan Dokumen (BAP)
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">
            Sesuai aturan bisnis, wajib mengunggah berkas persetujuan (approval) pemusnahan sebelum tombol pengesahan aktif.
        </p>
    </div>

    <!-- Archive Summary Box -->
    <div class="bg-rose-500/10 border border-rose-500/30 rounded-3xl p-6 shadow-sm space-y-3">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 block">Informasi Berkas Yang Dimusnahkan</span>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs font-medium">
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">No. Box Arsip:</span>
                <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-sm">{{ $archive->box_number ?? 'DRAFT' }}</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Judul Berkas:</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->effective_title }}</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Departemen:</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->department->name ?? 'Dept' }}</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 block">Lokasi Fisik Terakhir:</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-sm">{{ $archive->location->full_location ?? 'Gudang' }}</span>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
        <form action="{{ route('destructions.store', $archive) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- BAP Number -->
                <div>
                    <label for="bap_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                        Nomor Berita Acara (BAP) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="bap_number" 
                        id="bap_number" 
                        value="{{ old('bap_number', $autoBapNumber) }}" 
                        required 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm font-mono text-amber-600 dark:text-amber-400 font-bold focus:outline-none focus:border-rose-500 transition"
                    >
                </div>

                <!-- Destruction Date -->
                <div>
                    <label for="destruction_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                        Tanggal Pelaksanaan Pemusnahan <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="destruction_date" 
                        id="destruction_date" 
                        value="{{ old('destruction_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium"
                    >
                </div>
            </div>

            <!-- Method -->
            <div>
                <label for="method" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                    Metode Fisik Pemusnahan <span class="text-rose-500">*</span>
                </label>
                <select name="method" id="method" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-rose-500 transition font-medium">
                    <option value="Pencacahan Mesin Industrial Paper Shredder">Pencacahan Mesin Industrial Paper Shredder</option>
                    <option value="Pembakaran Standard Suhu Tinggi">Pembakaran Standard Suhu Tinggi (Incinerator)</option>
                    <option value="Peleburan Bahan Kimia & Daur Ulang">Peleburan Bahan Kimia & Daur Ulang Industri</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                    Catatan Pelaksanaan & Saksi Pemusnahan
                </label>
                <textarea 
                    name="notes" 
                    id="notes" 
                    rows="3" 
                    placeholder="Contoh: Pemusnahan disaksikan oleh perwakilan tim Manajemen, Tim Legal, & PIC Departemen Keuangan..." 
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-rose-500 transition font-medium"
                >{{ old('notes') }}</textarea>
            </div>

            <!-- Scan Approval (Wajib) & Certificate File Upload -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 rounded-2xl bg-amber-500/10 border-2 border-dashed border-amber-500/40">
                <div>
                    <label for="approval_file" class="block text-xs font-black uppercase tracking-wider text-amber-950 dark:text-amber-200 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="file-check" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                        Upload Scan Form Approval Pemusnahan <span class="text-rose-500">* (Wajib)</span>
                    </label>
                    <p class="text-[11px] text-amber-800 dark:text-amber-300 mb-2 font-medium">Tombol pengesahan hanya akan aktif jika file persetujuan sudah dipilih.</p>
                    <input 
                        type="file" 
                        name="approval_file" 
                        id="approval_file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="onApprovalChange($event)"
                        required
                        class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 rounded-xl text-xs text-slate-700 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer"
                    >
                    @error('approval_file') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="certificate_file" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1.5">
                        Upload Scan Berita Acara / Foto Pemusnahan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <p class="text-[11px] text-slate-500 mb-2">Dapat dilampirkan setelah eksekusi fisik pemusnahan selesai.</p>
                    <input 
                        type="file" 
                        name="certificate_file" 
                        id="certificate_file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-700 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rose-500/20 file:text-rose-700 dark:file:text-rose-400 hover:file:bg-rose-500/30"
                    >
                </div>
            </div>

            <!-- Actions (Conditional Button) -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    <span x-show="!hasApprovalFile" class="text-rose-500 font-bold">• Unggah berkas formulir persetujuan (approval) pemusnahan untuk mengaktifkan tombol</span>
                    <span x-show="hasApprovalFile" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Berkas persetujuan siap, Anda dapat mengesahkan BAP
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('destructions.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition">
                        Batal
                    </a>
                    <button type="submit" 
                            :disabled="!hasApprovalFile"
                            :class="hasApprovalFile ? 'bg-rose-600 hover:bg-rose-500 text-white cursor-pointer shadow-lg shadow-rose-500/20' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed'"
                            onclick="return confirm('Apakah Anda yakin ingin mengesahkan pemusnahan berkas arsip ini? Tindakan ini tidak dapat dibatalkan.')" 
                            class="px-6 py-2.5 rounded-xl font-black text-xs sm:text-sm transition flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Sah-kan Berita Acara Pemusnahan (BAP)
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
