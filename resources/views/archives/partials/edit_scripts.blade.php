<script>
function archiveEditApp() {
    const isDraft = {{ $archive->status === 'draft' ? 'true' : 'false' }};
    const savedDraftBoxes = @json($draftBoxes);
    let initialBoxes = [];

    if (savedDraftBoxes && savedDraftBoxes.length > 0) {
        initialBoxes = savedDraftBoxes.map((b, bIdx) => ({
            id: b.id || (Date.now() + bIdx + Math.random()),
            title: b.title || '',
            sub_department_id: b.sub_department_id || '',
            document_type: b.document_type || '',
            physical_condition: b.physical_condition || 'Baik / Box Karton Standar TB 30g',
            periode_bulan: (b.periode_bulan || (b.periode ? String(b.periode).replace(/[^0-9]/g, '') : '') || '1'),
            follow_previous: b.follow_previous || false,
            similarityInfo: null,
            items: (b.items && b.items.length > 0) ? b.items.map((it, iIdx) => ({
                id: it.id || (Date.now() + iIdx + Math.random()),
                document_name: it.document_name || '',
                period_start: it.period_start || '',
                period_end: it.period_end || it.period_start || '',
                notes: it.notes || ''
            })) : [
                { id: Date.now(), document_name: '', period_start: '{{ date('Y-m') }}', period_end: '{{ date('Y-m') }}', notes: '' }
            ]
        }));
    } else {
        const rawItems = @json($archive->items);
        const mappedItems = (rawItems && rawItems.length > 0) ? rawItems.map((it, iIdx) => ({
            id: it.id || (Date.now() + iIdx + Math.random()),
            document_name: it.document_name,
            period_start: it.period_start || '',
            period_end: it.period_end || it.period_start || '',
            notes: it.notes || ''
        })) : [
            { id: Date.now(), document_name: '', period_start: '{{ date('Y-m') }}', period_end: '{{ date('Y-m') }}', notes: '' }
        ];

        let extractedBulan = '1';
        @if(!empty($archive->periode))
            extractedBulan = '{{ preg_match('/(\d+)/', $archive->periode, $m) ? $m[1] : 1 }}';
        @elseif(!empty($archive->periode_doc))
            extractedBulan = '{{ preg_match('/(\d+)/', $archive->periode_doc, $m) ? $m[1] : 1 }}';
        @endif

        initialBoxes = [
            {
                id: 'box_' + {{ $archive->id }},
                title: @json($archive->title ?? ''),
                sub_department_id: '{{ $archive->sub_department_id ?? '' }}',
                document_type: @json($archive->document_type ?? ''),
                physical_condition: @json($archive->physical_condition ?? 'Baik / Box Karton Standar TB 30g'),
                periode_bulan: extractedBulan || '1',
                follow_previous: false,
                similarityInfo: null,
                items: mappedItems
            }
        ];
    }

    const departmentsData = @json($departments);
    const initialDeptId = '{{ old('department_id', $archive->department_id) }}';
    const initialDept = departmentsData.find(d => String(d.id) === String(initialDeptId)) || departmentsData[0] || null;
    const initialSubDepts = (initialDept && initialDept.sub_departments) ? initialDept.sub_departments : [];
    const initialMaster = (initialDept && initialDept.master_archives) ? initialDept.master_archives : [];

    return {
        departments: departmentsData,
        selectedCompany: '{{ old('company_name', $archive->company_name ?? 'PT INDRACO GLOBAL INDONESIA') }}',
        selectedDeptId: initialDept ? initialDept.id : initialDeptId,
        selectedSubDeptId: '{{ old('sub_department_id', $archive->sub_department_id ?? '') }}',
        tglPenyerahan: '{{ old('tgl_penyerahan', $archive->tgl_penyerahan ? $archive->tgl_penyerahan->format('Y-m-d') : date('Y-m-d')) }}',
        currentMonth: '{{ date('Y-m') }}',
        subDepartments: initialSubDepts,
        masterArchives: initialMaster,
        hasExistingScanForm: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
        hasScanForm: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},

        // Workstation Multi-Box State
        activeBoxIndex: 0,
        boxes: initialBoxes,

        // Duplicate Modal State
        duplicateModalOpen: false,
        duplicateSourceIndex: 0,
        duplicateMode: 'full',
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

        init() {
            this.updateSubDepartments(false);
            this.updateMasterArchives();
            this.boxes.forEach(b => {
                if (this.selectedSubDeptId && !b.sub_department_id) {
                    b.sub_department_id = this.selectedSubDeptId;
                }
                this.onBoxSubDeptChange(b, true);
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
            const prevBox = this.boxes[this.activeBoxIndex] || (this.boxes.length > 0 ? this.boxes[this.boxes.length - 1] : null);
            const inheritedSubDept = prevBox ? prevBox.sub_department_id : (this.selectedSubDeptId || '');
            const inheritedPeriod = prevBox && prevBox.periode_bulan ? prevBox.periode_bulan : '1';

            const tempBox = { sub_department_id: inheritedSubDept };
            const available = this.getBoxMasterArchives(tempBox);
            const defaultDoc = available.length > 0 ? available[0].name : '';
            const defaultDocType = available.length > 0 ? (available[0].document_type || '') : (prevBox ? prevBox.document_type : '');

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: 'Box ' + nextNum,
                sub_department_id: inheritedSubDept,
                document_type: defaultDocType,
                physical_condition: 'Baik / Box Karton Standar TB 30g',
                periode_bulan: inheritedPeriod,
                follow_previous: true,
                similarityInfo: null,
                items: [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ]
            });
            this.activeBoxIndex = this.boxes.length - 1;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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

        openDuplicateModal(index) {
            this.duplicateSourceIndex = index;
            this.duplicateMode = 'full';
            this.duplicateModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        confirmDuplicateBox() {
            const source = this.boxes[this.duplicateSourceIndex];
            if (!source) return;

            let duplicatedItems = [];
            if (this.duplicateMode === 'full') {
                duplicatedItems = source.items.map(it => ({
                    id: Date.now() + Math.random(),
                    document_name: it.document_name,
                    period_start: it.period_start,
                    period_end: it.period_end,
                    notes: it.notes
                }));
            } else {
                const defaultDoc = this.masterArchives.length > 0 ? this.masterArchives[0].name : '';
                duplicatedItems = [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ];
            }

            this.boxes.push({
                id: Date.now() + Math.random(),
                title: source.title + ' (Salinan)',
                sub_department_id: source.sub_department_id,
                document_type: source.document_type,
                physical_condition: source.physical_condition,
                periode_bulan: source.periode_bulan,
                follow_previous: false,
                similarityInfo: null,
                items: duplicatedItems
            });

            this.activeBoxIndex = this.boxes.length - 1;
            this.duplicateModalOpen = false;
            this.checkSimilarity(this.activeBoxIndex);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async removeBox(index) {
            if (this.boxes.length <= 1) {
                if (window.showConfirmModal) {
                    await window.showConfirmModal({
                        title: 'Perhatian',
                        message: 'Pengajuan minimal harus memiliki 1 box arsip.',
                        type: 'warning',
                        confirmText: 'Mengerti'
                    });
                }
                return;
            }
            if (window.showConfirmModal) {
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Arsip',
                    message: `Apakah Anda yakin ingin menghapus Box #${index + 1}? Seluruh berkas dokumen di dalam box ini akan dihapus.`,
                    type: 'danger',
                    confirmText: 'Ya, Hapus Box'
                });
                if (!ok) return;
            }
            this.boxes.splice(index, 1);
            if (this.activeBoxIndex >= this.boxes.length) {
                this.activeBoxIndex = this.boxes.length - 1;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleTablePaste(e, boxIndex) {
            const clipboardData = (e.clipboardData || window.clipboardData);
            if (!clipboardData) return;
            const text = clipboardData.getData('text');
            if (!text || (!text.includes('\t') && !text.includes('\n'))) {
                return; // Normal paste inside a single input
            }

            e.preventDefault();
            const box = this.boxes[boxIndex];
            if (!box) return;

            const lines = text.trim().split(/\r?\n/);
            const newItems = [];

            for (let line of lines) {
                let trimmed = line.trim();
                if (!trimmed) continue;

                let cols = trimmed.split('\t');
                if (cols.length === 1 && trimmed.includes(';')) cols = trimmed.split(';');
                else if (cols.length === 1 && trimmed.includes(',')) cols = trimmed.split(',');

                cols = cols.map(c => c.trim().replace(/^["']|["']$/g, ''));

                const firstColLower = (cols[0] || '').toLowerCase();
                if (firstColLower === 'nama dokumen' || firstColLower === 'judul dokumen' || firstColLower === 'nama' || firstColLower === 'no') {
                    continue;
                }

                let docName = cols[0] || '';
                let pStart = cols[1] || this.currentMonth;
                let pEnd = cols[2] || pStart;
                let notes = cols[3] || '';

                pStart = pStart.replace('/', '-').substring(0, 7);
                pEnd = pEnd.replace('/', '-').substring(0, 7);
                if (!/^\d{4}-\d{2}$/.test(pStart)) pStart = this.currentMonth;
                if (!/^\d{4}-\d{2}$/.test(pEnd)) pEnd = pStart;

                if (docName) {
                    newItems.push({
                        id: Date.now() + Math.random(),
                        document_name: docName,
                        period_start: pStart,
                        period_end: pEnd,
                        notes: notes
                    });
                }
            }

            if (newItems.length > 0) {
                if (box.items.length === 1 && !box.items[0].document_name) {
                    box.items = newItems;
                } else {
                    box.items.push(...newItems);
                }

                if (window.showToast) {
                    window.showToast(`📋 Berhasil menempel ${newItems.length} berkas dokumen dari spreadsheet ke Box #${boxIndex + 1}.`, 'success');
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        addItem(boxIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const defaultDoc = prevItem ? (prevItem.document_name || '') : '';
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;
            const pNotes = prevItem ? prevItem.notes : '';

            box.items.push({
                id: Date.now() + Math.random(),
                document_name: defaultDoc,
                period_start: pStart,
                period_end: pEnd,
                notes: pNotes
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleEnterOnItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (itemIndex === box.items.length - 1) {
                this.addItem(boxIndex);
            }
        },

        applyPreviousBoxPreset(boxIndex) {
            if (boxIndex <= 0) return;
            const prevBox = this.boxes[boxIndex - 1];
            const currentBox = this.boxes[boxIndex];
            if (!prevBox || !currentBox) return;

            currentBox.sub_department_id = prevBox.sub_department_id;
            currentBox.document_type = prevBox.document_type;
            currentBox.physical_condition = prevBox.physical_condition;
            currentBox.periode_bulan = prevBox.periode_bulan;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        addBulkItems(boxIndex, count = 5) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const defaultDoc = prevItem ? (prevItem.document_name || '') : '';
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;

            for (let i = 0; i < count; i++) {
                box.items.push({
                    id: Date.now() + i + Math.random(),
                    document_name: defaultDoc,
                    period_start: pStart,
                    period_end: pEnd,
                    notes: ''
                });
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        duplicateItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            const source = box.items[itemIndex];
            box.items.splice(itemIndex + 1, 0, {
                id: Date.now() + Math.random(),
                document_name: source.document_name,
                period_start: source.period_start,
                period_end: source.period_end,
                notes: source.notes
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removeItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (box.items.length <= 1) {
                alert('Box minimal harus memiliki 1 berkas dokumen.');
                return;
            }
            box.items.splice(itemIndex, 1);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        promptCustomDocName(item) {
            const current = item.document_name || '';
            const custom = prompt('Ketik nama berkas khusus yang ingin ditambahkan (Super Admin):', current);
            if (custom !== null && custom.trim() !== '') {
                item.document_name = custom.trim();
            }
        },

        checkSimilarity(boxIndex) {
            clearTimeout(this.similarityTimer);
            this.similarityTimer = setTimeout(() => {
                const box = this.boxes[boxIndex];
                if (!box || !box.title || !box.title.trim()) {
                    if (box) box.similarityInfo = null;
                    return;
                }

                const currentTitle = box.title.trim().toLowerCase();

                // 1. Local In-Batch Check
                let localMatch = null;
                for (let i = 0; i < this.boxes.length; i++) {
                    if (i === boxIndex) continue;
                    const otherTitle = (this.boxes[i].title || '').trim().toLowerCase();
                    if (!otherTitle) continue;
                    if (currentTitle === otherTitle) {
                        localMatch = {
                            box_num: i + 1,
                            title: this.boxes[i].title,
                            similarity_percentage: 100
                        };
                        break;
                    }
                }

                if (localMatch) {
                    box.similarityInfo = {
                        has_similarity: true,
                        is_duplicate: true,
                        is_restored_box: false,
                        match_type: 'identical',
                        source: 'batch',
                        similarity_percentage: 100,
                        message: `Nama box sama dengan Box #${localMatch.box_num} di form pengajuan ini. Setiap box harus memiliki nama berbeda.`,
                        matched_title: localMatch.title
                    };
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    return;
                }

                // 2. Remote Database Scoped Check
                const deptId = this.selectedDeptId;
                if (!deptId) return;

                fetch('{{ route('api.archives.check_similarity') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        department_id: deptId,
                        title: box.title,
                        exclude_archive_id: {{ $archive->id }},
                        origin_archive_id: box.origin_archive_id || null
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.has_similarity) {
                        const isIdentical = data.match_type === 'identical' || data.is_duplicate;
                        const isRestored = !!box.origin_archive_id;
                        box.similarityInfo = {
                            has_similarity: true,
                            is_duplicate: isIdentical && !isRestored,
                            is_restored_box: isIdentical && isRestored,
                            match_type: data.match_type,
                            source: 'database',
                            similarity_percentage: data.similarity_percentage,
                            message: isIdentical
                                ? (isRestored 
                                    ? `Box dari penarikan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`
                                    : `Nama box sudah digunakan oleh arsip lain [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`)
                                : `Kemiripan ${data.similarity_percentage}% dengan arsip tersimpan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`,
                            matched_archive: data.matched_archive,
                            diff: data.diff
                        };
                    } else {
                        box.similarityInfo = null;
                    }
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Similarity check error:', err);
                });
            }, 400);
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
                this.masterArchives = dept.master_archives;
                this.boxes.forEach(b => {
                    this.onBoxSubDeptChange(b, true);
                });
            } else if (this.selectedDeptId) {
                fetch('{{ url('/api/departments') }}/' + this.selectedDeptId + '/master-archives')
                    .then(res => res.json())
                    .then(data => {
                        this.masterArchives = data.master_archives || [];
                        this.boxes.forEach(b => {
                            this.onBoxSubDeptChange(b, true);
                        });
                    })
                    .catch(err => {
                        this.masterArchives = [];
                    });
            } else {
                this.masterArchives = [];
            }
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
                const subSpecific = all.filter(m => String(m.sub_department_id) === String(subId));
                const generalDept = all.filter(m => !m.sub_department_id);
                return subSpecific.length > 0 ? [...subSpecific, ...generalDept] : generalDept;
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

        onBoxSubDeptChange(box, preserveExisting = false) {
            if (!box) return;
            const available = this.getBoxMasterArchives(box);
            if (available && available.length > 0) {
                if (box.items && box.items.length > 0) {
                    box.items.forEach(it => {
                        if (it.document_name && (!preserveExisting && !available.some(m => m.name === it.document_name))) {
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
                } else if (!preserveExisting && (box.sub_department_id === 'inti' || (box.sub_department_id && box.sub_department_id !== ''))) {
                    box.document_type = available[0].document_type || '';
                    if (available[0].retention_years) {
                        box.retention_years = available[0].retention_years;
                    }
                } else if (!preserveExisting) {
                    box.document_type = '';
                }
            } else if (!preserveExisting) {
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

        @include('archives.partials.edit_modal_scripts')
    };
}
</script>