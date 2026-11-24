/* Propose (pemusnahan) and extend (perpanjangan) forms (Laravel 7 destructions/propose proposeCreateApp
   + extend_scripts extendCreateApp): Alpine data `retentionForm`. Search candidate boxes, chosen list
   (accordion), 500 KB file check, months (extension: max 60, or 120 for FAT/FIN/FATCLM boxes),
   print "Form Pemusnahan" / "Form Perpanjangan" in a new tab.
   Config: <script id="retention-form-config">. The server checks everything again. */
(function () {
    var MAX_KB = 500;

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('retentionForm', function () {
            var config = JSON.parse(document.getElementById('retention-form-config').textContent);
            var isExtension = config.kind === 'extension';

            return {
                archives: config.archives,
                selected: [],
                search: '',
                searchOpen: false,
                openIndex: null,
                hasFile: false,
                fileError: '',
                months: config.months || 60,
                bap: config.bap || '',

                init: function () {
                    var self = this;
                    (config.oldIds.length ? config.oldIds : config.selected).forEach(function (id) {
                        var arc = self.archives.find(function (a) { return Number(a.id) === Number(id); });
                        if (arc && !self.isSelected(arc.id)) { self.selected.push(arc); }
                    });
                    this.autoBap();
                },

                autoBap: function () {
                    if (!this.bap && this.selected.length) {
                        this.bap = 'BAP/IND/' + new Date().getFullYear() + '/' + String(this.selected[0].id).padStart(5, '0');
                    }
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
                    if (this.isSelected(arc.id)) { return; }
                    this.selected.push(arc);
                    this.search = '';
                    this.searchOpen = false;
                    this.autoBap();
                    this.clampMonths();
                },

                remove: function (index) {
                    var self = this;
                    var arc = this.selected[index];
                    var what = isExtension ? 'Perpanjangan' : 'Pemusnahan';
                    window.showConfirmModal({
                        title: 'Hapus Box ' + what,
                        message: 'Apakah Anda yakin ingin membatalkan pilihan Box "' + (arc.box_number || arc.title) + '" dari daftar ' + what.toLowerCase() + '?',
                        type: 'danger',
                        confirmText: 'Ya, Hapus'
                    }).then(function (ok) {
                        if (!ok) { return; }
                        if (self.openIndex === index) { self.openIndex = null; } else if (self.openIndex > index) { self.openIndex--; }
                        self.selected.splice(index, 1);
                    });
                },

                maxMonths: function () {
                    return config.userMax === 120 || this.selected.some(function (a) { return a.long; }) ? 120 : 60;
                },

                presets: function () {
                    return this.maxMonths() === 120 ? [6, 12, 24, 36, 60, 120] : [6, 12, 24, 36, 60];
                },

                clampMonths: function () {
                    if (this.months > this.maxMonths()) { this.months = this.maxMonths(); }
                    if (this.months !== '' && this.months < 1) { this.months = 1; }
                },

                /* Laravel 7 getExtendedPeriodEndFormatted: expiry (else period end) month + months, "MM-YYYY". */
                newEnd: function (arc) {
                    var base = (arc && arc.expiry_ym ? arc.expiry_ym : new Date().toISOString().slice(0, 7)).split('-');
                    var d = new Date(Number(base[0]), Number(base[1]) - 1 + (parseInt(this.months, 10) || 0), 1);
                    return String(d.getMonth() + 1).padStart(2, '0') + '-' + d.getFullYear();
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
                    return this.selected.length > 0 && !this.fileError && (isExtension || this.hasFile);
                },

                onSubmit: function (e) {
                    if (!this.canSubmit()) { e.preventDefault(); }
                },

                printForm: function () {
                    var params = new URLSearchParams();
                    this.selected.forEach(function (a) { params.append('archive_ids[]', a.id); });
                    if (isExtension) {
                        params.append('months', this.months || this.maxMonths());
                        params.append('reason', this.$refs.reason ? this.$refs.reason.value : '');
                    } else {
                        params.append('notes', this.$refs.notes ? this.$refs.notes.value : '');
                    }
                    window.open(config.printUrl + '?' + params.toString(), '_blank', 'noopener');
                }
            };
        });
    });
})();
