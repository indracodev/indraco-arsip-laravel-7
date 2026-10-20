                openSettings() {
                    this.tempFontSize = this.currentFontSize;
                    this.newLogoFile = null;
                    this.newLogoPreview = null;
                    this.openSettingsModal = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                },

                closeSettings() {
                    this.applyFontSizeLive(this.currentFontSize);
                    this.openSettingsModal = false;
                },

                onLogoSelected(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.newLogoFile = file;
                        this.newLogoPreview = URL.createObjectURL(file);
                    }
                },

                applyFontSizeLive(size) {
                    this.tempFontSize = size;
                    document.documentElement.style.fontSize = size;
                    document.querySelectorAll('iframe').forEach(ifr => {
                        try {
                            if (ifr.contentDocument && ifr.contentDocument.documentElement) {
                                ifr.contentDocument.documentElement.style.fontSize = size;
                            }
                            if (ifr.contentWindow) {
                                ifr.contentWindow.postMessage({ type: 'SET_FONT_SIZE', fontSize: size }, '*');
                            }
                        } catch(e) {}
                    });
                },

                applyFontSizeToIframe(ifr) {
                    try {
                        const size = this.tempFontSize || this.currentFontSize || '19px';
                        if (ifr && ifr.contentDocument && ifr.contentDocument.documentElement) {
                            ifr.contentDocument.documentElement.style.fontSize = size;
                        }
                        if (ifr && ifr.contentWindow) {
                            ifr.contentWindow.postMessage({ type: 'SET_FONT_SIZE', fontSize: size }, '*');
                        }
                    } catch(e) {}
                },

                saveSettings() {
                    this.settingsSaving = true;
                    const formData = new FormData();
                    if (this.newLogoFile) {
                        formData.append('logo', this.newLogoFile);
                    }
                    formData.append('font_size', this.tempFontSize);
                    formData.append('ping_alert_enabled', this.pingAlertEnabled ? '1' : '0');
                    formData.append('ping_alert_threshold_ms', this.pingAlertThresholdMs);

                    fetch('{{ route("master.settings.update") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        this.settingsSaving = false;
                        if (!res.ok) {
                            const errMsg = data.message || 'Gagal menyimpan pengaturan sistem.';
                            if (window.showAlertModal) {
                                window.showAlertModal(errMsg, 'Akses Dibatasi', 'danger');
                            } else {
                                console.error(errMsg);
                            }
                            return;
                        }
                        if (data.settings && data.settings.app_logo) {
                            this.appLogoUrl = data.settings.app_logo;
                        }
                        if (data.settings && typeof data.settings.ping_alert_enabled !== 'undefined') {
                            this.pingAlertEnabled = !!data.settings.ping_alert_enabled;
                        }
                        if (data.settings && data.settings.ping_alert_threshold_ms) {
                            this.pingAlertThresholdMs = parseInt(data.settings.ping_alert_threshold_ms);
                        }
                        window.dispatchEvent(new CustomEvent('lan-settings-updated', {
                            detail: {
                                pingAlertEnabled: this.pingAlertEnabled,
                                pingAlertThresholdMs: this.pingAlertThresholdMs
                            }
                        }));
                        this.currentFontSize = this.tempFontSize;
                        localStorage.setItem('app_font_size', this.currentFontSize);
                        this.applyFontSizeLive(this.currentFontSize);
                        this.openSettingsModal = false;
                        if (window.showAlertModal) {
                            window.showAlertModal(data.message || 'Pengaturan sistem berhasil disimpan.', 'Berhasil Disimpan', 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.settingsSaving = false;
                        if (window.showAlertModal) {
                            window.showAlertModal('Gagal menyimpan pengaturan. Silakan periksa koneksi Anda.', 'Gagal Menyimpan', 'danger');
                        }
                    });
                },

                async resetLogo() {
                    const ok = await window.showConfirmModal({
                        title: 'Reset Logo Sistem',
                        message: 'Kembalikan logo sistem ke default PT Indraco?',
                        type: 'warning',
                        confirmText: 'Ya, Reset Logo'
                    });
                    if (!ok) return;
                    this.settingsSaving = true;
                    fetch('{{ route("master.settings.reset_logo") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.settingsSaving = false;
                        this.appLogoUrl = data.app_logo || '{{ asset("images/logo_indraco.png") }}';
                        this.newLogoFile = null;
                        this.newLogoPreview = null;
                        alert(data.message || 'Logo berhasil dikembalikan ke default.');
                    })
                    .catch(err => {
                        console.error(err);
                        this.settingsSaving = false;
                        alert('Gagal mereset logo.');
                    });
                },

                async clearLogsAction() {
                    const ok = await window.showConfirmModal({
                        title: 'Kosongkan Log History',
                        message: 'PERINGATAN: Apakah Anda yakin ingin MENGOSONGKAN SELURUH LOG HISTORY (aktivitas sistem dan riwayat gudang)? Tindakan ini tidak dapat dibatalkan.',
                        type: 'danger',
                        confirmText: 'Ya, Kosongkan Log'
                    });
                    if (!ok) return;
                    this.maintenanceLoading = true;
                    fetch('{{ route("master.settings.clear_logs") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.maintenanceLoading = false;
                        alert(data.message || 'Log history berhasil dikosongkan.');
                        this.refreshActiveIframe();
                    })
                    .catch(err => {
                        console.error(err);
                        this.maintenanceLoading = false;
                        alert('Gagal mengosongkan log history.');
                    });
                },

                async clearBoxAllocationsAction() {
                    const ok = await window.showConfirmModal({
                        title: 'Kosongkan Penempatan Box di Rak',
                        message: 'PERINGATAN: Apakah Anda yakin ingin MENGOSONGKAN SEMUA PENEMPATAN BOX DI RAK GUDANG? Seluruh slot rak gudang akan direset menjadi kosong.',
                        type: 'danger',
                        confirmText: 'Ya, Kosongkan Rak'
                    });
                    if (!ok) return;
                    this.maintenanceLoading = true;
                    fetch('{{ route("master.settings.clear_box_allocations") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.maintenanceLoading = false;
                        alert(data.message || 'Semua box di rak gudang berhasil dikosongkan.');
                        this.refreshActiveIframe();
                    })
                    .catch(err => {
                        console.error(err);
                        this.maintenanceLoading = false;
                        alert('Gagal mengosongkan penempatan box.');
                    });
                },

                async clearArchivesAction() {
                    const ok = await window.showConfirmModal({
                        title: 'Hapus Seluruh Data Pengajuan Arsip',
                        message: 'PERINGATAN KRUSIAL: Apakah Anda yakin ingin MENGOSONGKAN SELURUH DATA PENGAJUAN BOX ARSIP? Seluruh arsip, butir dokumen, dan riwayat transaksi penarikan/pemusnahan akan DIHAPUS PERMANEN!',
                        type: 'danger',
                        confirmText: 'Lanjutkan Verifikasi'
                    });
                    if (!ok) return;
                    const confirmText = prompt('Ketik "RESET" dengan huruf besar untuk mengonfirmasi penghapusan seluruh data pengajuan arsip:');
                    if (confirmText !== 'RESET') {
                        alert('Penghapusan dibatalkan karena konfirmasi teks tidak sesuai.');
                        return;
                    }
                    this.maintenanceLoading = true;
                    fetch('{{ route("master.settings.clear_archives") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.maintenanceLoading = false;
                        alert(data.message || 'Seluruh data pengajuan box arsip berhasil dikosongkan.');
                        this.refreshActiveIframe();
                    })
                    .catch(err => {
                        console.error(err);
                        this.maintenanceLoading = false;
                        alert('Gagal mengosongkan data pengajuan arsip.');
                    });
                },

                async gitPullRestartAction() {
                    const ok = await window.showConfirmModal({
                        title: 'Git Pull & Restart Server (Opsi A)',
                        message: 'Apakah Anda yakin ingin menjalankan "Git Pull" dan me-restart server otomatis? Proses ini akan mengambil pembaruan kode Git, membersihkan cache Laravel, dan me-restart server dalam 5-10 detik.',
                        type: 'danger',
                        confirmText: 'Ya, Jalankan Pull & Restart'
                    });
                    if (!ok) return;

                    this.maintenanceLoading = true;
                    try {
                        const res = await fetch('{{ route("master.settings.git_pull_restart") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();

                        if (data.status === 'success') {
                            alert(data.message || 'Perintah berhasil dipicu.');

                            // Auto-reload browser countdown
                            let countdown = 8;
                            const timer = setInterval(() => {
                                countdown--;
                                if (countdown <= 0) {
                                    clearInterval(timer);
                                    window.location.reload();
                                }
                            }, 1000);
                        } else {
                            this.maintenanceLoading = false;
                            alert(data.message || 'Gagal memicu restart server.');
                        }
                    } catch (err) {
                        console.error(err);
                        setTimeout(() => {
                            window.location.reload();
                        }, 8000);
                    }
                },

                async viewGitPullLogAction() {
                    try {
                        const res = await fetch('{{ route("master.settings.git_pull_log") }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        alert("=== LOG RIWAYAT GIT PULL & RESTART TERAKHIR ===\n\n" + (data.log || 'Belum ada log update.'));
                    } catch (err) {
                        alert('Gagal mengambil log: ' + err.message);
                    }
                },

                refreshActiveIframe() {
                    try {
                        const activeWin = this.openWindows.find(w => w.id === this.activeWinId);
                        if (activeWin) {
                            const ifr = document.getElementById('iframe-' + activeWin.id);
                            if (ifr && ifr.contentWindow) {
                                ifr.contentWindow.location.reload();
                            }
                        }
                    } catch(e) {}
                }
            }
