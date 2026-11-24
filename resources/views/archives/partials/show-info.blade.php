{{-- Detail Arsip: "Informasi Utama Berkas" + "Rincian Berkas Dokumen Dalam Box" (Laravel 7 archives/show).
     Kode Klasifikasi, Tingkat Perkembangan, Media Simpan, Status Keaslian and Tingkat Akses have no
     database column: Laravel 7 always showed "-", empty, "Reguler" and "Internal"; kept as shown. --}}
<x-card title="Informasi Utama Berkas">
    <div class="stack">
        <div class="cluster">
            <div class="notice__body">
                <span class="text-muted text-sm">JUDUL / URAIAN BERKAS</span>
                <h2>{{ $archive->title }}</h2>
            </div>
            <x-badge-status :status="$archive->status" :destruction="$archive->destructionLog?->approval_status" />
        </div>
        <dl class="meta-grid">
            <div><dt>Departemen</dt><dd>{{ $archive->department->name ?? '-' }} ({{ $archive->department->code ?? '-' }})</dd></div>
            <div><dt>Sub Bagian / Unit</dt><dd>{{ $archive->subDepartment->name ?? '-' }}</dd></div>
            <div><dt>Kode Klasifikasi</dt><dd>-</dd></div>
            <div><dt>Periode Dokumen</dt><dd>{{ $archive->effectivePeriode() }}</dd></div>
            <div><dt>Tingkat Perkembangan</dt><dd>-</dd></div>
            <div><dt>Media Simpan</dt><dd>-</dd></div>
            <div><dt>Tgl. Penyerahan</dt><dd>{{ $archive->tgl_penyerahan?->format('d/m/Y') ?? '-' }}</dd></div>
            <div><dt>Diserahkan Oleh</dt><dd>{{ $archive->creator->name ?? 'User' }}</dd></div>
            <div><dt>Status Keaslian</dt><dd>Reguler</dd></div>
            <div><dt>Tingkat Akses</dt><dd>Internal</dd></div>
            <div class="is-wide"><dt>Kondisi Wadah Fisik</dt><dd>{{ $archive->physical_condition }}</dd></div>
        </dl>
    </div>
</x-card>

@if ($isGudang)
    <x-card title="Informasi Berkas Dokumen (Privasi Departemen)">
        <div class="notice notice--warning">
            <div class="notice__body">Rincian berkas isi dokumen di dalam box bersifat rahasia dan disembunyikan. Wewenang PIC Gudang hanya pada verifikasi fisik nomor box, segel, dan penempatan lokasi rak.</div>
        </div>
    </x-card>
@else
    <x-card :title="'Rincian Berkas Dokumen Dalam Box ('.$archive->items->count().' Berkas)'">
        @if ($archive->items->isNotEmpty())
            <x-table>
                <thead>
                    <tr><th>NO</th><th>NAMA DOKUMEN / BERKAS ARSIP</th><th>PERIODE</th><th>KETERANGAN</th></tr>
                </thead>
                <tbody>
                    @foreach ($archive->items as $item)
                        <tr>
                            <td data-label="NO">{{ $item->item_number }}</td>
                            <td data-label="NAMA DOKUMEN / BERKAS ARSIP"><strong>{{ $item->document_name }}</strong></td>
                            <td data-label="PERIODE">{{ $item->period_text ?? '-' }}</td>
                            <td data-label="KETERANGAN">{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @else
            <p class="text-muted">{{ $archive->content_description ?: 'Tidak ada rincian berkas dokumen.' }}</p>
        @endif
    </x-card>
@endif
