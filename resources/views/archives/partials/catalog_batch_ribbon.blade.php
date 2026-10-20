<!-- BATCH ACTIONS RIBBON (When items checked) -->
    <div x-show="selected.length > 0" x-transition class="bg-amber-500/10 border border-amber-500/40 rounded p-2.5 flex flex-wrap items-center justify-between gap-2 font-mono text-xs shadow-sm">
        <div class="flex items-center gap-2">
            <span class="p-1 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded">
                <i data-lucide="check-square" class="w-4 h-4"></i>
            </span>
            <span class="text-slate-800 dark:text-slate-200">
                Terpilih: <strong class="text-amber-600 dark:text-amber-400 font-extrabold" x-text="selected.length"></strong> box berkas arsip.
                <template x-if="selectedDrafts.length > 0 && selectedNonDrafts.length > 0">
                    <span class="text-slate-500 dark:text-slate-400">
                        (<span x-text="selectedDrafts.length"></span> draft, <span x-text="selectedNonDrafts.length"></span> non-draft)
                    </span>
                </template>
            </span>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if(!auth()->user()->isPicGudang())
            <!-- Batch Borrowing Action (Tarik Berkas) — hanya muncul untuk non-gudang jika ada box yang MEMENUHI SYARAT TARIK (tersimpan di gudang & belum ditarik) -->
            <template x-if="selectedBorrowable.length > 0">
                <button 
                    type="button" 
                    @click="window.location.href = '{{ route('borrowings.create') }}?archive_ids=' + selectedBorrowable.join(',') + '&from=archives{{ request()->has('embed') ? '&embed=1' : '' }}'"
                    class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded border border-purple-700 shadow transition flex items-center gap-1.5 cursor-pointer"
                    title="Ajukan penarikan untuk box arsip yang memenuhi syarat penarikan (tersimpan di gudang & belum ditarik)"
                >
                    <i data-lucide="corner-up-right" class="w-3.5 h-3.5 text-white"></i>
                    <span>Tarik Berkas (<span x-text="selectedBorrowable.length"></span> Box)</span>
                </button>
            </template>
            @endif

            <!-- Batch Delete Drafts Action — hanya muncul jika ada draft terpilih -->
            <template x-if="selectedDrafts.length > 0">
                <form action="{{ route('archives.batch_delete_drafts') }}" method="POST" class="inline" @submit.prevent="
                    const draftCount = selectedDrafts.length;
                    const ok = await window.showConfirmModal({
                        title: 'Hapus Draft Arsip Terpilih',
                        message: 'Apakah Anda yakin ingin menghapus ' + draftCount + ' data draft arsip yang dipilih?' + (selectedNonDrafts.length > 0 ? ' (' + selectedNonDrafts.length + ' berkas non-draft akan diabaikan)' : ''),
                        type: 'danger',
                        confirmText: 'Ya, Hapus ' + draftCount + ' Draft'
                    });
                    if (ok) $el.submit();
                ">
                    @csrf
                    <input type="hidden" name="ids" :value="selectedDrafts.join(',')">
                    @if(request()->has('embed'))
                        <input type="hidden" name="embed" value="1">
                    @endif
                    <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded border border-rose-700 shadow transition flex items-center gap-1.5 cursor-pointer" title="Hapus draft yang dipilih">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-white"></i>
                        <span>Hapus Draft (<span x-text="selectedDrafts.length"></span>)</span>
                    </button>
                </form>
            </template>

            @if(!auth()->user()->isPicDept())
            <!-- Batch Print Labels (Warehouse / Admin) -->
            <button 
                type="button" 
                @click="selectedPrintable.length > 0 ? window.openLabelModal(selectedPrintable) : alert('Tidak ada box terpilih yang memenuhi syarat cetak label (label hanya untuk arsip di gudang atau perpanjangan).')" 
                :disabled="selectedPrintable.length === 0"
                :class="selectedPrintable.length === 0 ? 'opacity-40 cursor-not-allowed bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700' : 'bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 border-amber-600 cursor-pointer shadow'"
                class="px-3 py-1 font-black text-xs rounded border transition flex items-center gap-1.5"
                title="Cetak label box untuk arsip berstatus di gudang atau perpanjangan"
            >
                <i data-lucide="printer" class="w-3.5 h-3.5" :class="selectedPrintable.length === 0 ? 'text-slate-400 dark:text-slate-500' : 'text-slate-950'"></i>
                <span>Cetak Label (<span x-text="selectedPrintable.length"></span>)</span>
            </button>
            @endif
        </div>
    </div>

    