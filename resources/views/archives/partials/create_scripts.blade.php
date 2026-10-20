<script>
function archiveCreateApp() {
    const initialMonth = '{{ date('Y-m') }}';

    const departmentsData = @json($departments);
    const initialDeptId = '{{ $targetDeptId ?? old('department_id', auth()->user()->isPicDept() ? auth()->user()->department_id : ($departments->first()->id ?? '')) }}';
    const initialDept = departmentsData.find(d => String(d.id) === String(initialDeptId)) || departmentsData[0] || null;
    const initialSubDepts = (initialDept && initialDept.sub_departments) ? initialDept.sub_departments : [];
    const initialMaster = (initialDept && initialDept.master_archives) ? initialDept.master_archives : [];

    const defaultBoxes = [
        {
            id: 1,
            title: @json(old('title', '')),
            sub_department_id: '{{ old('sub_department_id', '') }}',
            document_type: @json(old('document_type', '')),
            periode_bulan: '{{ old('periode_bulan', '1') }}',
            follow_previous: false,
            physical_condition: 'Baik / Box Karton Standar TB 30g',
            similarityInfo: null,
            items: [
                { id: 1, document_name: '', period_start: initialMonth, period_end: initialMonth, notes: '' },
                { id: 2, document_name: '', period_start: initialMonth, period_end: initialMonth, notes: '' },
                { id: 3, document_name: '', period_start: initialMonth, period_end: initialMonth, notes: '' }
            ]
        }
    ];

    const initialBoxes = @json(!empty($preloadedBoxes) ? $preloadedBoxes : null) || defaultBoxes;

    return {
        isSubmitting: false,
        hasScanForm: false,
        draftRestored: false,
        draftSavedTime: null,
        autoSaveDebounceTimer: null,
        departments: departmentsData,
        selectedCompany: '{{ old('company_name', 'PT INDRACO GLOBAL INDONESIA') }}',
        selectedDeptId: initialDept ? initialDept.id : initialDeptId,
        selectedSubDeptId: '{{ old('sub_department_id', '') }}',
        subDepartments: initialSubDepts,
        masterArchives: initialMaster,
        currentMonth: initialMonth,
        tglPenyerahan: '{{ old('tgl_penyerahan', date('Y-m-d')) }}',

        // Multi-Box Workstation State
        activeBoxIndex: 0,
        boxes: initialBoxes,

        // Duplicate Modal State
        duplicateModalOpen: false,
        duplicateSourceIndex: 0,
        duplicateMode: 'full', // 'full' | 'metadata_only' | 'selected_items'
        duplicateSelectedItems: [],

        // Form Pengajuan Dokumen Modal State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date('Y-m-d') }}',
            dept_display: '',
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Simpan',
            masa_simpan: 1,
            catatan: 'Kondisi fisik box standar TB 30g dalam keadaan baik.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        // Similarity check debounce timer
        similarityTimer: null,

        saveDraftToStorage() {
            @if(!empty($isReStore)) return; @endif
            if (this.isSubmitting || window.formSubmitted || window.__dmsClearingDraft) return;
            try {
                if (!Array.isArray(this.boxes) || this.boxes.length === 0) return;
                const hasContent = this.boxes.some(b => 
                    (b.title && b.title.trim()) || 
                    (b.items && b.items.some(it => it.document_name && it.document_name.trim()))
                );
                if (!hasContent) return;

                const draft = {
                    userId: {{ auth()->id() ?? 0 }},
                    selectedCompany: this.selectedCompany,
                    selectedDeptId: this.selectedDeptId,
                    selectedSubDeptId: this.selectedSubDeptId,
                    tglPenyerahan: this.tglPenyerahan,
                    activeBoxIndex: this.activeBoxIndex,
                    boxes: this.boxes.map(b => ({
                        id: b.id,
                        title: b.title || '',
                        sub_department_id: b.sub_department_id || '',
                        document_type: b.document_type || '',
                        periode_bulan: b.periode_bulan || '1',
                        follow_previous: !!b.follow_previous,
                        physical_condition: b.physical_condition || 'Baik / Box Karton Standar TB 30g',
                        items: (b.items || []).map(it => ({
                            id: it.id,
                            document_name: it.document_name || '',
                            period_start: it.period_start || '',
                            period_end: it.period_end || '',
                            notes: it.notes || ''
                        }))
                    })),
                    savedAt: Date.now(),
                    savedTimeFormatted: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                };
                localStorage.setItem('dms_draft_archive_create', JSON.stringify(draft));
                this.draftSavedTime = draft.savedTimeFormatted;
            } catch (e) {}
        },

        triggerAutoSave() {
            if (this.isSubmitting || window.formSubmitted || window.__dmsClearingDraft) return;
            clearTimeout(this.autoSaveDebounceTimer);
            this.autoSaveDebounceTimer = setTimeout(() => {
                this.saveDraftToStorage();
            }, 400);
        },

        async clearSavedDraft() {
            if (window.showConfirmModal) {
                const ok = await window.showConfirmModal({
                    title: 'Kosongkan Draft Formulir?',
                    message: 'Apakah Anda yakin ingin menghapus seluruh data isian draft formulir ini dan memulai kembali dari formulir kosong?',
                    type: 'danger',
                    confirmText: 'Ya, Kosongkan Formulir',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm('Apakah Anda yakin ingin mengosongkan draft isian formulir ini?')) {
                return;
            }

            // Kunci auto-save dan beforeunload seketika agar tidak menyimpan ulang
            window.__dmsClearingDraft = true;
            window.formSubmitted = true;
            this.isSubmitting = true;
            if (this.autoSaveDebounceTimer) {
                clearTimeout(this.autoSaveDebounceTimer);
            }

            try {
                localStorage.removeItem('dms_draft_archive_create');
                sessionStorage.removeItem('dms_draft_archive_create');
                sessionStorage.setItem('dms_draft_cleared_at', Date.now().toString());
            } catch(e) {}

            this.boxes = JSON.parse(JSON.stringify(defaultBoxes));
            this.activeBoxIndex = 0;
            this.draftRestored = false;
            this.draftSavedTime = null;

            // Navigasi ulang bersih
            window.location.replace(window.location.href);
        },

        init() {
            this.updateSubDepartments(false);
            this.updateMasterArchives();

            // Cek jika baru saja dikosongkan
            let justCleared = false;
            try {
                if (sessionStorage.getItem('dms_draft_cleared_at')) {
                    justCleared = true;
                    sessionStorage.removeItem('dms_draft_cleared_at');
                    localStorage.removeItem('dms_draft_archive_create');
                }
            } catch(e) {}

            @if(!empty($fromExcelImport))
            try {
                localStorage.removeItem('dms_draft_archive_create');
            } catch(e) {}
            @endif

            @if(empty($isReStore) && empty($fromExcelImport))
            if (!justCleared) {
                // Auto-restore draft from localStorage across refresh / reconnection
                try {
                    const raw = localStorage.getItem('dms_draft_archive_create');
                    if (raw) {
                        const draft = JSON.parse(raw);
                        if (draft && Number(draft.userId) === Number({{ auth()->id() ?? 0 }})) {
                            if (Array.isArray(draft.boxes) && draft.boxes.length > 0) {
                                const hasDraftContent = draft.boxes.some(b => 
                                    (b.title && b.title.trim()) || 
                                    (b.items && b.items.some(it => it.document_name && it.document_name.trim()))
                                );
                                if (hasDraftContent) {
                                    this.boxes = draft.boxes;
                                    if (draft.selectedCompany) this.selectedCompany = draft.selectedCompany;
                                    if (draft.selectedDeptId) this.selectedDeptId = draft.selectedDeptId;
                                    if (draft.selectedSubDeptId) this.selectedSubDeptId = draft.selectedSubDeptId;
                                    if (draft.tglPenyerahan) this.tglPenyerahan = draft.tglPenyerahan;
                                    this.activeBoxIndex = Math.min(draft.activeBoxIndex || 0, this.boxes.length - 1);
                                    this.draftRestored = true;
                                    this.draftSavedTime = draft.savedTimeFormatted || 'Sesi Sebelumnya';
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Gagal memulihkan draft form arsip:', e);
                }
            }
            @endif

            this.boxes.forEach(b => {
                if (this.selectedSubDeptId && !b.sub_department_id) {
                    b.sub_department_id = this.selectedSubDeptId;
                }
                this.onBoxSubDeptChange(b);
            });

            // Watch changes to trigger continuous autosave
            this.$watch('boxes', () => this.triggerAutoSave());
            this.$watch('selectedSubDeptId', () => this.triggerAutoSave());
            this.$watch('tglPenyerahan', () => this.triggerAutoSave());
            this.$watch('activeBoxIndex', () => this.triggerAutoSave());

            // Save on beforeunload only if not currently submitting
            window.addEventListener('beforeunload', () => {
                if (!this.isSubmitting && !window.formSubmitted && !window.__dmsClearingDraft) {
                    this.saveDraftToStorage();
                }
            });

            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        isFatAccount() {
            @if(auth()->check() && (
                (auth()->user()->department && in_array(strtoupper(auth()->user()->department->code), ['FAT', 'FIN', 'FATCLM'])) ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FAT') ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FIN')
            ))
                return true;
            @else
                const currentDept = this.departments.find(d => String(d.id) === String(this.selectedDeptId));
                if (currentDept && currentDept.code && ['FAT', 'FIN', 'FATCLM'].includes(String(currentDept.code).toUpperCase())) {
                    return true;
                }
                return false;
            @endif
        },

        getMaxPeriodMonths() {
            return this.isFatAccount() ? 120 : 60;
        },

        getPresetMonths() {
            return this.isFatAccount() 
                ? [1, 3, 4, 6, 12, 15, 24, 60, 120] 
                : [1, 3, 4, 6, 12, 15, 24, 60];
        },

        getTotalItemsCount() {
            return this.boxes.reduce((sum, b) => sum + (b.items ? b.items.length : 0), 0);
        },

        addBox() {
            const nextNum = this.boxes.length + 1;
            // Auto inherit sub-dept from active box if set
            const prevBox = this.boxes[this.activeBoxIndex] || this.boxes[this.boxes.length - 1];
            const inheritedSubDept = prevBox ? prevBox.sub_department_id : (this.selectedSubDeptId || '');
            const inheritedPeriod = prevBox && prevBox.periode_bulan ? prevBox.periode_bulan : '1';

            const tempBox = { sub_department_id: inheritedSubDept };
            const available = this.getBoxMasterArchives(tempBox);
            const defaultDoc = '';
            const defaultDocType = (inheritedSubDept && available.length > 0) ? (available[0].document_type || '') : '';

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: '',
                sub_department_id: inheritedSubDept,
                document_type: defaultDocType,
                periode_bulan: inheritedPeriod,
                follow_previous: true,
                physical_condition: 'Baik / Box Karton Standar TB 30g',
                similarityInfo: null,
                items: [
                    { id: Date.now() + 1, document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ]
            });
            this.activeBoxIndex = this.boxes.length - 1;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        promptCustomDocName(item) {
            const current = item.document_name || '';
            const val = prompt('Masukkan nama berkas dokumen kustom:', current);
            if (val !== null && val.trim()) {
                item.document_name = val.trim();
            }
        },

        getBoxFinalPeriod(box, bIdx) {
            if (bIdx > 0 && box.follow_previous) {
                const prev = this.boxes[bIdx - 1];
                const prevVal = (prev && prev.periode_bulan) ? prev.periode_bulan : '1';
                return prevVal ? (prevVal + ' Bulan') : '1 Bulan';
            }
            const val = box.periode_bulan || '1';
            return val ? (val + ' Bulan') : '1 Bulan';
        },

        getPreviousBoxPeriodText(bIdx) {
            if (bIdx <= 0) return '1 Bulan';
            const prev = this.boxes[bIdx - 1];
            return (prev && prev.periode_bulan) ? (prev.periode_bulan + ' Bulan') : '1 Bulan';
        },

        @include('archives.partials.create_box_operations')

        getBoxCalculatedPeriod(box) {
            if (!box || !box.items || box.items.length === 0) {
                return { label: '1 Bulan', range: '-', months: 1 };
            }

            let min = null;
            let max = null;

            box.items.forEach(it => {
                if (it.period_start) {
                    if (!min || it.period_start < min) min = it.period_start;
                }
                if (it.period_end) {
                    if (!max || it.period_end > max) max = it.period_end;
                }
            });

            if (!min || !max) {
                return { label: '1 Bulan', range: this.currentMonth, months: 1 };
            }

            const [y1, m1] = min.split('-').map(Number);
            const [y2, m2] = max.split('-').map(Number);
            let months = (y2 - y1) * 12 + (m2 - m1) + 1;

            if (months > this.getMaxPeriodMonths()) months = this.getMaxPeriodMonths();
            if (months < 1) months = 1;

            return {
                label: `${months} Bulan`,
                range: min === max ? min : `${min} s/d ${max}`,
                months: months
            };
        },

        validatePeriod(item) {
            if (item.period_end && item.period_end > this.currentMonth) {
                item.period_end = this.currentMonth;
            }
            if (item.period_start && item.period_start > this.currentMonth) {
                item.period_start = this.currentMonth;
            }
            if (item.period_start && item.period_end && item.period_start > item.period_end) {
                item.period_start = item.period_end;
            }
        },

        updateSubDepartments(clearSelection = true) {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            if (dept && dept.sub_departments) {
                this.subDepartments = dept.sub_departments;
            } else {
                this.subDepartments = [];
            }
            if (clearSelection) {
                this.selectedSubDeptId = '';
            }
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                if (clearSelection) {
                    b.sub_department_id = '';
                }
                this.onBoxSubDeptChange(b);
            });
        },

        updateMasterArchives() {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            let allArchives = [];
            if (dept && dept.master_archives && dept.master_archives.length > 0) {
                allArchives = dept.master_archives;
                this.filterAndSetArchives(allArchives);
            } else if (this.selectedDeptId) {
                fetch('{{ url('/api/departments') }}/' + this.selectedDeptId + '/master-archives')
                    .then(res => res.json())
                    .then(data => {
                        if (data.master_archives) {
                            if (dept) dept.master_archives = data.master_archives;
                            this.filterAndSetArchives(data.master_archives);
                        } else {
                            this.masterArchives = [];
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching master archives:', err);
                        this.masterArchives = [];
                    });
            } else {
                this.masterArchives = [];
            }
        },

        filterAndSetArchives(allArchives) {
            this.masterArchives = allArchives;
            this.boxes.forEach(b => {
                this.onBoxSubDeptChange(b);
            });
        },

        getBoxMasterArchives(box) {
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            if (!all || all.length === 0) return [];

            const subId = (box && box.sub_department_id !== undefined && box.sub_department_id !== '' && box.sub_department_id !== null) 
                ? box.sub_department_id 
                : this.selectedSubDeptId;

            // Jika memilih Inti Departemen
            if (subId === 'inti') {
                return all.filter(m => !m.sub_department_id);
            }

            // Jika memilih sub-departemen spesifik
            if (subId && subId !== '') {
                return all.filter(m => String(m.sub_department_id) === String(subId));
            }

            // Saat mode pilih sub-departemen (belum memilih sub-dept / default), langsung muncul semua
            return all;
        },

        getItemDocType(box, item) {
            if (!item || !item.document_name) return box ? (box.document_type || '') : '';
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            const matched = all.find(m => m.name === item.document_name);
            if (matched && matched.document_type) {
                return matched.document_type;
            }
            return box ? (box.document_type || '') : '';
        },

        onBoxSubDeptChange(box) {
            if (!box) return;
            const available = this.getBoxMasterArchives(box);
            if (available && available.length > 0) {
                if (box.items && box.items.length > 0) {
                    box.items.forEach(it => {
                        // Jika dokumen terpilih sebelumnya tidak valid di daftar baru, kosongkan
                        if (it.document_name && !available.some(m => m.name === it.document_name)) {
                            it.document_name = '';
                        }
                        if (it.document_name) {
                            this.onMasterDocChange(box, it);
                        }
                    });
                }
                const firstValidItem = box.items ? box.items.find(it => it.document_name) : null;
                if (firstValidItem) {
                    this.onMasterDocChange(box, firstValidItem);
                } else if (box.sub_department_id === 'inti' || (box.sub_department_id && box.sub_department_id !== '')) {
                    box.document_type = available[0].document_type || '';
                    if (available[0].retention_years) {
                        box.retention_years = available[0].retention_years;
                    }
                } else {
                    box.document_type = '';
                }
            } else {
                // Jika tidak ada master berkas untuk sub-departemen ini, kosongkan tipe dokumen dan butir dokumen
                box.document_type = '';
                if (box.items && box.items.length > 0) {
                    box.items.forEach(it => {
                        it.document_name = '';
                    });
                }
                const subId = (box && box.sub_department_id !== undefined && box.sub_department_id !== '' && box.sub_department_id !== null)
                    ? box.sub_department_id 
                    : this.selectedSubDeptId;
                if (subId && subId !== 'inti') {
                    const currentSub = this.subDepartments.find(s => String(s.id) === String(subId));
                    if (currentSub && currentSub.retention_years) {
                        box.retention_years = currentSub.retention_years;
                    }
                }
            }
        },

        onMasterDocChange(box, item) {
            if (!item || !item.document_name) return;
            const dept = this.departments.find(d => d.id == this.selectedDeptId);
            const all = (dept && dept.master_archives && dept.master_archives.length > 0) ? dept.master_archives : (this.masterArchives || []);
            const matched = all.find(m => m.name === item.document_name);
            if (matched) {
                if (matched.document_type) {
                    box.document_type = matched.document_type;
                }
                if (matched.retention_years) {
                    box.retention_years = matched.retention_years;
                }
            }
        },

        onSectionSubDeptChange() {
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                b.sub_department_id = this.selectedSubDeptId;
                this.onBoxSubDeptChange(b);
            });
        },

        @include('archives.partials.create_modal_scripts')
    };
}
</script>