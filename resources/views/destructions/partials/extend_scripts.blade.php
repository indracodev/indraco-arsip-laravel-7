@push('scripts')
<script>
function extendCreateApp() {
    return {
        selectedArchives: [],
        globalSearch: '',
        searchDropdownOpen: false,
        openAccordionIndex: null, // null = default semua menutup
        additionalMonths: {{ old('additional_months', 60) }},
        hasScanFile: false,
        archives: window.extendArchives || [],

        isFat() {
            @if(auth()->check() && (
                (auth()->user()->department && in_array(strtoupper(auth()->user()->department->code), ['FAT', 'FIN', 'FATCLM'])) ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FAT') ||
                str_contains(strtoupper(auth()->user()->name ?? ''), 'FIN')
            ))
                return true;
            @else
                return this.selectedArchives.some(a => a.department && ['FAT', 'FIN', 'FATCLM'].includes(String(a.department.code).toUpperCase()));
            @endif
        },

        getMaxMonths() {
            return this.isFat() ? 120 : 60;
        },

        getPresetMonths() {
            return this.isFat() ? [6, 12, 24, 36, 60, 120] : [6, 12, 24, 36, 60];
        },

        init() {
            const initialIds = @json($selectedArchiveIds ?? ($selectedArchiveId ? [$selectedArchiveId] : []));
            if (Array.isArray(initialIds) && initialIds.length > 0) {
                initialIds.forEach(id => {
                    const found = this.archives.find(a => a.id == id);
                    if (found && !this.isAlreadySelected(found.id)) {
                        this.selectedArchives.push(found);
                    }
                });
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        focusSearch() {
            this.searchDropdownOpen = true;
            this.$nextTick(() => {
                const el = document.getElementById('archiveSearchInput');
                if (el) el.focus();
            });
        },

        get filteredArchives() {
            if (!this.globalSearch.trim()) return this.archives.slice(0, 15);
            const q = this.globalSearch.toLowerCase();
            return this.archives.filter(a => 
                (a.box_number && a.box_number.toLowerCase().includes(q)) ||
                (a.title && a.title.toLowerCase().includes(q)) ||
                (a.period_text && a.period_text.toLowerCase().includes(q)) ||
                (a.department && a.department.code && a.department.code.toLowerCase().includes(q)) ||
                (a.rack_label && a.rack_label.toLowerCase().includes(q)) ||
                (a.short_location && a.short_location.toLowerCase().includes(q))
            );
        },

        isAlreadySelected(arcId) {
            return this.selectedArchives.some(a => a.id === arcId);
        },

        addArchiveToSelection(arc) {
            if (this.isAlreadySelected(arc.id)) return;
            this.selectedArchives.push(arc);
            this.globalSearch = '';
            this.searchDropdownOpen = false;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async removeArchive(index) {
            if (window.showConfirmModal) {
                const arc = this.selectedArchives[index];
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Perpanjangan',
                    message: `Apakah Anda yakin ingin membatalkan pilihan Box "${arc.box_number || arc.title}" dari daftar perpanjangan?`,
                    type: 'danger',
                    confirmText: 'Ya, Hapus'
                });
                if (!ok) return;
            }

            if (this.openAccordionIndex === index) {
                this.openAccordionIndex = null;
            } else if (this.openAccordionIndex > index) {
                this.openAccordionIndex--;
            }

            this.selectedArchives.splice(index, 1);
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // Accordion: buka bergantian (single open) agar tidak terlalu ke bawah
        toggleAccordion(index) {
            if (this.openAccordionIndex === index) {
                this.openAccordionIndex = null;
            } else {
                this.openAccordionIndex = index;
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // Rincian dokumen dalam box ditata rapi
        getArchiveItems(arc) {
            if (!arc) return [];
            if (arc.items && Array.isArray(arc.items) && arc.items.length > 0) {
                return arc.items.map((it, idx) => ({
                    number: it.item_number || (idx + 1),
                    name: it.document_name || '-',
                    period: it.period_text || (it.period_start ? `${it.period_start} s/d ${it.period_end || ''}` : '-'),
                    notes: it.notes || '-'
                }));
            }
            if (arc.content_description && arc.content_description.trim()) {
                const desc = arc.content_description.trim();
                const parts = desc.split(/(?=\b\d+\.\s*)/g).filter(p => p.trim());
                if (parts.length > 0) {
                    return parts.map((p, idx) => {
                        const cleaned = p.replace(/^\d+\.\s*/, '').trim();
                        let period = '-';
                        let notes = '-';
                        let name = cleaned;
                        const mPeriod = cleaned.match(/\(([^)]+)\)/);
                        if (mPeriod) {
                            period = mPeriod[1];
                            name = cleaned.replace(/\([^)]+\)/, '').trim();
                        }
                        const mNotes = name.split(/\s*-\s*(.+)/);
                        if (mNotes.length > 1) {
                            name = mNotes[0].trim();
                            notes = mNotes[1].trim();
                        }
                        return {
                            number: idx + 1,
                            name: name || cleaned,
                            period: period,
                            notes: notes
                        };
                    });
                }
            }
            return [];
        },

        get selectedBoxesCount() {
            return this.selectedArchives.length;
        },

        get canSubmit() {
            return this.selectedArchives.length > 0;
        },

        // Form Pengajuan Dokumen (Perpanjangan) State
        formModalOpen: false,
        formPengajuanData: {
            no_form: '',
            tanggal_pengajuan: '{{ date("Y-m-d") }}',
            dept_display: @json(auth()->user()->department ? auth()->user()->department->name : ''),
            pic_pemohon: @json(auth()->user()->name ?? ''),
            tipe: 'Perpanjangan',
            masa_simpan: 60,
            catatan: 'Permohonan perpanjangan masa simpan dokumen arsip.',
            approval_pemohon: @json(auth()->user()->name ?? ''),
            approval_atasan: '',
            approval_pimpinan: '',
            approval_admin_gudang: 'Admin Gudang',
            approval_tgl: ''
        },
        formPengajuanRows: [],

        openFormPengajuanModal() {
            const selected = this.selectedArchives;
            if (selected.length > 0) {
                const dept = selected[0].department;
                const deptName = dept ? (dept.name || dept.code) : (@json(auth()->user()->department ? auth()->user()->department->name : ''));

                const distinctSubDepts = new Set();
                selected.forEach(arc => {
                    const sub = arc.sub_department || arc.subDepartment;
                    const subId = arc.sub_department_id || (sub ? sub.id : '');
                    distinctSubDepts.add(String(subId || ''));
                });

                if (distinctSubDepts.size === 1) {
                    const singleSub = selected[0].sub_department || selected[0].subDepartment;
                    if (singleSub && singleSub.name) {
                        this.formPengajuanData.dept_display = `${deptName} / ${singleSub.name}`;
                    } else {
                        this.formPengajuanData.dept_display = deptName;
                    }
                } else {
                    this.formPengajuanData.dept_display = deptName;
                }
            } else if (!this.formPengajuanData.dept_display) {
                this.formPengajuanData.dept_display = @json(auth()->user()->department ? auth()->user()->department->name : '');
            }

            this.populateFormRows();
            this.formPengajuanData.tipe = 'Perpanjangan';
            this.formPengajuanData.catatan = 'Perpanjangan masa simpan (' + this.additionalMonths + ' Bulan). Alasan: ' + (document.getElementById('extension_reason')?.value || '');
            this.formPengajuanData.approval_pemohon = this.formPengajuanData.pic_pemohon;
            this.formModalOpen = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        getExtendedPeriodEndFormatted(arc) {
            if (!arc) return '-';
            const monthsToAdd = parseInt(this.additionalMonths, 10) || 0;
            let baseYear = null;
            let baseMonth = null;

            if (arc.retention_expiry_date && typeof arc.retention_expiry_date === 'string') {
                const match = arc.retention_expiry_date.match(/^(\d{4})-(\d{1,2})/);
                if (match) {
                    baseYear = parseInt(match[1], 10);
                    baseMonth = parseInt(match[2], 10);
                }
            }

            if ((!baseYear || !baseMonth) && arc.period_end_date && typeof arc.period_end_date === 'string') {
                const match = arc.period_end_date.match(/^(\d{4})-(\d{1,2})/);
                if (match) {
                    baseYear = parseInt(match[1], 10);
                    baseMonth = parseInt(match[2], 10);
                }
            }

            if ((!baseYear || !baseMonth) && arc.formatted_expiry_date && typeof arc.formatted_expiry_date === 'string') {
                const mYMatch = arc.formatted_expiry_date.match(/^([A-Za-z]+)\s+(\d{4})$/);
                if (mYMatch) {
                    const monthNames = {
                        jan: 1, feb: 2, mar: 3, apr: 4, may: 5, mei: 5, jun: 6,
                        jul: 7, aug: 8, agu: 8, ags: 8, sep: 9, oct: 10, okt: 10, nov: 11, dec: 12, des: 12
                    };
                    const mKey = mYMatch[1].toLowerCase().slice(0, 3);
                    if (monthNames[mKey]) {
                        baseMonth = monthNames[mKey];
                        baseYear = parseInt(mYMatch[2], 10);
                    }
                }
            }

            if (!baseYear || !baseMonth) {
                const now = new Date();
                baseYear = now.getFullYear();
                baseMonth = now.getMonth() + 1;
            }

            const targetDate = new Date(baseYear, (baseMonth - 1) + monthsToAdd, 1);
            const endM = String(targetDate.getMonth() + 1).padStart(2, '0');
            const endY = targetDate.getFullYear();
            return `${endM}-${endY}`;
        },

        populateFormRows() {
            const rows = [];
            const padTo = 10;

            this.selectedArchives.forEach((arc, idx) => {
                const rackLoc = arc.rack_label || (arc.short_location && arc.short_location !== 'Belum Ada Lokasi' ? arc.short_location : '') || '';
                const boxLabel = rackLoc || arc.box_number || arc.archive_code || '';

                const subDeptName = arc.sub_department ? (arc.sub_department.code || arc.sub_department.name) : (arc.sub_department_id ? 'Inti Dept' : '-');

                rows.push({
                    no: idx + 1,
                    title: arc.title || ('Box ' + (arc.box_number || (idx + 1))),
                    sub_dept: subDeptName,
                    masa_simpan: this.additionalMonths ? (this.additionalMonths + ' Bln') : (this.getMaxMonths() + ' Bln'),
                    period_end: this.getExtendedPeriodEndFormatted(arc),
                    label_kardus: boxLabel,
                    label_box: boxLabel
                });
            });

            const currentCount = rows.length;
            for (let i = currentCount + 1; i <= Math.max(padTo, currentCount); i++) {
                rows.push({
                    no: i,
                    title: '',
                    sub_dept: '',
                    masa_simpan: '',
                    period_end: '',
                    label_box: '',
                    label_kardus: ''
                });
            }

            this.formPengajuanRows = rows;
        },

        printFormPengajuan() {
            if (window.dmsPrint) {
                window.dmsPrint('form-pengajuan-print-document');
            } else {
                window.print();
            }
        },

        formatDateDisplay(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
            } catch(e) {
                return dateStr;
            }
        },

        getDeptSubDeptDisplay() {
            return this.formPengajuanData.dept_display || '';
        }
    };
}
</script>
@endpush
