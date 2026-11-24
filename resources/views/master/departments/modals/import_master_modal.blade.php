    <!-- WINDOWS FORM DIALOG: IMPORT EXCEL DATA MASTER -->
    <div x-data="masterDataImport()"
         x-show="open"
         x-cloak
         @open-import-master.window="reset(); open = true"
         @keydown.escape.window="if (open && !busy) open = false"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-5xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden font-mono">
            <!-- Window Title Bar -->
            <div class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-400"></i> frmMasterImport : Import Excel Data Master
                </span>
                <button @click="if (!busy) open = false" type="button" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div class="p-4 space-y-3 font-sans text-xs overflow-y-auto">
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-2">
                    <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">File Template</legend>
                    <p class="text-slate-600 dark:text-slate-400">
                        Isi sheet <strong>Data Master</strong> pada template (satu baris = satu type berkas). Departemen / sub departemen / type berkas yang sudah ada tidak diubah dan tidak diduplikasi.
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('master.departments.import.template') }}" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded font-mono font-bold flex items-center gap-1">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Download Template
                        </a>
                        <input type="file" accept=".xlsx,.csv" x-ref="file" @change="file = $event.target.files[0] || null; result = null; errors = []"
                               class="flex-1 min-w-[200px] p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white">
                        <button type="button" data-no-loading @click="send(false)" :disabled="!file || busy"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-mono font-bold rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="busy === 'preview'"></i>
                            <span x-text="busy === 'preview' ? 'Memproses...' : 'Pratinjau'"></span>
                        </button>
                    </div>
                </fieldset>

                <!-- Pesan sukses -->
                <div x-show="successMessage" class="p-2 rounded border border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold" x-text="successMessage"></div>

                <!-- Error -->
                <div x-show="errors.length" class="p-2 rounded border border-rose-400 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 max-h-40 overflow-y-auto">
                    <p class="font-bold mb-1">Error (<span x-text="errors.length"></span>) &mdash; perbaiki file lalu pratinjau ulang:</p>
                    <ul class="list-disc pl-5 space-y-0.5"><template x-for="(e, i) in errors" :key="'e' + i"><li x-text="e"></li></template></ul>
                </div>

                <!-- Peringatan -->
                <div x-show="warnings.length" class="p-2 rounded border border-amber-400 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 max-h-32 overflow-y-auto">
                    <p class="font-bold mb-1">Peringatan (<span x-text="warnings.length"></span>):</p>
                    <ul class="list-disc pl-5 space-y-0.5"><template x-for="(w, i) in warnings" :key="'w' + i"><li x-text="w"></li></template></ul>
                </div>

                <template x-if="result">
                    <div class="space-y-2">
                        <!-- Ringkasan -->
                        <div class="flex flex-wrap gap-1.5 font-mono text-[11px] font-bold">
                            <span class="px-2 py-0.5 rounded border border-slate-400 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200">Total Baris: <span x-text="result.summary.total_baris"></span></span>
                            <span class="px-2 py-0.5 rounded border border-purple-400 bg-purple-100 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300">Departemen Baru: <span x-text="result.summary.departemen_baru"></span></span>
                            <span class="px-2 py-0.5 rounded border border-amber-400 bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300">Sub Departemen Baru: <span x-text="result.summary.sub_departemen_baru"></span></span>
                            <span class="px-2 py-0.5 rounded border border-emerald-400 bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300">Type Berkas Baru: <span x-text="result.summary.dokumen_baru"></span></span>
                            <span class="px-2 py-0.5 rounded border border-slate-400 bg-slate-100 dark:bg-slate-900 text-slate-500 dark:text-slate-400">Sudah Ada (dilewati): <span x-text="result.summary.dokumen_sudah_ada"></span></span>
                        </div>

                        <!-- Tabel pratinjau -->
                        <div class="border border-slate-300 dark:border-slate-700 rounded overflow-auto max-h-80 bg-white dark:bg-slate-950">
                            <table class="w-full text-[11px]">
                                <thead class="bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-mono sticky top-0">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Baris</th>
                                        <th class="px-2 py-1 text-left">Departemen</th>
                                        <th class="px-2 py-1 text-left">Sub Departemen</th>
                                        <th class="px-2 py-1 text-left">Nama Dokumen</th>
                                        <th class="px-2 py-1 text-center">Retensi</th>
                                        <th class="px-2 py-1 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="r in result.rows" :key="r.row">
                                        <tr class="border-t border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200"
                                            :class="r.status === 'Error' ? 'bg-rose-50 dark:bg-rose-950/30' : (r.status === 'Baru' ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '')">
                                            <td class="px-2 py-1 font-mono" x-text="r.row"></td>
                                            <td class="px-2 py-1"><span class="font-mono font-bold" x-text="r.dept_code"></span> <span x-text="r.dept_name"></span></td>
                                            <td class="px-2 py-1"><span class="font-mono font-bold" x-text="r.sub_code"></span> <span x-text="r.sub_name || (r.sub_code ? '' : '-')"></span></td>
                                            <td class="px-2 py-1" x-text="r.doc_name || '-'"></td>
                                            <td class="px-2 py-1 text-center font-mono" x-text="r.retention || '-'"></td>
                                            <td class="px-2 py-1">
                                                <span class="px-1.5 py-0.5 rounded font-mono font-bold"
                                                      :class="r.status === 'Error' ? 'bg-rose-600 text-white' : (r.status === 'Baru' ? 'bg-emerald-600 text-white' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-200')"
                                                      x-text="r.status"></span>
                                                <span class="text-slate-500 dark:text-slate-400" x-text="r.message"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-2 p-3 border-t border-slate-300 dark:border-slate-700 font-mono">
                <button type="button" @click="if (!busy) open = false" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Tutup (Esc)</button>
                <button type="button" data-no-loading @click="send(true)" :disabled="!canCommit || busy"
                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="busy === 'commit'"></i>
                    <span x-text="busy === 'commit' ? 'Menyimpan...' : 'Simpan Import'"></span>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function masterDataImport() {
        return {
            open: false, file: null, busy: false, result: null, errors: [], warnings: [], successMessage: '',
            get canCommit() {
                const s = this.result && this.result.success && this.result.summary;
                return !!s && !this.successMessage && (s.departemen_baru + s.sub_departemen_baru + s.dokumen_baru) > 0;
            },
            reset() {
                this.file = null; this.busy = false; this.result = null; this.errors = []; this.warnings = []; this.successMessage = '';
                if (this.$refs.file) this.$refs.file.value = '';
            },
            async send(commit) {
                if (!this.file) return;
                this.busy = commit ? 'commit' : 'preview';
                this.errors = []; this.warnings = [];
                const formData = new FormData();
                formData.append('file', this.file);
                try {
                    const res = await fetch(commit ? '{{ route('master.departments.import.commit') }}' : '{{ route('master.departments.import.preview') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const json = await res.json();
                    // Error validasi Laravel berbentuk objek {field: [pesan]}
                    const errs = Array.isArray(json.errors) ? json.errors : Object.values(json.errors || {}).flat();
                    this.errors = errs.length ? errs : (res.ok ? [] : [json.message || 'Gagal memproses file.']);
                    this.warnings = json.warnings || [];
                    if (json.summary) this.result = json;
                    if (commit && res.ok && json.success) {
                        this.successMessage = json.message || 'Import berhasil.';
                        setTimeout(() => window.location.reload(), 1200);
                    }
                } catch (err) {
                    this.errors = ['Terjadi kesalahan koneksi saat memproses file: ' + err.message];
                } finally {
                    this.busy = false;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                }
            }
        };
    }
    </script>
    @endpush
