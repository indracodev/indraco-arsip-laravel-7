/* Box submission form (Laravel 7 archives/create + edit, multi-box): Alpine data `archiveForm`.
   Boxes, document lines, period rules, Master Berkas per sub-department. Validation, submit,
   similarity check and autosave: archive-form-submit.js. Config: <script id="archive-form-config">. */
(function () {
    var DEFAULT_CONDITION = 'Baik / Box Karton Standar TB 30g';
    var LONG_PERIOD_CODES = ['FAT', 'FIN', 'FATCLM'];
    var uid = 0;

    function nextId() { uid += 1; return uid; }

    function copyItem(from) {
        /* custom: admin row typing a name outside Master Berkas; picked: last chosen name (kept when switching to typing). */
        return { id: nextId(), document_name: from.document_name || '', period_start: from.period_start || '', period_end: from.period_end || '', notes: from.notes || '', custom: false, picked: from.document_name || '' };
    }

    window.dmsArchiveForm = window.dmsArchiveForm || {};

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('archiveForm', function () {
            var config = JSON.parse(document.getElementById('archive-form-config').textContent);
            var month = config.currentMonth;
            var blankItem = function () { return copyItem({ period_start: month, period_end: month }); };

            var core = {
                config: config,
                departments: config.departments,
                deptId: String(config.deptId || ''),
                subDeptId: config.subDeptId || '',
                tglPenyerahan: config.tglPenyerahan,
                currentMonth: month,
                activeBox: 0,
                boxes: [],
                duplicate: { source: 0, mode: 'full', selected: [] },

                init: function () {
                    var self = this;
                    var start = config.boxes || [{ items: [1, 2, 3].map(blankItem) }];
                    this.boxes = start.map(function (b) { return self.newBox(b, b.items || []); });
                    this.boxes.forEach(function (box) {
                        if (self.subDeptId && !box.sub_department_id) { box.sub_department_id = self.subDeptId; }
                        self.onBoxSubDeptChange(box, true);
                    });
                    this.initSubmit();
                },

                newBox: function (from, items) {
                    return {
                        id: nextId(), title: from.title || '', sub_department_id: from.sub_department_id ? String(from.sub_department_id) : '',
                        document_type: from.document_type || '', physical_condition: from.physical_condition || DEFAULT_CONDITION,
                        periode_bulan: String(from.periode_bulan || '1'), follow_previous: !!from.follow_previous, similarity: null,
                        items: items.length ? items.map(copyItem) : [blankItem()]
                    };
                },

                dept: function () {
                    var id = this.deptId;
                    return this.departments.find(function (d) { return String(d.id) === id; }) || null;
                },
                subDepartments: function () { var d = this.dept(); return d ? d.sub_departments : []; },
                maxMonths: function () { var d = this.dept(); return d && LONG_PERIOD_CODES.indexOf(String(d.code).toUpperCase()) >= 0 ? 120 : 60; },
                presets: function () { return this.maxMonths() === 120 ? [1, 3, 4, 6, 12, 15, 24, 60, 120] : [1, 3, 4, 6, 12, 15, 24, 60]; },
                presetLabel: function (p) { return p + ' Bln' + (p === 120 ? ' (10 Thn)' : (p === 60 ? ' (5 Thn)' : '')); },
                totalItems: function () { return this.boxes.reduce(function (sum, b) { return sum + b.items.length; }, 0); },

                addBox: function () {
                    var prev = this.boxes[this.activeBox] || this.boxes[this.boxes.length - 1];
                    var box = this.newBox({ sub_department_id: prev ? prev.sub_department_id : this.subDeptId, periode_bulan: prev ? prev.periode_bulan : '1', follow_previous: true }, []);
                    var available = this.masterFor(box);
                    box.document_type = box.sub_department_id && available.length ? (available[0].document_type || '') : '';
                    this.boxes.push(box);
                    this.activeBox = this.boxes.length - 1;
                },
                removeBox: function (index) {
                    var self = this;
                    if (this.boxes.length <= 1) { window.showAlertModal('Pengajuan minimal harus memiliki 1 box arsip.', 'Perhatian'); return; }
                    window.showConfirmModal({ title: 'Hapus Box Arsip', message: 'Apakah Anda yakin ingin menghapus Box #' + (index + 1) + '? Seluruh butir berkas di dalam box ini akan dihapus.', type: 'danger', confirmText: 'Ya, Hapus Box' })
                        .then(function (ok) {
                            if (!ok) { return; }
                            self.boxes.splice(index, 1);
                            self.activeBox = Math.min(self.activeBox, self.boxes.length - 1);
                        });
                },
                openDuplicate: function (index) {
                    this.duplicate = { source: index, mode: 'full', selected: this.boxes[index].items.map(function (it) { return it.id; }) };
                    window.dmsModal.open('duplicate-box-modal');
                },
                confirmDuplicate: function () {
                    var d = this.duplicate, source = this.boxes[d.source];
                    var items = d.mode === 'metadata_only' ? [] : source.items.filter(function (it) { return d.mode === 'full' || d.selected.indexOf(it.id) >= 0; });
                    if (!items.length) {
                        var available = this.masterFor(source);
                        items = [copyItem({ document_name: available.length ? available[0].name : '', period_start: month, period_end: month })];
                    }
                    this.boxes.push(this.newBox({
                        title: source.title ? source.title + ' (Salinan)' : '', sub_department_id: source.sub_department_id, document_type: source.document_type,
                        periode_bulan: source.periode_bulan, follow_previous: true, physical_condition: source.physical_condition
                    }, items));
                    this.activeBox = this.boxes.length - 1;
                    window.dmsModal.close('duplicate-box-modal');
                    this.checkSimilarity(this.activeBox);
                },
                applyPreviousPreset: function (index) {
                    var prev = this.boxes[index - 1], box = this.boxes[index];
                    ['sub_department_id', 'document_type', 'physical_condition', 'periode_bulan'].forEach(function (key) { box[key] = prev[key]; });
                },

                /* Document lines: a new line copies the last one (Laravel 7). */
                addItems: function (boxIndex, count) {
                    var box = this.boxes[boxIndex];
                    var last = box.items[box.items.length - 1] || { period_start: month, period_end: month };
                    for (var i = 0; i < count; i++) { box.items.push(copyItem(last)); }
                },
                duplicateItem: function (boxIndex, itemIndex) {
                    var box = this.boxes[boxIndex];
                    box.items.splice(itemIndex + 1, 0, copyItem(box.items[itemIndex]));
                },
                removeItem: function (boxIndex, itemIndex) {
                    var box = this.boxes[boxIndex];
                    if (box.items.length <= 1) { window.showAlertModal('Box minimal harus memiliki 1 butir dokumen.', 'Perhatian'); return; }
                    box.items.splice(itemIndex, 1);
                },
                validatePeriod: function (it) {
                    if (it.period_end && it.period_end > month) { it.period_end = month; }
                    if (it.period_start && it.period_start > month) { it.period_start = month; }
                    if (it.period_start && it.period_end && it.period_start > it.period_end) { it.period_start = it.period_end; }
                },
                duplicateInfo: function (box, it, index) {
                    var key = function (x) { return [(x.document_name || '').trim().toLowerCase(), (x.period_start || '').trim(), (x.period_end || '').trim(), (x.notes || '').trim().toLowerCase()].join('||'); };
                    if (!(it.document_name || '').trim() && !(it.notes || '').trim()) { return null; }
                    for (var k = 0; k < box.items.length; k++) {
                        if (k !== index && key(box.items[k]) === key(it)) { return 'Baris #' + (k + 1); }
                    }
                    return null;
                },

                /* Period: number of months, or the previous box's value ("Ikuti Periode Box Sebelumnya"). */
                clampMonths: function (box) {
                    if (box.periode_bulan > this.maxMonths()) { box.periode_bulan = String(this.maxMonths()); }
                    if (box.periode_bulan !== '' && box.periode_bulan < 1) { box.periode_bulan = '1'; }
                },
                boxMonths: function (index) {
                    var box = this.boxes[index];
                    return index > 0 && box.follow_previous ? this.boxMonths(index - 1) : (box.periode_bulan || '1');
                },
                finalPeriod: function (index) { return this.boxMonths(index) + ' Bulan'; },
                followPrevious: function (index) {
                    if (this.boxes[index].follow_previous) { this.boxes[index].periode_bulan = this.boxMonths(index - 1); }
                },
                calculatedRange: function (box) {
                    var starts = box.items.map(function (it) { return it.period_start; }).filter(Boolean).sort();
                    var ends = box.items.map(function (it) { return it.period_end; }).filter(Boolean).sort();
                    if (!starts.length || !ends.length) { return month; }
                    return starts[0] === ends[ends.length - 1] ? starts[0] : starts[0] + ' s/d ' + ends[ends.length - 1];
                },

                /* Master Berkas of the box's sub-department (+ general ones); "inti" = department only. */
                masterFor: function (box) {
                    var all = this.dept() ? this.dept().master_archives : [];
                    var sub = box.sub_department_id || this.subDeptId;
                    var general = all.filter(function (m) { return !m.sub_department_id; });
                    if (sub === 'inti') { return general; }
                    if (!sub) { return all; }
                    var specific = all.filter(function (m) { return String(m.sub_department_id) === String(sub); });
                    return specific.concat(general);
                },
                masterLabel: function (m) { return (m.code ? m.code + ' - ' : '') + m.name + (m.document_type ? ' [' + m.document_type + ']' : ''); },
                isCustomName: function (box, it) { return !!it.document_name && !this.masterFor(box).some(function (m) { return m.name === it.document_name; }); },
                onMasterDocChange: function (box, it, event) {
                    if (it.document_name === '__custom__') {
                        /* Admin "type a name" option (Laravel 7 prompt()): the row becomes a text input. */
                        it.document_name = it.picked;
                        it.custom = true;
                        this.$nextTick(function () { var input = event.target.parentNode.querySelector('input[type="text"]'); if (input) { input.focus(); } });
                        return;
                    }
                    it.picked = it.document_name;
                    var match = this.masterFor(box).find(function (m) { return m.name === it.document_name; });
                    if (match && match.document_type) { box.document_type = match.document_type; }
                },
                onBoxSubDeptChange: function (box, preserve) {
                    var self = this;
                    var available = this.masterFor(box);
                    if (!preserve) {
                        box.items.forEach(function (it) {
                            if (it.document_name && !available.some(function (m) { return m.name === it.document_name; })) { it.document_name = ''; }
                        });
                    }
                    var first = box.items.find(function (it) { return it.document_name; });
                    if (first) { self.onMasterDocChange(box, first); } else if (!preserve) {
                        box.document_type = box.sub_department_id && available.length ? (available[0].document_type || '') : '';
                    }
                },
                onHeaderSubDeptChange: function () {
                    var self = this;
                    this.boxes.forEach(function (box) { box.sub_department_id = self.subDeptId; self.onBoxSubDeptChange(box, false); });
                },
                onDeptChange: function () {
                    this.subDeptId = '';
                    this.onHeaderSubDeptChange();
                }
            };

            return Object.assign(core, window.dmsArchiveForm.submitPart(config));
        });
    });
})();
