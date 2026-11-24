/* Import Excel Data Master (Laravel 7 masterDataImport): Alpine data `masterImport`.
   Preview and commit post the same file; the server parses it again each time. After a
   successful commit the page reloads (new departments in the grid). URLs: data-preview-url /
   data-commit-url on the component root. Uses window.masterFetch (master-departments.js). */
(function () {
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('masterImport', function () {
            return {
                file: null, busy: false, result: null, errors: [], warnings: [], successMessage: '',

                init: function () {
                    var self = this;
                    /* Laravel 7 reset the dialog every time it was opened; here when it closes. */
                    this.$el.querySelector('.modal').addEventListener('modal-closed', function () { if (!self.busy) { self.reset(); } });
                },
                reset: function () {
                    this.file = null; this.busy = false; this.result = null; this.errors = []; this.warnings = []; this.successMessage = '';
                    if (this.$refs.file) { this.$refs.file.value = ''; }
                },
                pick: function (e) {
                    this.file = e.target.files[0] || null;
                    this.result = null; this.errors = []; this.warnings = []; this.successMessage = '';
                },
                canCommit: function () {
                    var s = this.result && this.result.success && this.result.summary;
                    return !!s && !this.successMessage && (s.departemen_baru + s.sub_departemen_baru + s.dokumen_baru) > 0;
                },
                send: function (commit) {
                    var self = this;
                    if (!self.file) { return; }
                    self.busy = commit ? 'commit' : 'preview';
                    self.errors = []; self.warnings = [];
                    var form = new FormData();
                    form.append('file', self.file);
                    window.masterFetch(self.$el.getAttribute(commit ? 'data-commit-url' : 'data-preview-url'), 'POST', form).then(function (res) {
                        var json = res.data || {};
                        var errs = Array.isArray(json.errors) ? json.errors : Object.values(json.errors || {}).flat();
                        self.errors = errs.length ? errs : (res.ok ? [] : [json.message || 'Gagal memproses file.']);
                        self.warnings = json.warnings || [];
                        if (json.summary) { self.result = json; }
                        if (commit && res.ok && json.success) {
                            self.successMessage = json.message || 'Import berhasil.';
                            if (/^Import berhasil/.test(self.successMessage)) { setTimeout(function () { window.location.reload(); }, 1200); }
                        }
                    }).catch(function (err) {
                        self.errors = ['Terjadi kesalahan koneksi saat memproses file: ' + err.message];
                    }).then(function () { self.busy = false; });
                }
            };
        });
    });
})();
