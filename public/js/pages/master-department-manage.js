/* "Kelola Unit & Master Dokumen" (Laravel 7 manage_department_modal + departments_scripts):
   Alpine data `deptManage`, opened by the `dept-manage` window event {dept, sub, urls}.
   Tab 1 PIC (assign / create / password / release), tab 2 Master Berkas (single, batch, edit,
   delete). Every change is a JSON call; the lists are reloaded afterwards. No native dialogs. */
(function () {
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('deptManage', function () {
            return {
                dept: null, sub: null, urls: {}, tab: 'pic', loading: false, busy: false,
                pics: [], available: [], masters: [], subs: [],
                assignUserId: '', showCreatePic: false, newPic: { name: '', email: '', password: '', phone: '' },
                subFilter: '', masterSearch: '', showBatch: false, showSingle: false,
                batch: { text: '', sub: '', type: 'UMUM', retention: 5 },
                single: { name: '', code: '', type: 'FAKTUR_PAJAK', sub: '', retention: 5, description: '' },
                editing: null, passwordUser: null, newPassword: '',

                open: function (detail) {
                    var subId = detail.sub && detail.sub.id ? String(detail.sub.id) : '';
                    this.dept = detail.dept; this.sub = detail.sub; this.urls = detail.urls;
                    this.tab = detail.sub ? 'master' : 'pic';
                    this.subFilter = subId || (detail.sub && detail.sub.code === 'GEN' ? 'induk_only' : '');
                    this.pics = []; this.available = []; this.masters = []; this.subs = [];
                    this.assignUserId = ''; this.showCreatePic = false; this.showBatch = false; this.showSingle = false; this.masterSearch = '';
                    this.batch = { text: '', sub: subId, type: 'UMUM', retention: this.defaultRetention() };
                    this.single = { name: '', code: '', type: 'FAKTUR_PAJAK', sub: subId, retention: this.defaultRetention(), description: '' };
                    window.dmsModal.open('dept-manage');
                    this.load();
                },
                defaultRetention: function () {
                    return (this.sub && this.sub.retention_years) || (this.dept && this.dept.retention_years) || 5;
                },
                base: function () { return this.urls.departments + '/' + this.dept.id; },
                load: function () {
                    var self = this;
                    self.loading = true;
                    return window.masterFetch(self.urls.apiDepartments + '/' + self.dept.id + '/manage-data').then(function (res) {
                        var d = res.data || {};
                        self.pics = d.pic_users || []; self.available = d.available_users || [];
                        self.masters = d.master_archives || []; self.subs = d.sub_departments || [];
                        self.loading = false;
                    }).catch(function () { self.loading = false; });
                },
                /* One JSON call: alert the answer (Laravel 7 alert()), reload the lists, run `done` on success. */
                call: function (url, method, body, fallback, done) {
                    var self = this;
                    self.busy = true;
                    return window.masterFetch(url, method, body).then(function (res) {
                        self.busy = false;
                        if (!res.ok || res.data.status === 'error') { window.showAlertModal(window.masterError(res.data, fallback)); return; }
                        if (done) { done(); }
                        window.showAlertModal(res.data.message, 'Berhasil', 'success');
                        self.load();
                    }).catch(function () { self.busy = false; window.showAlertModal('Terjadi kesalahan jaringan. Coba lagi.'); });
                },

                assignPic: function () {
                    var self = this;
                    if (!self.assignUserId) { window.showAlertModal('Pilih user terlebih dahulu.'); return; }
                    self.call(self.base() + '/pic/assign', 'POST', { user_id: self.assignUserId }, 'Gagal menugaskan PIC.', function () { self.assignUserId = ''; });
                },
                createPic: function () {
                    var self = this, p = self.newPic;
                    if (!p.name.trim() || !p.email.trim() || !p.password) { window.showAlertModal('Nama, Email, dan Password wajib diisi.'); return; }
                    self.call(self.base() + '/pic/assign', 'POST', p, 'Gagal membuat user PIC baru.', function () {
                        self.newPic = { name: '', email: '', password: '', phone: '' };
                        self.showCreatePic = false;
                    });
                },
                openPassword: function (user) {
                    this.passwordUser = user; this.newPassword = '';
                    window.dmsModal.open('pic-password');
                },
                savePassword: function () {
                    var self = this, value = self.newPassword.trim();
                    if (!value) { window.showAlertModal('Password baru tidak boleh kosong.'); return; }
                    if (value.length < 6) { window.showAlertModal('Password baru minimal 6 karakter.'); return; }
                    self.call(self.urls.users + '/' + self.passwordUser.id + '/reset-password', 'POST', { password: value }, 'Gagal mereset password.', function () {
                        window.dmsModal.close('pic-password');
                    });
                },
                removePic: function (user) {
                    var self = this;
                    window.showConfirmModal({ title: 'Lepaskan PIC', message: 'Lepaskan penugasan PIC ' + user.name + ' dari departemen ini?', type: 'warning', confirmText: 'Ya, Lepaskan' })
                        .then(function (ok) { if (ok) { self.call(self.base() + '/pic/' + user.id, 'DELETE', null, 'Gagal melepaskan PIC.'); } });
                },

                masterRows: function () {
                    var f = this.subFilter, q = this.masterSearch.trim().toLowerCase();
                    var has = function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; };
                    return this.masters.filter(function (m) {
                        if (f === 'induk_only' && m.sub_department_id) { return false; }
                        if (f && f !== 'induk_only' && String(m.sub_department_id) !== f) { return false; }
                        return !q || has(m.name) || has(m.code) || has(m.document_type) || has(m.description);
                    });
                },
                batchLines: function () {
                    return this.batch.text.split(/[\r\n]+/).map(function (l) { return l.trim(); }).filter(Boolean);
                },
                saveBatch: function () {
                    var self = this, lines = self.batchLines();
                    if (!lines.length) { window.showAlertModal('Silakan masukkan minimal 1 baris nama berkas arsip.'); return; }
                    self.call(self.base() + '/master-archives/batch', 'POST',
                        { items: lines, sub_department_id: self.batch.sub || null, document_type: self.batch.type, retention_years: self.batch.retention || null },
                        'Gagal menyimpan batch master arsip.', function () { self.batch.text = ''; self.showBatch = false; });
                },
                saveMaster: function () {
                    var self = this, s = self.single;
                    if (!s.name.trim()) { window.showAlertModal('Nama Dokumen / Berkas Arsip wajib diisi.'); return; }
                    self.call(self.base() + '/master-archives', 'POST',
                        { name: s.name, code: s.code || null, document_type: s.type, sub_department_id: s.sub || null, retention_years: s.retention || null, description: s.description || null },
                        'Gagal menambahkan Master Arsip.', function () {
                            self.single = { name: '', code: '', type: s.type, sub: s.sub, retention: s.retention, description: '' };
                            self.showSingle = false;
                        });
                },
                editMaster: function (m) {
                    this.editing = { id: m.id, name: m.name, code: m.code || '', retention_years: m.retention_years || '', document_type: m.document_type || 'UMUM', description: m.description || '', sub_department_id: m.sub_department_id };
                    window.dmsModal.open('master-edit');
                },
                updateMaster: function () {
                    var self = this, e = self.editing;
                    if (!e.name.trim()) { window.showAlertModal('Nama Dokumen / Berkas wajib diisi.'); return; }
                    self.call(self.base() + '/master-archives/' + e.id, 'PUT',
                        { name: e.name, code: e.code || null, retention_years: e.retention_years || null, document_type: e.document_type, description: e.description || null, sub_department_id: e.sub_department_id || null },
                        'Gagal memperbarui Master Arsip.', function () { window.dmsModal.close('master-edit'); });
                },
                deleteMaster: function (m) {
                    var self = this;
                    window.showConfirmModal({ title: 'Hapus Master Arsip', message: 'Hapus master arsip "' + m.name + '"?', type: 'danger', confirmText: 'Ya, Hapus' })
                        .then(function (ok) { if (ok) { self.call(self.base() + '/master-archives/' + m.id, 'DELETE', null, 'Gagal menghapus Master Arsip.'); } });
                }
            };
        });
    });
})();
