{{--
    "Impor Multi-Box & Berkas Dokumen (Excel / CSV)" (Laravel 7 archives/partials/modals/import_modal).
    Opened by [data-modal-open="archive-import-modal"] or ?open_import=1. Alpine: public/js/pages/archive-import.js.
    Status "Tersimpan di Gudang (Data Existing)" / "Simpan Langsung ke Gudang" (admin) stay disabled
    until the warehouse check-in path exists (.agents/context/modules/archive-import.md).
--}}
@php
    $isPic = auth()->user()->role === \App\Models\User::ROLE_PIC_DEPT;
    $importConfig = [
        'previewUrl' => route('archives.import.preview'),
        'commitUrl' => route('archives.import.commit'),
        'loadToFormUrl' => route('archives.import.load_to_form'),
        'openOnLoad' => request()->boolean('open_import'),
    ];
@endphp
<x-modal id="archive-import-modal" title="Impor Multi-Box & Berkas Dokumen (Excel / CSV)" x-data="archiveImport">
    <script type="application/json" id="archive-import-config">@json($importConfig)</script>
    <div class="stack">
        <p class="text-sm text-muted">Unggah ratusan berkas dalam banyak box sekaligus dengan otomasi perhitungan periode &amp; retensi.</p>

        <div class="notice">
            <div class="notice__body stack">
                <strong>Petunjuk Pengisian Multi-Box:</strong>
                <p class="text-sm">
                    @if ($isPic)
                        Identitas box (JUDUL_BOX, Dept) cukup diisi 1 kali pada baris pertama box. Baris dokumen berikutnya cukup isi Nama Dokumen, Periode, dan Keterangan. Pengisian JUDUL_BOX baru di baris bawahnya otomatis menjadi pemisah box baru. Nomor urut berkas otomatis dari atas ke bawah. Kolom MASA_SIMPAN_BULAN diisi angka bulan murni (contoh: 60 untuk 60 bulan/5 tahun, 12 untuk 12 bulan). Tab REFERENSI_KODE tersedia sebagai panduan kode departemen.
                    @else
                        Identitas box (JUDUL_BOX, Dept, Rak) cukup diisi 1 kali pada baris pertama box. Baris dokumen berikutnya dalam box yang sama cukup isi Nama Dokumen, Periode, dan Keterangan. Pengisian JUDUL_BOX baru otomatis memisahkan box baru. Nomor urut berkas otomatis dari atas ke bawah. Kolom MASA_SIMPAN_BULAN diisi angka bulan murni (contoh: 60). Tab REFERENSI_KODE untuk panduan kode departemen &amp; rak gudang.
                    @endif
                </p>
                <div class="cluster">
                    <x-link-button size="sm" variant="primary" :href="route('archives.import.template', ['format' => 'xlsx'])" download="template_arsip_multi_box.xlsx" @click="downloaded = 'template_arsip_multi_box.xlsx'">Template Excel (.xlsx)</x-link-button>
                    <x-link-button size="sm" :href="route('archives.import.template', ['format' => 'csv'])" download="template_arsip_multi_box.csv" @click="downloaded = 'template_arsip_multi_box.csv'">CSV</x-link-button>
                </div>
            </div>
        </div>

        <div class="notice notice--success" x-show="downloaded" x-cloak>
            <p class="notice__body text-sm" x-text="'Berkas Template Berhasil Diunduh! Berkas: ' + downloaded + ' (Tersimpan di: Folder Downloads Komputer, %USERPROFILE%\\Downloads\\)'"></p>
            <x-button variant="ghost" size="sm" @click="downloaded = ''" title="Tutup Notifikasi">Tutup</x-button>
        </div>

        <x-field label="Pilih File Spreadsheet (.xlsx / .csv)" for="archive_import_file">
            <div class="cluster">
                <x-file-input name="file" id="archive_import_file" accept=".xlsx,.xlsm,.csv" @change="selectFile($event)" />
                <x-button variant="primary" @click="preview()" ::disabled="!file || busy !== ''">
                    <span x-text="busy === 'preview' ? 'Menganalisis...' : 'Validasi File'">Validasi File</span>
                </x-button>
            </div>
        </x-field>

        <div class="notice notice--danger" role="alert" x-show="errors.length" x-cloak>
            <div class="notice__body">
                <strong>Ditemukan Kesalahan Validasi:</strong>
                <ul><template x-for="(message, index) in errors" :key="index"><li x-text="message"></li></template></ul>
            </div>
        </div>
        <div class="notice notice--warning" x-show="warnings.length" x-cloak>
            <div class="notice__body">
                <strong>Peringatan &amp; Catatan Penyesuaian:</strong>
                <ul><template x-for="(message, index) in warnings" :key="index"><li x-text="message"></li></template></ul>
            </div>
        </div>

        <template x-if="result">
            @include('archives.partials.import-preview', ['isPic' => $isPic])
        </template>
    </div>

    <x-slot:footer>
        <span class="text-sm" x-show="result" x-cloak x-text="result ? 'Siap diproses: ' + result.total_boxes + ' box (' + result.total_items + ' berkas).' : ''"></span>
        <x-button data-modal-close ::disabled="busy !== ''">Tutup</x-button>
        <x-button @click="loadToForm()" ::disabled="!ready() || busy !== ''" title="Bawa data dari Excel ini ke Formulir Pengajuan Dokumen Arsip untuk alur pengajuan biasa">
            <span x-text="busy === 'form' ? 'Memuat Form...' : 'Lanjutkan ke Form Pengajuan'">Lanjutkan ke Form Pengajuan</span>
        </x-button>
        <x-button variant="primary" @click="commit()" ::disabled="!ready() || busy !== ''" title="Simpan hasil impor dengan status yang dipilih">
            <span x-text="busy === 'commit' ? 'Mengimpor...' : (status === 'draft' ? 'Simpan sebagai Draft' : (status === 'in_warehouse' ? 'Simpan Langsung ke Gudang' : 'Simpan & Ajukan ke Gudang'))">Simpan sebagai Draft</span>
        </x-button>
    </x-slot:footer>
</x-modal>
