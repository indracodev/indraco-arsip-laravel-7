/* Penarikan form (Laravel 7 borrowings/create borrowingCreateApp): Alpine data `borrowingCreate`.
   Search boxes in the warehouse, chosen list (accordion), purpose, approval file (500 KB check),
   localStorage draft `dms_draft_borrowing_create`, print "Form Penarikan" in a new tab.
   Config: <script id="borrowing-create-config">. The server checks everything again. */
(function () {
    var DRAFT_KEY = 'dms_draft_borrowing_create';
    var MAX_KB = 500;

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('borrowingCreate', function () {
            var config = JSON.parse(document.getElementById('borrowing-create-config').textContent);
            var submitted = false;

            return {
                archives: config.archives,
                selected: [],
                search: '',
                searchOpen: false,
                openIndex: null,
                purpose: config.purpose || '',
                hasFile: false,
                fileError: '',

                init: function () {
                    var self = this;
                    var ids = config.oldIds.length ? config.oldIds : config.selected;
                    var draft = null;
                    if (!ids.length) {
                        try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { draft = null; }
                        if (draft && Number(draft.userId) === Number(config.userId)) {
                            ids = draft.archiveIds || [];
                            if (draft.purpose && !self.purpose) { self.purpose = draft.purpose; }
                        }
                    }
                    ids.forEach(function (id) {
                        var arc = self.find(id);
                        if (arc && !arc.busy && !self.isSelected(arc.id)) { self.selected.push(arc); }
                    });
                    this.$watch('selected', function () { self.saveDraft(); });
                    this.$watch('purpose', function () { self.saveDraft(); });
                },

                find: function (id) {
                    return this.archives.find(function (a) { return Number(a.id) === Number(id); });
                },

                isSelected: function (id) {
                    return this.selected.some(function (a) { return a.id === id; });
                },

                results: function () {
                    var q = this.search.trim().toLowerCase();
                    if (!q) { return this.archives.slice(0, 15); }
                    return this.archives.filter(function (a) {
                        return [a.box_number, a.title, a.period, a.dept_code, a.location].some(function (v) {
                            return v && String(v).toLowerCase().indexOf(q) !== -1;
                        });
                    });
                },

                add: function (arc) {
                    if (arc.busy || this.isSelected(arc.id)) { return; }
                    this.selected.push(arc);
                    this.search = '';
                    this.searchOpen = false;
                },

                remove: function (index) {
                    var self = this;
                    var arc = this.selected[index];
                    window.showConfirmModal({
                        title: 'Hapus Box Penarikan',
                        message: 'Apakah Anda yakin ingin membatalkan pilihan Box "' + (arc.box_number || arc.title) + '" dari daftar penarikan?',
                        type: 'danger',
                        confirmText: 'Ya, Hapus'
                    }).then(function (ok) {
                        if (!ok) { return; }
                        if (self.openIndex === index) { self.openIndex = null; } else if (self.openIndex > index) { self.openIndex--; }
                        self.selected.splice(index, 1);
                    });
                },

                onFile: function (e) {
                    var file = e.target.files[0];
                    this.fileError = '';
                    if (file && file.size > MAX_KB * 1024) {
                        this.fileError = 'Ukuran berkas melebihi batas maksimal 500 KB.';
                    } else if (file && !/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                        this.fileError = 'Format berkas harus PDF, JPG, atau PNG.';
                    }
                    this.hasFile = !!file && !this.fileError;
                },

                canSubmit: function () {
                    return this.selected.length > 0 && this.hasFile;
                },

                onSubmit: function (e) {
                    if (!this.canSubmit()) { e.preventDefault(); return; }
                    submitted = true;
                    try { localStorage.removeItem(DRAFT_KEY); } catch (err) { /* private mode */ }
                },

                saveDraft: function () {
                    if (submitted || (!this.selected.length && !this.purpose.trim())) { return; }
                    try {
                        localStorage.setItem(DRAFT_KEY, JSON.stringify({
                            userId: config.userId,
                            archiveIds: this.selected.map(function (a) { return a.id; }),
                            purpose: this.purpose
                        }));
                    } catch (err) { /* private mode */ }
                },

                printForm: function () {
                    var params = new URLSearchParams();
                    this.selected.forEach(function (a) { params.append('archive_ids[]', a.id); });
                    if (this.purpose.trim()) { params.append('purpose', this.purpose.trim()); }
                    window.open(config.printUrl + '?' + params.toString(), '_blank', 'noopener');
                }
            };
        });
    });
})();
