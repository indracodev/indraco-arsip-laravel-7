{{-- Preview of the parsed boxes (Laravel 7 import_modal "Pratinjau Struktur Box & Dokumen"); one root element (x-if). --}}
<div class="stack">
    <strong x-text="'Total Box Terdeteksi: ' + result.total_boxes + ' Box, Total Berkas Dokumen: ' + result.total_items + ' Berkas'"></strong>
    <div class="cluster">
        Status Pasca-Impor:
        <label class="checkbox"><input type="radio" name="import_status" value="draft" x-model="status">Draft Sementara</label>
        <label class="checkbox"><input type="radio" name="import_status" value="pending_verification" x-model="status">Verifikasi Gudang</label>
        @unless ($isPic)
            <label class="checkbox"><input type="radio" name="import_status" value="in_warehouse" x-model="status">Tersimpan di Gudang (Data Existing)</label>
        @endunless
    </div>

    <div x-show="status === 'pending_verification'">
        <x-field label="Scan / Berkas Formulir Pengajuan Fisik" for="archive_import_scan" required hint="Wajib untuk Verifikasi Gudang, sama dengan Form Pengajuan (pdf/jpg/jpeg/png, maks. 500 KB).">
            <x-file-input name="scan_input_form" id="archive_import_scan" accept=".pdf,.jpg,.jpeg,.png" badge="WAJIB UNTUK PENGAJUAN" @change="scan = $event.target.files[0] || null" />
        </x-field>
    </div>

    <strong>Pratinjau Struktur Box &amp; Dokumen: <span class="text-sm text-muted">Semua periode &amp; retensi dihitung otomatis</span></strong>
    <template x-for="(box, boxIndex) in result.boxes" :key="boxIndex">
        <section class="card">
            <header class="card__header">
                <h3 class="card__title"><span class="badge badge--outline mono" x-text="box.temp_box_id"></span> <span x-text="box.title"></span></h3>
                <div class="cluster">
                    <span class="badge" x-text="'Dept: ' + box.department_code + (box.sub_department_code ? ' / ' + box.sub_department_code : '')"></span>
                    <span class="badge badge--info" x-text="box.items.length + ' Berkas'"></span>
                </div>
            </header>
            <div class="card__body stack">
                <p class="text-sm" x-text="'Periode Arsip: ' + (box.periode_doc || '-') + ' | Rentang Tanggal: ' + box.period_start_date + ' s/d ' + box.period_end_date
                    + ' | Retensi (Kedaluwarsa): ' + box.retention_years + ' Thn (' + box.retention_expiry_date + ') | Kondisi Fisik: ' + (box.physical_condition || 'Baik')"></p>
                <x-table>
                    <thead><tr><th>No</th><th>Nama Dokumen / Berkas</th><th>Periode Dokumen</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <template x-for="(item, itemIndex) in box.items" :key="itemIndex">
                            <tr>
                                <td data-label="No" x-text="item.item_number"></td>
                                <td data-label="Nama Dokumen / Berkas" x-text="item.document_name"></td>
                                <td data-label="Periode Dokumen" x-text="item.period_text || '-'"></td>
                                <td data-label="Keterangan" x-text="item.notes || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </x-table>
            </div>
        </section>
    </template>
</div>
