/* "Impor Multi-Box (Excel)" modal (Laravel 7 catalog_modal_handlers import* + import_modal):
   Alpine data `archiveImport`. Preview, commit and "Lanjutkan ke Form Pengajuan" each send the
   file again (the server never trusts a preview sent back). Config: <script id="archive-import-config">. */
(function () {
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('archiveImport', function () {
            var config = JSON.parse(document.getElementById('archive-import-config').textContent);
            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            function send(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (json) {
                        json.ok = res.ok && json.success !== false;
                        if (!json.ok && !(json.errors && json.errors.length)) {
                            json.errors = [json.message || (res.status === 403 ? 'Akses Ditolak.' : 'Gagal memproses file template spreadsheet.')];
                        }
                        return json;
                    });
                });
            }

            return {
                file: null,
                scan: null,
                result: null,
                status: 'draft',
                errors: [],
                warnings: [],
                busy: '',
                downloaded: '',

                init: function () {
                    if (config.openOnLoad) {
                        var opener = document.querySelector('[data-modal-open="archive-import-modal"]');
                        if (opener) { opener.click(); }
                    }
                },

                selectFile: function (event) {
                    this.file = event.target.files[0] || null;
                    this.result = null;
                    this.errors = [];
                    this.warnings = [];
                },

                ready: function () {
                    return !!(this.result && this.result.boxes.length);
                },

                formData: function (extra) {
                    var data = new FormData();
                    data.append('file', this.file);
                    Object.keys(extra || {}).forEach(function (key) { if (extra[key]) { data.append(key, extra[key]); } });
                    return data;
                },

                run: function (kind, url, data) {
                    var self = this;
                    this.busy = kind;
                    this.errors = [];
                    return send(url, data).catch(function (err) {
                        return { ok: false, errors: ['Terjadi kesalahan koneksi saat membaca file Excel: ' + err.message] };
                    }).then(function (json) {
                        if (!json.ok) {
                            self.errors = json.errors;
                            self.busy = '';
                        }
                        return json;
                    });
                },

                preview: function () {
                    var self = this;
                    if (!this.file) {
                        window.showAlertModal('Pilih file Excel (.xlsx) atau CSV terlebih dahulu.');
                        return;
                    }
                    this.result = null;
                    this.warnings = [];
                    this.run('preview', config.previewUrl, this.formData()).then(function (json) {
                        self.busy = '';
                        if (json.ok) {
                            self.result = json;
                            self.warnings = json.warnings || [];
                        } else if (json.boxes) {
                            self.warnings = json.warnings || [];
                        }
                    });
                },

                loadToForm: function () {
                    this.run('form', config.loadToFormUrl, this.formData()).then(function (json) {
                        if (json.ok) { window.location.href = json.redirect_url; }
                    });
                },

                commit: function () {
                    var self = this;
                    var pending = this.status === 'pending_verification';
                    var existing = this.status === 'in_warehouse';
                    if (pending && !this.scan) {
                        window.showAlertModal('Scan Formulir Input wajib diunggah sebelum mengajukan verifikasi box arsip.', 'Data Belum Lengkap');
                        return;
                    }
                    var title = existing ? 'Konfirmasi Impor Langsung ke Gudang' : (pending ? 'Konfirmasi Impor & Ajukan ke Gudang' : 'Konfirmasi Impor sebagai Draft');
                    var statusLabel = existing ? 'Tersimpan di Gudang' : (pending ? 'Verifikasi Gudang' : 'Draft Sementara');
                    var confirmText = existing ? 'Ya, Simpan ke Gudang' : (pending ? 'Ya, Simpan & Ajukan' : 'Ya, Simpan Draft');
                    window.showConfirmModal({
                        title: title,
                        message: this.result.total_boxes + ' Box, ' + this.result.total_items + ' Berkas Dokumen akan disimpan dengan status "' + statusLabel + '". Lanjutkan?',
                        type: 'info',
                        confirmText: confirmText
                    }).then(function (ok) {
                        if (!ok) { return; }
                        self.run('commit', config.commitUrl, self.formData({ status: self.status, scan_input_form: pending ? self.scan : null })).then(function (json) {
                            if (json.ok) { window.location.href = json.redirect_url; }
                        });
                    });
                }
            };
        });
    });
})();
