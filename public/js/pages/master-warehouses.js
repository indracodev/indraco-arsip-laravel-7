/* Master Gudang & Rak (Laravel 7 masterWarehousesManager): Alpine data `masterWarehouses`.
   Search, add/edit modals (normal form POST, reopened with the old input after a validation
   error) and the admin active switch (JSON, window.masterFetch from master-departments.js).
   Config: <script id="master-warehouses-config">. */
(function () {
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('masterWarehouses', function () {
            var config = JSON.parse(document.getElementById('master-warehouses-config').textContent);
            var blankWh = { id: null, code: '', name: '', address: '' };

            return {
                items: config.warehouses,
                base: config.base,
                search: '',
                toggling: null,
                whForm: Object.assign({}, blankWh),
                locForm: {},

                init: function () {
                    var old = config.old || {};
                    this.locForm = this.blankLocation();
                    if (old._form === 'warehouse') {
                        this.whForm = { id: old._id || null, code: old.code || '', name: old.name || '', address: old.address || '' };
                        this.$nextTick(function () { window.dmsModal.open('warehouse-form'); });
                    } else if (old._form === 'location') {
                        this.locForm = { id: old._id || null, warehouse_id: String(old.warehouse_id || ''), rack_code: old.rack_code || '', shelf_code: old.shelf_code || '', box_capacity: old.box_capacity || 100 };
                        this.$nextTick(function () { window.dmsModal.open('location-form'); });
                    }
                },
                blankLocation: function () {
                    return { id: null, warehouse_id: this.items[0] ? String(this.items[0].id) : '', rack_code: '', shelf_code: '', box_capacity: 100 };
                },

                rows: function () {
                    var q = this.search.trim().toLowerCase();
                    if (!q) { return this.items; }
                    var has = function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; };
                    return this.items.filter(function (wh) {
                        return has(wh.code) || has(wh.name) || has(wh.address) || wh.locations.some(function (loc) {
                            return has(loc.rack_code) || has(loc.shelf_code) || has(loc.full_location);
                        });
                    });
                },
                capacityTone: function (loc) {
                    var pct = loc.box_capacity ? (loc.current_box_count || 0) / loc.box_capacity : 0;
                    return pct >= 1 ? 'badge--danger' : (pct >= 0.8 ? 'badge--warning' : 'badge--success');
                },

                openWarehouse: function (wh) {
                    this.whForm = wh ? { id: wh.id, code: wh.code, name: wh.raw_name, address: wh.address || '' } : Object.assign({}, blankWh);
                    window.dmsModal.open('warehouse-form');
                },
                openLocation: function (loc) {
                    this.locForm = loc ? { id: loc.id, warehouse_id: String(loc.warehouse_id || ''), rack_code: loc.rack_code, shelf_code: loc.shelf_code, box_capacity: loc.box_capacity }
                        : this.blankLocation();
                    window.dmsModal.open('location-form');
                },

                toggleActive: function (wh) {
                    var self = this;
                    var next = wh.is_active === false;
                    window.showConfirmModal({
                        title: 'Ubah Status Gudang',
                        message: 'Ubah status gudang ' + wh.name + ' menjadi ' + (next ? 'AKTIF' : 'NON-AKTIF') + '?',
                        type: 'warning', confirmText: 'Ya, Lanjutkan'
                    }).then(function (ok) {
                        if (!ok) { return; }
                        self.toggling = wh.id;
                        window.masterFetch(self.base + '/' + wh.id + '/toggle-active', 'POST', { is_active: next }).then(function (res) {
                            self.toggling = null;
                            if (res.ok && res.data.success) { wh.is_active = res.data.is_active; return; }
                            window.showAlertModal(window.masterError(res.data, 'Gagal mengubah status aktif gudang.'));
                        }).catch(function () {
                            self.toggling = null;
                            window.showAlertModal('Terjadi kesalahan jaringan saat mengubah status gudang.');
                        });
                    });
                }
            };
        });
    });
})();
