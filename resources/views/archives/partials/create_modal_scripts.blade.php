        getItemDuplicateInfo(box, item, iIdx) {
            if (!box || !box.items || !item) return null;
            const docName = (item.document_name || '').trim().toLowerCase();
            const pStart = (item.period_start || '').trim();
            const pEnd = (item.period_end || '').trim();
            const notes = (item.notes || '').trim().toLowerCase();

            // Jika baris masih belum memiliki nama dokumen dan keterangan, jangan tandai duplikat
            if (!docName && !notes) return null;

            for (let k = 0; k < box.items.length; k++) {
                if (k === iIdx) continue;
                const other = box.items[k];
                if (!other) continue;
                const oDocName = (other.document_name || '').trim().toLowerCase();
                const oPStart = (other.period_start || '').trim();
                const oPEnd = (other.period_end || '').trim();
                const oNotes = (other.notes || '').trim().toLowerCase();

                // Kembar identik jika Nama Berkas, Periode Mulai, Periode Selesai, dan Keterangan sama persis
                if (docName === oDocName && pStart === oPStart && pEnd === oPEnd && notes === oNotes) {
                    return {
                        targetIndex: k,
                        targetRow: k + 1,
                        targetLabel: `Baris #${k + 1}`
                    };
                }
            }
            return null;
        },

        focusAndHighlightElement(elementId, boxIndex = null) {
            if (boxIndex !== null && this.activeBoxIndex !== boxIndex) {
                this.activeBoxIndex = boxIndex;
            }
            this.$nextTick(() => {
                setTimeout(() => {
                    const el = document.getElementById(elementId);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.classList.add('ring-4', 'ring-rose-500', 'border-rose-500', 'animate-pulse');
                        try {
                            el.focus();
                        } catch(e) {}
                        setTimeout(() => {
                            el.classList.remove('animate-pulse');
                            setTimeout(() => {
                                el.classList.remove('ring-4', 'ring-rose-500');
                            }, 3000);
                        }, 1500);
                    }
                }, 80);
            });
        },

        validateAllFormRequirements(isFinal = true) {
            // 1. Departemen (jika user Super Admin / role yang memiliki pilihan departemen)
            if (!this.selectedDeptId) {
                alert('⚠️ PERINGATAN: Departemen belum dipilih. Silakan pilih departemen terlebih dahulu.');
                return false;
            }

            // 2. Tanggal Serah Terima Fisik
            if (!this.tglPenyerahan) {
                alert('⚠️ PERINGATAN FORM WAJIB:\n\nTanggal Serah Terima Fisik ke Gudang wajib diisi sebelum menyimpan/mengajukan.');
                this.focusAndHighlightElement('tgl_penyerahan');
                return false;
            }

            // 3. Validasi Tiap Box
            if (!this.boxes || this.boxes.length === 0) {
                alert('⚠️ Minimal harus ada 1 Box Arsip yang diajukan.');
                return false;
            }

            for (let b = 0; b < this.boxes.length; b++) {
                const box = this.boxes[b];
                const boxNum = b + 1;

                // Judul Box (Wajib)
                if (!box.title || !box.title.trim()) {
                    alert(`⚠️ PERINGATAN FORM WAJIB:\n\nNama / Judul Box untuk Box #${boxNum} masih kosong.\nSilakan lengkapi judul box terlebih dahulu.`);
                    this.focusAndHighlightElement('box_title_' + b, b);
                    return false;
                }

                // Kondisi Fisik Wadah (Wajib)
                if (!box.physical_condition || !box.physical_condition.trim()) {
                    alert(`⚠️ PERINGATAN FORM WAJIB:\n\nKondisi Fisik Wadah Box untuk Box #${boxNum} wajib diisi.`);
                    this.focusAndHighlightElement('box_condition_' + b, b);
                    return false;
                }

                // Periode Dokumen Bulan (Wajib)
                if (!box.periode_bulan || parseInt(box.periode_bulan) < 1) {
                    alert(`⚠️ PERINGATAN FORM WAJIB:\n\nPeriode Dokumen (Bulan) untuk Box #${boxNum} wajib diisi minimal 1 bulan.`);
                    this.focusAndHighlightElement('periode_bulan_' + b, b);
                    return false;
                }

                // Butir Berkas Minimal 1
                if (!box.items || box.items.length === 0) {
                    alert(`⚠️ Box #${boxNum} minimal harus memiliki 1 butir berkas arsip.`);
                    this.activeBoxIndex = b;
                    return false;
                }

                // Validasi Tiap Butir Dokumen
                for (let i = 0; i < box.items.length; i++) {
                    const it = box.items[i];
                    const rowNum = i + 1;

                    // Nama Berkas
                    if (!it.document_name || !it.document_name.trim()) {
                        alert(`⚠️ PERINGATAN FORM WAJIB:\n\nNama Dokumen / Berkas Arsip pada Box #${boxNum} Baris #${rowNum} masih kosong.\nSilakan pilih dari Master Berkas atau lengkapi nama dokumen.`);
                        this.focusAndHighlightElement('item_doc_name_' + b + '_' + i, b);
                        return false;
                    }

                    // Periode Mulai
                    if (!it.period_start || !it.period_start.trim()) {
                        alert(`⚠️ PERINGATAN FORM WAJIB:\n\nPeriode Mulai pada Box #${boxNum} Baris #${rowNum} masih kosong.`);
                        this.focusAndHighlightElement('item_period_start_' + b + '_' + i, b);
                        return false;
                    }

                    // Periode Selesai
                    if (!it.period_end || !it.period_end.trim()) {
                        alert(`⚠️ PERINGATAN FORM WAJIB:\n\nPeriode Selesai pada Box #${boxNum} Baris #${rowNum} masih kosong.`);
                        this.focusAndHighlightElement('item_period_end_' + b + '_' + i, b);
                        return false;
                    }

                    // Keterangan
                    if (!it.notes || !it.notes.trim()) {
                        alert(`⚠️ PERINGATAN FORM WAJIB:\n\nKeterangan pada Box #${boxNum} Baris #${rowNum} masih kosong.\nSilakan isi rincian berkas fisik atau catatan.`);
                        this.focusAndHighlightElement('item_notes_' + b + '_' + i, b);
                        return false;
                    }

                    // Pengecekan Data Sama / Duplikat Identik ("Kembar Identik")
                    const dupInfo = this.getItemDuplicateInfo(box, it, i);
                    if (dupInfo) {
                        alert(`⚠️ DITEMUKAN DATA DUPLIKAT (KEMBAR IDENTIK):\n\nPada Box #${boxNum}, data Baris #${rowNum} kembar identik dengan ${dupInfo.targetLabel}.\n\nNama Berkas: "${it.document_name}"\nPeriode: ${it.period_start} s/d ${it.period_end}\nKeterangan: "${it.notes}"\n\nData kembar identik tidak dapat disubmit. Silakan ubah agar berbeda atau hapus baris duplikat tersebut.`);
                        this.focusAndHighlightElement('item_notes_' + b + '_' + i, b);
                        return false;
                    }
                }
            }

            // 4. Cek duplikasi nama box di dalam batch
            const seenTitles = {};
            for (let i = 0; i < this.boxes.length; i++) {
                const rawTitle = (this.boxes[i].title || '').trim().toLowerCase();
                if (seenTitles[rawTitle] !== undefined) {
                    alert(`⚠️ DUPLIKASI NAMA BOX:\n\nBox #${i + 1} memiliki nama yang sama dengan Box #${seenTitles[rawTitle] + 1} ("${this.boxes[i].title}").\nSetiap box dalam pengajuan harus memiliki nama yang berbeda.`);
                    this.focusAndHighlightElement('box_title_' + i, i);
                    return false;
                }
                seenTitles[rawTitle] = i;
            }

            // 5. Cek duplikasi dengan DB (similarityInfo) untuk final pengajuan
            if (isFinal) {
                for (let i = 0; i < this.boxes.length; i++) {
                    const b = this.boxes[i];
                    if (b.similarityInfo && b.similarityInfo.is_duplicate && !b.origin_archive_id) {
                        alert(`⚠️ PENGAJUAN DITOLAK:\n\nNama Box #${i + 1} ("${b.title}") sudah digunakan oleh arsip lain yang aktif di sistem.\nHarap ubah nama box agar berbeda.`);
                        this.focusAndHighlightElement('box_title_' + i, i);
                        return false;
                    }
                }

                // 6. Scan Formulir Wajib untuk Final Submit
                if (!this.hasScanForm && !this.hasExistingScanForm) {
                    alert('⚠️ PERHATIAN: BERKAS BELUM LENGKAP!\n\n"Scan / Berkas Formulir Pengajuan Fisik" wajib diunggah sebelum mengajukan verifikasi box arsip ke Gudang.\n\nSilakan unggah Scan Formulir Input pada Bagian 3, atau klik tombol "Simpan Sementara (Draft)" jika ingin menyimpan draft terlebih dahulu.');
                    this.focusAndHighlightElement('scan_input_form');
                    return false;
                }
            }

            return true;
        },

        submitDraft() {
            if (this.isSubmitting) return;

            if (!this.validateAllFormRequirements(false)) {
                return;
            }

            this.isSubmitting = true;
            window.formSubmitted = true;
            clearTimeout(this.autoSaveDebounceTimer);
            try {
                localStorage.removeItem('dms_draft_archive_create');
            } catch(e) {}

            const form = document.getElementById('archiveCreateForm');
            if (form) {
                // Hilangkan seluruh pembatasan HTML5 validation saat simpan draft
                form.noValidate = true;

                let actionInput = form.querySelector('input[name="submit_action"]');
                if (!actionInput) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'submit_action';
                    form.appendChild(actionInput);
                }
                actionInput.value = 'draft';
                form.submit();
            }
        },

        submitFinal(action) {
            if (this.isSubmitting) return;

            if (!this.validateAllFormRequirements(true)) {
                return;
            }

            this.isSubmitting = true;
            window.formSubmitted = true;
            clearTimeout(this.autoSaveDebounceTimer);
            try {
                localStorage.removeItem('dms_draft_archive_create');
            } catch(e) {}

            const form = document.getElementById('archiveCreateForm');
            if (form) {
                let actionInput = form.querySelector('input[name="submit_action"]');
                if (!actionInput) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'submit_action';
                    form.appendChild(actionInput);
                }
                actionInput.value = action;
                form.submit();
            }
        },

        openFormPengajuanModal() {
            this.formPengajuanData.dept_display = this.getDeptSubDeptDisplay();
            if (!this.formPengajuanData.catatan || this.formPengajuanData.catatan.includes('Rincian Masa Simpan')) {
                this.formPengajuanData.catatan = 'Kondisi fisik box standar TB 30g dalam keadaan baik.';
            }

            this.formPengajuanData.tanggal_pengajuan = this.tglPenyerahan || '{{ date('Y-m-d') }}';
            this.formPengajuanData.approval_tgl = '';
            this.populateFormRows();
            this.formModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        getBoxSubDeptName(box) {
            if (!box) return '-';
            const subId = (box.sub_department_id !== undefined && box.sub_department_id !== '' && box.sub_department_id !== null)
                ? box.sub_department_id
                : this.selectedSubDeptId;
            if (subId === 'inti') return 'Inti Dept';
            if (!subId) return '-';
            const sub = (this.subDepartments || []).find(s => String(s.id) === String(subId));
            return sub ? (sub.code || sub.name) : '-';
        },

        populateFormRows() {
            const rows = [];
            const count = Math.max(10, this.boxes.length);

            for (let i = 0; i < count; i++) {
                if (i < this.boxes.length) {
                    const b = this.boxes[i];
                    const firstDoc = (b.items && b.items.length > 0) ? b.items.find(it => it.document_name && it.document_name.trim()) : null;
                    const defaultTitle = (b.title && b.title.trim()) ? b.title.trim() : (firstDoc ? firstDoc.document_name.trim() : `Box ${i + 1}`);
                    rows.push({
                        no: i + 1,
                        title: defaultTitle,
                        sub_dept: this.getBoxSubDeptName(b),
                        period_end: this.getBoxPeriodEndFormatted(b),
                        masa_simpan: b.periode_bulan ? String(b.periode_bulan) : '1',
                        label_kardus: '',
                        label_box: ''
                    });
                } else {
                    rows.push({
                        no: i + 1,
                        title: '',
                        sub_dept: '',
                        period_end: '',
                        masa_simpan: '',
                        label_kardus: '',
                        label_box: ''
                    });
                }
            }
            this.formPengajuanRows = rows;
        },

        getDeptSubDeptDisplay() {
            let deptName = '';
            const dept = this.departments.find(d => String(d.id) === String(this.selectedDeptId));
            if (dept) {
                deptName = dept.name || dept.code || '';
            } else {
                deptName = @json(auth()->user()->department ? auth()->user()->department->name : '');
            }

            // Kumpulkan seluruh sub-departemen efektif dari box-box saat ini
            const distinctSubDepts = new Set();
            this.boxes.forEach(b => {
                const sId = (b.sub_department_id !== undefined && b.sub_department_id !== '' && b.sub_department_id !== null)
                    ? b.sub_department_id
                    : (this.selectedSubDeptId || '');
                distinctSubDepts.add(String(sId || ''));
            });

            // Jika terdeteksi multi sub-departemen pada box, tampilkan Departemen saja tanpa sub
            if (distinctSubDepts.size > 1) {
                return deptName || '-';
            }

            // Jika seluruh box seragam menggunakan 1 sub-departemen yang sama
            let subDeptName = '';
            const onlySubId = distinctSubDepts.size === 1 ? Array.from(distinctSubDepts)[0] : '';
            if (onlySubId && onlySubId !== 'inti' && this.subDepartments) {
                const sub = this.subDepartments.find(s => String(s.id) === String(onlySubId));
                if (sub) subDeptName = sub.name || sub.code || '';
            }

            if (deptName && subDeptName) {
                return `${deptName} / ${subDeptName}`;
            }
            return deptName || '-';
        },

        getBoxPeriodStartFormatted(box) {
            if (this.tglPenyerahan) {
                const parts = this.tglPenyerahan.split('-');
                if (parts.length >= 2) {
                    return `${parts[1]}-${parts[0]}`;
                }
            }
            if (!box || !box.items || box.items.length === 0) return '';
            const starts = box.items.map(it => it.period_start).filter(Boolean).sort();
            if (starts.length === 0) return '';
            const minStart = starts[0];
            const parts = minStart.split('-');
            return parts.length === 2 ? `${parts[1]}-${parts[0]}` : minStart;
        },

        getBoxPeriodEndFormatted(box) {
            if (this.tglPenyerahan && box && box.periode_bulan) {
                const parts = this.tglPenyerahan.split('-');
                if (parts.length >= 2) {
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10);
                    const monthsToAdd = parseInt(box.periode_bulan, 10) || 1;
                    const targetDate = new Date(year, month - 1 + monthsToAdd, 1);
                    const endM = String(targetDate.getMonth() + 1).padStart(2, '0');
                    const endY = targetDate.getFullYear();
                    return `${endM}-${endY}`;
                }
            }
            if (!box || !box.items || box.items.length === 0) return '';
            const ends = box.items.map(it => it.period_end).filter(Boolean).sort();
            if (ends.length === 0) return '';
            const maxEnd = ends[ends.length - 1];
            const parts = maxEnd.split('-');
            return parts.length === 2 ? `${parts[1]}-${parts[0]}` : maxEnd;
        },

        formatDateDisplay(dateStr) {
            if (!dateStr) return '';
            if (typeof dateStr !== 'string') return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
            } catch (e) {}
            return dateStr;
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '';
            if (typeof dateStr !== 'string') return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    const day = parseInt(parts[2], 10);
                    const mIdx = parseInt(parts[1], 10) - 1;
                    const year = parts[0];
                    if (!isNaN(day) && mIdx >= 0 && mIdx < 12) {
                        return `${day} ${months[mIdx]} ${year}`;
                    }
                }
            } catch (e) {}
            return dateStr;
        },

        printFormPengajuan() {
            this.populateFormRows();
            this.$nextTick(() => {
                if (window.dmsPrint) {
                    window.dmsPrint('form-pengajuan-print-document');
                } else {
                    window.print();
                }
            });
        }
