{{-- "Eksekusi Pemusnahan & Penerbitan BAP Resmi" (Laravel 7 destruction_modals:1-147) for one approved proposal ($log). --}}
@php($archive = $log->archive)
<x-modal :id="'dest-complete-'.$log->id" title="Eksekusi Pemusnahan & Penerbitan BAP Resmi">
    <form method="POST" action="{{ route('destructions.complete', $log) }}" enctype="multipart/form-data" class="stack">
        @csrf
        <p class="text-sm text-muted">Tahap 3: Gudang mengeksekusi fisik pemusnahan berkas, menerbitkan nomor BAP resmi, dan mengosongkan rak.</p>
        <dl class="meta-grid">
            <div><dt>NO. BOX ARSIP</dt><dd class="mono">{{ $archive->box_number ?: '-' }}</dd></div>
            <div><dt>BERKAS ARSIP</dt><dd>{{ $archive->title }}</dd></div>
            <div><dt>DEPT</dt><dd>{{ $archive->department->code ?? '-' }}</dd></div>
        </dl>

        <x-field label="NOMOR BERITA ACARA (BAP) RESMI" :for="'bap_number_'.$log->id" required error="bap_number"
            hint="Nomor resmi Berita Acara Pemusnahan yang diterbitkan oleh bagian Gudang / Kearsipan.">
            <x-input name="bap_number" :id="'bap_number_'.$log->id" required maxlength="100" placeholder="Contoh: BAP/IND/2026/00012"
                :value="'BAP/IND/'.now()->year.'/'.str_pad((string) ($log->archive_id ?: $log->id), 5, '0', STR_PAD_LEFT)" />
        </x-field>
        <x-field label="TANGGAL EKSEKUSI PEMUSNAHAN FISIK" :for="'destruction_date_'.$log->id" required error="destruction_date">
            <x-input type="date" name="destruction_date" :id="'destruction_date_'.$log->id" required :value="now()->format('Y-m-d')" />
        </x-field>
        <x-field label="Upload Berkas / Sertifikat BAP Fisik" :for="'complete_certificate_file_'.$log->id" error="certificate_file"
            hint="Scan surat berita acara fisik bertandatangan (Format: PDF, JPG, PNG Maks. 500 KB).">
            <x-file-input name="certificate_file" :id="'complete_certificate_file_'.$log->id" accept=".pdf,.jpg,.jpeg,.png" badge="OPSIONAL" />
        </x-field>
        <x-field label="CATATAN PELAKSANAAN" optional :for="'notes_'.$log->id" error="notes">
            <x-textarea name="notes" :id="'notes_'.$log->id" rows="2" maxlength="1000" placeholder="Catatan pelaksanaan pemusnahan fisik, saksi, atau vendor pelaksana..." />
        </x-field>

        <div class="notice notice--warning">
            <div class="notice__body text-sm"><strong>PERHATIAN:</strong> Pengesahan ini akan mengubah status arsip menjadi <strong>Telah Dimusnahkan</strong>, mengosongkan slot rak penyimpanan gudang, dan mengaktifkan cetak BAP resmi.</div>
        </div>
        <div class="cluster">
            <span class="topbar__spacer"></span>
            <x-button data-modal-close>Batal</x-button>
            <x-button type="submit" variant="primary"><x-icon name="check" />Sahkan Pemusnahan & Terbitkan BAP</x-button>
        </div>
    </form>
</x-modal>
