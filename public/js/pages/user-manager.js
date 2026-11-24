/* Kelola User & Hak Akses (Laravel 7 masterUsersManager): Alpine data `userManager`.
   Search / sort, add-edit modal (form POST, reopened with the old input after a validation
   error; the password is never refilled), reset password modal with presets. Config:
   <script id="user-manager-config">. */
(function () {
    var ROLES = { admin: ['Super Admin', 'badge--danger'], pic_gudang: ['PIC Gudang Arsip', 'badge--warning'], pic_dept: ['PIC Departemen', 'badge--info'] };

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('userManager', function () {
            var config = JSON.parse(document.getElementById('user-manager-config').textContent);
            /* Laravel 7 prefilled "password" as the default password of a new account. */
            var blank = { id: null, name: '', email: '', password: 'password', role: 'pic_dept', department_id: '', phone: '' };

            return {
                users: config.users,
                currentUserId: config.currentUserId,
                base: config.base,
                search: '',
                sortColumn: 'name',
                sortDirection: 'asc',
                form: Object.assign({}, blank),
                resetUser: null,
                resetValue: 'indraco123',
                showReset: false,

                init: function () {
                    var old = config.old || {};
                    if (old._form !== 'user') { return; }
                    this.form = { id: old._id ? Number(old._id) : null, name: old.name || '', email: old.email || '', password: '', role: old.role || 'pic_dept', department_id: String(old.department_id || ''), phone: old.phone || '' };
                    this.$nextTick(function () { window.dmsModal.open('user-form'); });
                },

                roleLabel: function (role) { return (ROLES[role] || [role])[0]; },
                roleTone: function (role) { return (ROLES[role] || ['', ''])[1]; },
                rows: function () {
                    var q = this.search.trim().toLowerCase();
                    var col = this.sortColumn, dir = this.sortDirection === 'asc' ? 1 : -1;
                    var has = function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; };
                    var value = function (u) {
                        var v = col === 'department' ? (u.department ? u.department.name : 'Global') : u[col];
                        return String(v == null ? '' : v).toLowerCase();
                    };
                    return this.users.filter(function (u) {
                        return !q || has(u.name) || has(u.email) || has(u.role) || has(u.phone) || (u.department && (has(u.department.name) || has(u.department.code)));
                    }).sort(function (a, b) {
                        var x = value(a), y = value(b);
                        return x < y ? -dir : (x > y ? dir : 0);
                    });
                },
                sortBy: function (col) {
                    this.sortDirection = this.sortColumn === col && this.sortDirection === 'asc' ? 'desc' : 'asc';
                    this.sortColumn = col;
                },
                sortMark: function (col) { return this.sortColumn !== col ? '↕' : (this.sortDirection === 'asc' ? '↑' : '↓'); },

                openUser: function (u) {
                    this.form = u ? { id: u.id, name: u.name, email: u.email, password: '', role: u.role, department_id: String(u.department_id || ''), phone: u.phone || '' }
                        : Object.assign({}, blank);
                    window.dmsModal.open('user-form');
                },
                openReset: function (u) {
                    this.resetUser = u;
                    this.resetValue = 'indraco123';
                    this.showReset = false;
                    window.dmsModal.open('user-reset');
                },
                randomPassword: function () {
                    var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$';
                    var bytes = new Uint32Array(8);
                    window.crypto.getRandomValues(bytes);
                    this.resetValue = Array.prototype.map.call(bytes, function (b) { return chars.charAt(b % chars.length); }).join('');
                    this.showReset = true;
                }
            };
        });
    });
})();
