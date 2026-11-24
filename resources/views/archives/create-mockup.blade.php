{{--
    STATIC MOCKUP of "Draft Pengajuan Box Baru" (Laravel 7 archives/create.blade.php + create_unit_section
    + x-file-uploader) for owner approval: same sections, fields, labels, required markers, options and
    defaults (.agents/INVENTARIS-PIC-DEPT.md F5). Not wired: no action, no submit route (data-mockup).
--}}
@extends('layouts.app')

@section('title', 'Form Pengajuan Multi-Box Arsip')

@section('content')
    @php($subOptions = ($department?->subDepartments ?? collect())->mapWithKeys(fn ($s) => [$s->id => $s->name]))
    <x-page-header title="Form Pengajuan Multi-Box Arsip (TB 30g)" subtitle="Entri kolektif banyak box fisik & dense items dokumen dengan otomasi periode cerdas.">
        <x-slot:actions>
            <span class="badge badge--outline">1 Box • 1 Berkas</span>
            <x-button disabled title="Pratinjau & cetak Form Pengajuan (Fase 4)">Form Pengajuan</x-button>
            <x-button disabled title="Impor Excel (Fase 4)"><x-icon name="upload" />Impor Excel</x-button>
            <x-link-button :href="route('archives.index')" data-shortcut="Escape">Kembali (Esc)</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <p class="notice notice--warning"><x-icon name="info" /><span><strong>MOCKUP</strong> — tampilan untuk persetujuan pemilik. Formulir belum aktif dan tidak menyimpan apa pun.</span></p>

    <form data-mockup class="stack" novalidate>
        <x-card title="1. Identitas Unit Kerja & Tanggal Serah Terima Pengajuan">
            <div class="form-grid form-grid--2">
                <x-field label="PERUSAHAAN ENTITAS" for="company_name" required>
                    <x-select name="company_name" :options="['PT INDRACO GLOBAL INDONESIA' => 'PT INDRACO GLOBAL INDONESIA']" selected="PT INDRACO GLOBAL INDONESIA" />
                </x-field>
                <x-field label="DEPARTEMEN PEMILIK ARSIP" for="department_name" required>
                    <x-input name="department_name" :value="$department ? $department->code.' - '.$department->name : 'Semua Departemen (Admin)'" readonly />
                </x-field>
                <x-field label="SUB-DEPARTEMEN" for="sub_department_id" optional>
                    <x-select name="sub_department_id" placeholder="-- Pilih Sub-Departemen --" :options="['inti' => 'Inti Departemen'] + $subOptions->all()" />
                </x-field>
                <x-field label="TGL. SERAH TERIMA FISIK KE GUDANG" for="tgl_penyerahan" required>
                    <x-input name="tgl_penyerahan" type="date" :max="now()->toDateString()" required />
                </x-field>
            </div>
        </x-card>

        <x-card title="2. Pengelolaan Multi-Box Arsip">
            <x-slot:actions>
                <x-button size="sm"><x-icon name="plus" />Tambah Box</x-button>
                <x-button size="sm">Duplikat Box...</x-button>
                <x-button size="sm" variant="ghost" class="tone--danger">Hapus Box</x-button>
            </x-slot:actions>

            <div class="stack">
                <x-card title="Box #1 — Pengaturan Identitas & Wadah Box" class="card--muted">
                    <div class="form-grid form-grid--2">
                        <x-field label="NAMA / JUDUL BOX" for="box_0_title" required hint="Cek kemiripan aktif otomatis">
                            <x-input name="boxes[0][title]" id="box_0_title" maxlength="255" required placeholder="Contoh: Voucher Kas Kecil & Pengeluaran Q1" />
                        </x-field>
                        <x-field label="SUB-DEPARTEMEN" for="box_0_sub" optional>
                            <x-select name="boxes[0][sub_department_id]" id="box_0_sub" placeholder="-- Ikuti Sub-Dept Induk / Semua --" :options="['inti' => 'Inti Departemen'] + $subOptions->all()" />
                        </x-field>
                        <x-field label="KATEGORI DOKUMEN (Terkunci)" for="box_0_type" hint="Tipe dokumen otomatis terkunci sesuai Master Berkas">
                            <x-input name="boxes[0][document_type]" id="box_0_type" readonly placeholder="Otomatis dari Master Berkas" />
                        </x-field>
                        <x-field label="KONDISI FISIK WADAH BOX" for="box_0_condition" required>
                            <x-input name="boxes[0][physical_condition]" id="box_0_condition" value="Baik / Box Karton Standar TB 30g" maxlength="100" required />
                        </x-field>
                        <x-field label="PERIODE DOKUMEN (BULAN)" for="box_0_period" required hint="Maks. 60 bulan (120 bulan untuk departemen FAT/FIN/FATCLM). Box berikutnya punya pilihan Ikuti Periode Box Sebelumnya.">
                            <div class="input-group">
                                <x-input name="periode_bulan" id="box_0_period" type="number" min="1" max="60" value="1" required />
                                <span class="input-group__addon">Bulan</span>
                            </div>
                        </x-field>
                    </div>
                </x-card>

                <div class="cluster">
                    <h3>Rincian Berkas Dokumen dalam Box #1</h3>
                    <span class="topbar__spacer"></span>
                    <x-button size="sm"><x-icon name="plus" />Tambah Berkas</x-button>
                    <x-button size="sm">Tambah 5 Baris</x-button>
                </div>
                <x-table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA DOKUMEN / BERKAS ARSIP <span class="field__required">*</span></th>
                            <th>PERIODE MULAI <span class="field__required">*</span></th>
                            <th>PERIODE SELESAI <span class="field__required">*</span></th>
                            <th>KETERANGAN <span class="field__required">*</span></th>
                            <th class="table__actions">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="NO">1</td>
                            <td data-label="NAMA DOKUMEN"><x-select name="boxes[0][items][0][document_name]" id="item_0_name" aria-label="Nama dokumen" placeholder="-- Pilih dari Master Berkas Arsip --" :options="$masterArchives->combine($masterArchives)" /></td>
                            <td data-label="PERIODE MULAI"><x-input name="boxes[0][items][0][period_start]" id="item_0_start" type="month" :value="now()->format('Y-m')" aria-label="Periode mulai" /></td>
                            <td data-label="PERIODE SELESAI"><x-input name="boxes[0][items][0][period_end]" id="item_0_end" type="month" :value="now()->format('Y-m')" aria-label="Periode selesai" /></td>
                            <td data-label="KETERANGAN"><x-textarea name="boxes[0][items][0][notes]" id="item_0_notes" rows="1" maxlength="255" placeholder="No. berkas fisik / catatan" aria-label="Keterangan" /></td>
                            <td data-label="" class="table__actions"><x-button size="sm" variant="ghost" class="tone--danger" aria-label="Hapus baris">Hapus</x-button></td>
                        </tr>
                    </tbody>
                </x-table>
            </div>
        </x-card>

        <x-card title="3. Berkas Bukti Formulir Pengajuan & Foto Fisik Box (Mencakup Semua Box)">
            <div class="form-grid form-grid--2">
                <x-field label="1. Scan / Berkas Formulir Pengajuan Fisik" for="scan_input_form" required hint="Cukup unggah 1 lembar berkas formulir serah terima bertanda tangan untuk seluruh box di atas (Maksimal 500 KB, PDF/JPG/PNG).">
                    <x-file-input name="scan_input_form" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PENGAJUAN" />
                </x-field>
                <x-field label="2. Foto Fisik Box / Lampiran Digital" for="file" hint="Foto fisik box arsip atau lampiran digital (Maksimal 500 KB, PDF/JPG/PNG). Otomatis terhubung ke seluruh box.">
                    <x-file-input name="file" accept=".pdf,.jpg,.jpeg,.png" badge="OPSIONAL" />
                </x-field>
            </div>
        </x-card>

        <x-card>
            <div class="cluster">
                <span>Ringkasan Pengajuan: <strong>1 Box</strong> dengan <strong>1 Berkas Dokumen</strong></span>
                <span class="topbar__spacer"></span>
                <x-link-button :href="route('archives.index')">Batal</x-link-button>
                <x-button type="submit" title="Simpan sementara semua box sebagai draft usulan (tanpa pembatasan wajib isi apapun)">Simpan Sementara (Draft)</x-button>
                <x-button type="submit" variant="primary" title="Simpan & Ajukan seluruh box ke PIC Gudang (Wajib menyertakan Scan Formulir)">Simpan &amp; Ajukan 1 Box Arsip</x-button>
            </div>
        </x-card>
    </form>
@endsection
