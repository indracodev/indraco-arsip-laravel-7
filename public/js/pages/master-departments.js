/* Master Departemen (Laravel 7 masterDepartmentsManager): Alpine data `masterDepartments`.
   Grid with search / sort / expand, add-edit modals for departments and sub-departments (normal
   form POST; reopened with the old input after a validation error), active switch (JSON) and the
   box drill-down (admin-only JSON). Config: <script id="master-departments-config">.
   window.masterFetch is shared with master-department-manage.js / master-import.js. */
(function () {
    window.masterFetch = function (url, method, body) {
        var token = document.querySelector('meta[name="csrf-token"]');
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' };
        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }
        return fetch(url, { method: method || 'GET', headers: headers, body: body || undefined }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    };

    /* First message of a failed JSON answer (Laravel validation {errors: {field: [msg]}} or {message}). */
    window.masterError = function (data, fallback) {
        var errors = data && data.errors;
        if (errors && !Array.isArray(errors)) { errors = Object.values(errors).flat(); }
        return (errors && errors.length ? errors.join('\n') : '') || (data && data.message) || fallback;
    };

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('masterDepartments', function () {
            var config = JSON.parse(document.getElementById('master-departments-config').textContent);
            var blankDept = { id: null, code: '', name: '', description: '' };
            var blankSub = { id: null, department_id: '', code: '', name: '', description: '' };

            return {
                items: config.departments,
                urls: config.urls,
                search: '',
                sortColumn: 'code',
                sortDirection: 'asc',
                expanded: {},
                deptForm: Object.assign({}, blankDept),
                subForm: Object.assign({}, blankSub),
                list: { title: '', subtitle: '', items: [], search: '', loading: false },
                detail: null,

                init: function () {
                    var old = config.old || {};
                    if (old._form === 'dept') {
                        this.deptForm = { id: old._id || null, code: old.code || '', name: old.name || '', description: old.description || '' };
                        this.$nextTick(function () { window.dmsModal.open('dept-form'); });
                    } else if (old._form === 'sub') {
                        this.subForm = { id: old._id || null, department_id: String(old.department_id || ''), code: old.code || '', name: old.name || '', description: old.description || '' };
                        this.$nextTick(function () { window.dmsModal.open('sub-form'); });
                    }
                },

                rows: function () {
                    var q = this.search.trim().toLowerCase();
                    var col = this.sortColumn, dir = this.sortDirection === 'asc' ? 1 : -1;
                    var has = function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; };
                    return this.items.filter(function (d) {
                        return !q || has(d.code) || has(d.name) || has(d.description) || d.sub_departments.some(function (s) {
                            return has(s.code) || has(s.name) || has(s.description);
                        });
                    }).sort(function (a, b) {
                        var x = a[col] == null ? '' : a[col], y = b[col] == null ? '' : b[col];
                        if (typeof x === 'number' && typeof y === 'number') { return (x - y) * dir; }
                        x = String(x).toLowerCase(); y = String(y).toLowerCase();
                        return x < y ? -dir : (x > y ? dir : 0);
                    });
                },
                sortBy: function (col) {
                    this.sortDirection = this.sortColumn === col && this.sortDirection === 'asc' ? 'desc' : 'asc';
                    this.sortColumn = col;
                },
                sortMark: function (col) { return this.sortColumn !== col ? '↕' : (this.sortDirection === 'asc' ? '↑' : '↓'); },
                toggle: function (id) { this.expanded[id] = !this.expanded[id]; },
                expandAll: function () { var self = this; this.items.forEach(function (d) { self.expanded[d.id] = true; }); },
                collapseAll: function () { this.expanded = {}; },

                openDept: function (dept) {
                    this.deptForm = dept ? { id: dept.id, code: dept.code, name: dept.name, description: dept.description || '' } : Object.assign({}, blankDept);
                    window.dmsModal.open('dept-form');
                },
                openSub: function (sub, deptId) {
                    var parent = deptId || (this.items[0] ? this.items[0].id : '');
                    this.subForm = sub ? { id: sub.id, department_id: String(parent), code: sub.code, name: sub.name, description: sub.description || '' }
                        : Object.assign({}, blankSub, { department_id: String(parent) });
                    window.dmsModal.open('sub-form');
                },
                manage: function (dept, sub) {
                    window.dispatchEvent(new CustomEvent('dept-manage', { detail: { dept: dept, sub: sub, urls: this.urls } }));
                },

                toggleActive: function (kind, item) {
                    var label = kind === 'dept' ? 'departemen' : 'sub-departemen';
                    var action = item.is_active !== false ? 'menonaktifkan' : 'mengaktifkan';
                    var url = (kind === 'dept' ? this.urls.departments : this.urls.subDepartments) + '/' + item.id + '/toggle-active';
                    window.showConfirmModal({
                        title: kind === 'dept' ? 'Ubah Status Departemen' : 'Ubah Status Sub-Departemen',
                        message: 'Yakin ingin ' + action + ' ' + label + ' "' + item.name + '"?',
                        type: 'warning', confirmText: 'Ya, Lanjutkan'
                    }).then(function (ok) {
                        if (!ok) { return; }
                        window.masterFetch(url, 'POST').then(function (res) {
                            if (res.ok && res.data.success) { item.is_active = res.data.is_active; return; }
                            window.showAlertModal(window.masterError(res.data, 'Gagal mengubah status aktif ' + label + '.'));
                        });
                    });
                },

                openArchives: function (kind, item, dept) {
                    var url = kind === 'sub' ? this.urls.apiSubDepartments + '/' + item.id + '/archives'
                        : this.urls.apiDepartments + '/' + item.id + '/archives' + (kind === 'unassigned' ? '?filter=unassigned' : '');
                    var titles = {
                        dept: ['Daftar Berkas Arsip: ' + item.name + ' (' + item.code + ')', 'Seluruh Box & Dokumen Fisik Terdaftar di Unit ' + item.name],
                        sub: ['Daftar Berkas Arsip: ' + (dept ? dept.code : '') + '-' + item.code + ' (' + item.name + ')', 'Sub-Departemen ' + item.name + ' di bawah ' + (dept ? dept.name : '')],
                        unassigned: ['Daftar Berkas Arsip Induk: ' + item.name + ' (' + item.code + ')', 'Berkas arsip tingkat departemen induk (belum dialokasikan ke sub-unit tertentu)']
                    }[kind];
                    var list = this.list;
                    Object.assign(list, { title: titles[0], subtitle: titles[1], items: [], search: '', loading: true });
                    window.dmsModal.open('archive-list');
                    window.masterFetch(url).then(function (res) {
                        list.items = res.ok ? (res.data.archives || []) : [];
                        list.loading = false;
                    }).catch(function () { list.loading = false; });
                },
                listRows: function () {
                    var q = this.list.search.trim().toLowerCase();
                    if (!q) { return this.list.items; }
                    var has = function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; };
                    return this.list.items.filter(function (a) {
                        return has(a.box_number) || has(a.title) || has(a.periode_doc) || has(a.location)
                            || (a.items || []).some(function (it) { return has(it.document_name); });
                    });
                }
            };
        });
    });
})();
