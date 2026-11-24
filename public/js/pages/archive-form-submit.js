/* Box submission form, second part (merged into Alpine data `archiveForm` by archive-form.js):
   Laravel 7 checks before "Simpan Sementara (Draft)" / "Ajukan", live title similarity check,
   file size check (500 KB), paste rows from Excel, browser autosave of a new form. */
(function () {
    var STORAGE_KEY = 'dms_draft_archive_create';
    var MAX_BYTES = 500 * 1024;

    function store(action, value) {
        try {
            if (action === 'get') { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); }
            if (action === 'set') { localStorage.setItem(STORAGE_KEY, JSON.stringify(value)); } else { localStorage.removeItem(STORAGE_KEY); }
        } catch (e) { /* storage blocked */ }
        return null;
    }

    window.dmsArchiveForm = window.dmsArchiveForm || {};

    window.dmsArchiveForm.submitPart = function (config) {
        var similarityTimer = null;
        var saveTimer = null;
        var submitting = false;

        return {
            hasScanForm: !!config.hasExistingScan,
            removeScan: false,
            removeFile: false,
            fileErrors: { scan_input_form: '', file: '' },
            restored: null,

            initSubmit: function () {
                var self = this;
                if (!config.autosave) { return; }
                var draft = store('get');
                if (draft && Number(draft.userId) === Number(config.userId) && Array.isArray(draft.boxes) && draft.boxes.length) {
                    this.boxes = draft.boxes.map(function (b) { return self.newBox(b, b.items || []); });
                    this.tglPenyerahan = draft.tglPenyerahan || this.tglPenyerahan;
                    this.restored = draft.savedAt;
                }
                this.$watch('boxes', function () { self.queueAutosave(); });
                this.$watch('tglPenyerahan', function () { self.queueAutosave(); });
            },
            queueAutosave: function () {
                var self = this;
                clearTimeout(saveTimer);
                saveTimer = setTimeout(function () {
                    if (submitting || !self.boxes.some(function (b) { return b.title.trim() || b.items.some(function (it) { return it.document_name; }); })) { return; }
                    store('set', { userId: config.userId, tglPenyerahan: self.tglPenyerahan, savedAt: new Date().toLocaleTimeString('id-ID'), boxes: JSON.parse(JSON.stringify(self.boxes)) });
                }, 400);
            },
            clearSavedDraft: function () {
                window.showConfirmModal({ title: 'Kosongkan Draft Formulir?', message: 'Apakah Anda yakin ingin menghapus seluruh data isian draft formulir ini dan memulai kembali dari formulir kosong?', type: 'danger', confirmText: 'Ya, Kosongkan Formulir' })
                    .then(function (ok) {
                        if (!ok) { return; }
                        submitting = true;
                        store('remove');
                        window.location.replace(window.location.href);
                    });
            },

            onFileChange: function (event, name) {
                var file = event.target.files[0];
                this.fileErrors[name] = '';
                if (file && file.size > MAX_BYTES) {
                    this.fileErrors[name] = 'Ukuran berkas ' + Math.round(file.size / 1024) + ' KB melebihi batas maksimal 500 KB.';
                    event.target.value = '';
                    file = null;
                }
                if (name === 'scan_input_form') { this.hasScanForm = !!file || (!!config.hasExistingScan && !this.removeScan); }
            },
            toggleRemoveScan: function () {
                this.removeScan = !this.removeScan;
                this.hasScanForm = !this.removeScan || !!document.getElementById('scan_input_form').files.length;
            },

            /* Laravel 7 validateAllFormRequirements: first problem -> message + focus. */
            problem: function (isFinal) {
                if (!this.deptId) { return ['Departemen belum dipilih. Silakan pilih departemen terlebih dahulu.', 'department_id']; }
                if (!this.tglPenyerahan) { return ['Tanggal Serah Terima Fisik ke Gudang wajib diisi sebelum menyimpan/mengajukan.', 'tgl_penyerahan']; }
                var seen = {};
                for (var b = 0; b < this.boxes.length; b++) {
                    var box = this.boxes[b];
                    var n = b + 1;
                    if (!box.title.trim()) { return ['Nama / Judul Box untuk Box #' + n + ' masih kosong.\nSilakan lengkapi judul box terlebih dahulu.', 'box_title_' + b, b]; }
                    if (!box.physical_condition.trim()) { return ['Kondisi Fisik Wadah Box untuk Box #' + n + ' wajib diisi.', 'box_condition_' + b, b]; }
                    if (!(parseInt(this.boxMonths(b), 10) >= 1)) { return ['Periode Dokumen (Bulan) untuk Box #' + n + ' wajib diisi minimal 1 bulan.', 'periode_bulan_' + b, b]; }
                    for (var i = 0; i < box.items.length; i++) {
                        var it = box.items[i];
                        var at = ' pada Box #' + n + ' Baris #' + (i + 1);
                        if (!it.document_name.trim()) { return ['Nama Dokumen / Berkas Arsip' + at + ' masih kosong.\nSilakan pilih dari Master Berkas atau lengkapi nama dokumen.', 'item_name_' + b + '_' + i, b]; }
                        if (!it.period_start) { return ['Periode Mulai' + at + ' masih kosong.', 'item_start_' + b + '_' + i, b]; }
                        if (!it.period_end) { return ['Periode Selesai' + at + ' masih kosong.', 'item_end_' + b + '_' + i, b]; }
                        if (!it.notes.trim()) { return ['Keterangan' + at + ' masih kosong.\nSilakan isi rincian berkas fisik atau catatan.', 'item_notes_' + b + '_' + i, b]; }
                        var twin = this.duplicateInfo(box, it, i);
                        if (twin) { return ['Pada Box #' + n + ', data Baris #' + (i + 1) + ' kembar identik dengan ' + twin + '.\n\nData kembar identik tidak dapat disubmit. Silakan ubah agar berbeda atau hapus baris duplikat tersebut.', 'item_notes_' + b + '_' + i, b]; }
                    }
                    var key = box.title.trim().toLowerCase();
                    if (seen[key] !== undefined) { return ['Box #' + n + ' memiliki nama yang sama dengan Box #' + (seen[key] + 1) + ' ("' + box.title + '").\nSetiap box dalam pengajuan harus memiliki nama yang berbeda.', 'box_title_' + b, b]; }
                    seen[key] = b;
                    if (isFinal && box.similarity && box.similarity.is_duplicate) { return ['Nama Box #' + n + ' ("' + box.title + '") sudah digunakan oleh arsip lain yang aktif di sistem.\nHarap ubah nama box agar berbeda.', 'box_title_' + b, b]; }
                }
                if (isFinal && !this.hasScanForm) {
                    return ['"Scan / Berkas Formulir Pengajuan Fisik" wajib diunggah sebelum mengajukan verifikasi box arsip ke Gudang.\n\nSilakan unggah Scan Formulir Input pada Bagian 3, atau klik tombol "Simpan Sementara (Draft)" jika ingin menyimpan draft terlebih dahulu.', 'scan_input_form'];
                }
                return null;
            },
            /* Click on a submit button: check first (the button stays a normal submit, app.js locks it). */
            beforeSubmit: function (event, isFinal) {
                var self = this;
                var found = this.problem(isFinal);
                if (!found) {
                    submitting = true;
                    store('remove');
                    return;
                }
                event.preventDefault();
                if (found[2] !== undefined) { this.activeBox = found[2]; }
                window.showAlertModal(found[0], 'Peringatan Form Wajib').then(function () {
                    self.$nextTick(function () { var el = document.getElementById(found[1]); if (el) { el.focus(); } });
                });
            },

            checkSimilarity: function (index) {
                var self = this;
                clearTimeout(similarityTimer);
                similarityTimer = setTimeout(function () {
                    var box = self.boxes[index];
                    if (!box || !box.title.trim()) { if (box) { box.similarity = null; } return; }
                    var title = box.title.trim().toLowerCase();
                    var twin = self.boxes.findIndex(function (b, k) { return k !== index && b.title.trim().toLowerCase() === title; });
                    if (twin >= 0) {
                        box.similarity = { is_duplicate: true, message: 'Nama box sama dengan Box #' + (twin + 1) + ' di form pengajuan ini. Setiap box harus memiliki nama berbeda.', badge: 'DUPLIKAT (DITOLAK)' };
                        return;
                    }
                    fetch(config.similarityUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ title: box.title, department_id: self.deptId, exclude_archive_id: config.archiveId })
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        if (!data || !data.has_similarity) { box.similarity = null; return; }
                        var m = data.matched_archive;
                        var ref = '[' + (m.box_number || 'Box') + ']: ' + m.title;
                        box.similarity = data.is_duplicate
                            ? { is_duplicate: true, message: 'Nama box sudah digunakan oleh arsip lain ' + ref, badge: 'DUPLIKAT (DITOLAK)', diff: data.diff.unique_in_input.join(', ') }
                            : { is_duplicate: false, message: 'Kemiripan ' + data.similarity_percentage + '% dengan arsip tersimpan ' + ref, badge: data.similarity_percentage + '% Mirip', diff: data.diff.unique_in_input.join(', ') };
                    }).catch(function () { /* informative only */ });
                }, 400);
            },

            /* Ctrl+V of rows copied from Excel into the table: name, start, end, notes per row. */
            pasteRows: function (event, boxIndex) {
                var text = (event.clipboardData || window.clipboardData).getData('text');
                if (!text || (text.indexOf('\t') < 0 && text.indexOf('\n') < 0)) { return; }
                event.preventDefault();
                var month = this.currentMonth;
                var fix = function (v, fallback) { v = (v || '').replace('/', '-').substring(0, 7); return /^\d{4}-\d{2}$/.test(v) ? v : fallback; };
                var rows = text.trim().split(/\r?\n/).map(function (line) {
                    var cols = line.split('\t').map(function (c) { return c.trim().replace(/^["']|["']$/g, ''); });
                    var start = fix(cols[1], month);
                    return { document_name: cols[0] || '', period_start: start, period_end: fix(cols[2], start), notes: cols[3] || '' };
                }).filter(function (r) { return r.document_name && ['nama dokumen', 'judul dokumen', 'nama', 'no'].indexOf(r.document_name.toLowerCase()) < 0; });
                if (!rows.length) { return; }
                var box = this.boxes[boxIndex];
                var fresh = this.newBox({}, rows).items;
                box.items = box.items.length === 1 && !box.items[0].document_name ? fresh : box.items.concat(fresh);
            }
        };
    };
})();
