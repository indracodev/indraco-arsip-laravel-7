<!-- Modal Dept Approve -->
<div id="deptApproveModal-{{ $bLog->id }}" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 text-left">
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded shadow-2xl p-5 max-w-md w-full space-y-4 font-mono text-xs">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
            <h3 class="font-bold text-slate-900 dark:text-white uppercase flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-4 h-4 text-cyan-600"></i>
                <span>Persetujuan Penarikan Departemen</span>
            </h3>
            <button type="button" onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        @if($bLog->effective_approval_file)
        <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                <i data-lucide="file-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Surat Permohonan Terlampir</span>
            </div>
            <button type="button" 
                    onclick="window.openBorrowingFilePreview({{ $bLog->id }})"
                    class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                <i data-lucide="eye" class="w-3 h-3"></i>
                <span>Lihat Berkas</span>
            </button>
        </div>
        @endif

        <form action="{{ route('borrowings.dept_approve', $bLog) }}" method="POST" enctype="multipart/form-data" class="space-y-3 font-sans">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Unggah Berkas Tambahan / Pengganti (Opsional)</label>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1">Surat permohonan sudah ada di atas. Anda tidak perlu mengunggah ulang jika berkas sudah sesuai.</p>
                <input type="file" name="scan_approval_borrow" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 font-mono">
                <button type="button" onclick="document.getElementById('deptApproveModal-{{ $bLog->id }}').classList.add('hidden')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold">Batal</button>
                <button type="submit" class="px-3 py-1 bg-cyan-600 hover:bg-cyan-500 text-white border border-cyan-700 rounded text-xs font-bold transition">Sahkan & Teruskan ke Gudang</button>
            </div>
        </form>
    </div>
</div>