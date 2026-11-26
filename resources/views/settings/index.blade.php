{{-- Pengaturan Sistem (admin): the admin sections of the Laravel 7 "Pengaturan Sistem & Tampilan" modal
     as a page - 1. logo (+ app name), 2. LAN ping alert, 3. maintenance (backup, hapus massal with
     confirm + automatic backup, git pull & restart + its log). Font size: per user (Pengaturan Tampilan). --}}
@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
    <x-page-header title="Pengaturan Sistem (Super Admin)" subtitle="Logo aplikasi, notifikasi latensi LAN, backup dan pemeliharaan database." />

    <form method="POST" action="{{ route('master.settings.update') }}" enctype="multipart/form-data" class="stack" id="settings-form">
        @csrf
        <x-card title="1. Kustomisasi Logo Aplikasi (Header & Laporan)">
            <div class="form-grid form-grid--2">
                <div class="stack">
                    <div class="cluster">
                        <img class="settings-logo settings-logo--light" src="{{ $logoUrl }}" alt="Logo mode terang aktif" height="48">
                        <span class="badge">Logo Mode Terang</span>
                    </div>
                    <x-field label="Logo Mode Terang" for="logo" error="logo" hint="PNG / JPG (Maks. 2 MB). Dipakai di mode terang & dokumen cetak.">
                        <x-file-input name="logo" accept=".png,.jpg,.jpeg" />
                    </x-field>
                </div>
                <div class="stack">
                    <div class="cluster">
                        <img class="settings-logo {{ $darkFallsBackToLight ? 'settings-logo--light' : 'settings-logo--dark' }}" src="{{ $logoDarkUrl }}" alt="Logo mode gelap aktif" height="48">
                        <span class="badge">{{ $darkFallsBackToLight ? 'Mengikuti Logo Terang' : 'Logo Mode Gelap' }}</span>
                    </div>
                    <x-field label="Logo Mode Gelap" for="logo_dark" optional error="logo_dark" hint="PNG / JPG (Maks. 2 MB). Rekomendasi teks terang/putih dengan latar transparan.">
                        <x-file-input name="logo_dark" accept=".png,.jpg,.jpeg" />
                    </x-field>
                </div>
            </div>
            <x-field label="Nama Aplikasi" for="app_name" error="app_name">
                <x-input name="app_name" :value="old('app_name', $appName)" maxlength="100" />
            </x-field>
            <x-slot:footer>
                <x-button type="submit" form="reset-logo-form" data-confirm="Kembalikan logo terang & gelap sistem ke default PT Indraco?" data-confirm-title="Reset Logo Sistem" data-confirm-type="warning" data-confirm-btn="Ya, Reset Logo"><x-icon name="refresh" />Reset Logo Default</x-button>
            </x-slot:footer>
        </x-card>

        <x-card title="2. Notifikasi Ping & Latensi LAN (Super Admin)">
            <x-checkbox name="ping_alert_enabled" label="Popup Toast Peringatan Latensi: tampilkan peringatan jika koneksi LAN lambat atau terputus" :checked="old('ping_alert_enabled', $pingAlertEnabled)" />
            <x-field label="Ambang Batas Latensi (Threshold ms)" for="ping_alert_threshold_ms" error="ping_alert_threshold_ms" hint="Peringatan hanya muncul jika latensi LAN melebihi batas ini. Preset: 250, 500 (Standar), 1000, 2000 ms.">
                <x-input name="ping_alert_threshold_ms" type="number" min="50" max="10000" step="50" :value="old('ping_alert_threshold_ms', $pingAlertThreshold)" />
            </x-field>
        </x-card>

        <div class="cluster">
            <x-button type="submit" variant="primary">Simpan Pengaturan Permanen</x-button>
        </div>
    </form>
    <form method="POST" action="{{ route('master.settings.reset_logo') }}" id="reset-logo-form" hidden>@csrf</form>

    <x-card title="3. Pemeliharaan & Reset Database (Super Admin)">
        <div class="stack">
            <div class="maint">
                <div><strong>Backup Database</strong><p class="text-sm text-muted">Unduh snapshot SQLite murni (VACUUM INTO, 100% konsisten & siap restore) ke komputer Anda.</p></div>
                <form method="POST" action="{{ route('master.settings.backup_database') }}" data-no-lock>@csrf<x-button type="submit" variant="primary">Unduh .SQLite</x-button></form>
            </div>
            <div class="maint">
                <div><strong>Kosongkan Log History</strong><p class="text-sm text-muted">Menghapus seluruh rekaman audit trail, riwayat aktivitas pengguna, dan log histori gudang. Backup otomatis dibuat lebih dulu.</p></div>
                <form method="POST" action="{{ route('master.settings.clear_logs') }}">@csrf<input type="hidden" name="confirmed" value="1">
                    <x-button type="submit" variant="danger" data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGOSONGKAN SELURUH LOG HISTORY (aktivitas sistem dan riwayat gudang)? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Kosongkan Log History" data-confirm-btn="Ya, Kosongkan Log">Kosongkan Log</x-button>
                </form>
            </div>
            <div class="maint">
                <div><strong>Kosongkan Box di Rak Gudang</strong><p class="text-sm text-muted">Mereset semua penempatan box di slot rak gudang menjadi kosong (data berkas arsip tetap aman tersimpan). Backup otomatis dibuat lebih dulu.</p></div>
                <form method="POST" action="{{ route('master.settings.clear_box_allocations') }}">@csrf<input type="hidden" name="confirmed" value="1">
                    <x-button type="submit" variant="danger" data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGOSONGKAN SEMUA PENEMPATAN BOX DI RAK GUDANG? Seluruh slot rak gudang akan direset menjadi kosong." data-confirm-title="Kosongkan Penempatan Box di Rak" data-confirm-btn="Ya, Kosongkan Rak">Kosongkan Box Rak</x-button>
                </form>
            </div>
            <div class="maint">
                <div><strong>Kosongkan Data Pengajuan Box Arsip</strong><p class="text-sm text-muted">Menghapus seluruh pengajuan box berkas arsip, butir item, histori penarikan & pemusnahan secara permanen. Backup otomatis dibuat lebih dulu.</p></div>
                <form method="POST" action="{{ route('master.settings.clear_archives') }}" class="cluster">@csrf<input type="hidden" name="confirmed" value="1">
                    <x-field label="Ketik RESET (huruf besar) untuk mengonfirmasi" for="confirmation_text" error="confirmation_text">
                        <x-input name="confirmation_text" autocomplete="off" required pattern="RESET" />
                    </x-field>
                    <x-button type="submit" variant="danger" data-confirm="PERINGATAN KRUSIAL: Apakah Anda yakin ingin MENGOSONGKAN SELURUH DATA PENGAJUAN BOX ARSIP? Seluruh arsip, butir dokumen, dan riwayat transaksi penarikan/pemusnahan akan DIHAPUS PERMANEN!" data-confirm-title="Hapus Seluruh Data Pengajuan Arsip" data-confirm-btn="Ya, Hapus Semua Arsip">Kosongkan Semua Arsip</x-button>
                </form>
            </div>
            <div class="maint">
                <div><strong>Git Pull & Restart Server</strong><p class="text-sm text-muted">Mengambil kode terbaru dari Git (git pull), membersihkan cache Laravel, migrasi database, dan me-restart server otomatis. Backup otomatis dibuat lebih dulu.</p></div>
                <form method="POST" action="{{ route('master.settings.git_pull_restart') }}">@csrf
                    <x-button type="submit" variant="danger" :disabled="! $hasUpdateScript" data-confirm="Apakah Anda yakin ingin menjalankan 'Git Pull' dan me-restart server otomatis? Proses ini akan mengambil pembaruan kode Git, membersihkan cache Laravel, dan me-restart server dalam 5-10 detik." data-confirm-title="Git Pull & Restart Server" data-confirm-btn="Ya, Jalankan Pull & Restart"><x-icon name="refresh" />Pull & Restart</x-button>
                </form>
            </div>
            <details class="log-detail">
                <summary class="btn btn--sm">Log Riwayat Git Pull & Restart Terakhir</summary>
                <pre class="log-view">{{ $gitLog }}</pre>
            </details>
        </div>
    </x-card>
@endsection
