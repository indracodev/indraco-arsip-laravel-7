{{-- Tab "Berkas Jatuh Tempo (H-30)" (Laravel 7 destructions/index:289-469). Boxes chosen with the
     checkboxes go to the extend / propose form (GET archive_ids[], buttons with formaction). --}}
@include('destructions.partials.search-form', ['id' => 'h30-search'])
<x-card title="Daftar Berkas Mendekati Jatuh Tempo (Batas Waktu ≤ 30 Hari)">
    <p class="text-sm text-muted">Hanya menampilkan arsip yang telah mencapai atau berada dalam rentang H-30 sebelum masa simpan habis.</p>

    <form method="GET" action="{{ route('destructions.extend_form') }}" class="stack" x-data="{ n: 0 }"
        @change="n = $el.querySelectorAll('input[name=\'archive_ids[]\']:checked').length">
        <div class="cluster">
            <input class="control" type="search" name="q" value="{{ request('q') }}" form="h30-search" data-table-search="h30-table" placeholder="Cari No. Box, judul berkas..." aria-label="Cari No. Box, judul berkas">
            @if ($canPropose)
                <span class="cluster" x-show="n > 0" x-cloak>
                    <x-button type="submit" size="sm" title="Ajukan perpanjangan masa simpan untuk box terpilih"><span x-text="'Perpanjangan (' + n + ' Box)'"></span></x-button>
                    <x-button type="submit" size="sm" variant="danger" :formaction="route('destructions.propose')" title="Ajukan pemusnahan untuk box terpilih"><span x-text="'Pemusnahan (' + n + ' Box)'"></span></x-button>
                </span>
            @endif
        </div>

        <x-table id="h30-table">
            <thead>
                <tr>
                    @if ($canPropose)
                        <th><input type="checkbox" aria-label="Pilih semua" @change="$root.querySelectorAll('input[name=\'archive_ids[]\']').forEach(c => { if (c.closest('tr').hidden === false) c.checked = $event.target.checked })"></th>
                    @endif
                    @include('archives.partials.sort-header', ['column' => 'box_number', 'label' => 'NO. BOX ARSIP'])
                    @include('archives.partials.sort-header', ['column' => 'title', 'label' => 'JUDUL BERKAS & DEPT'])
                    <th>LOKASI GUDANG</th>
                    @include('archives.partials.sort-header', ['column' => 'retention_expiry_date', 'label' => 'TGL JATUH TEMPO'])
                    <th>STATUS RETENSI</th>
                    <th class="table__actions">AKSI PERMINTAAN</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $archive = $row['archive'];
                        $file = $fileIndex(\App\Services\RetentionList::file('Lampiran Digital - '.$archive->title, 'archive', $archive->id, 'file_path', $archive->file_path));
                    @endphp
                    <tr @class(['table__row--danger' => $row['expired'], 'table__row--warning' => ! $row['expired']])
                        data-search="{{ mb_strtolower($archive->box_number.' '.$archive->title.' '.($archive->department->code ?? '')) }}">
                        @if ($canPropose)
                            <td class="table__select"><input type="checkbox" name="archive_ids[]" value="{{ $archive->id }}" aria-label="Pilih {{ $archive->title }}"></td>
                        @endif
                        <td data-label="NO. BOX ARSIP" class="table__nowrap mono">{{ $archive->box_number ?: '-' }}</td>
                        <td data-label="JUDUL BERKAS & DEPT" class="table__wide">
                            <a class="table__title" href="{{ route('archives.show', ['archive' => $archive, 'from' => 'destructions']) }}">{{ $archive->title }}</a>
                            @if ($file !== null)
                                <x-button size="sm" class="btn--icon" data-modal-open="file-viewer" data-file-index="{{ $file }}" title="Pratinjau Berkas Digital"><x-icon name="eye" /></x-button>
                            @endif
                            <div class="table__meta">{{ $archive->department->code ?? 'Dept' }} • Periode: {{ $archive->period_text ?: ($archive->periode_doc ?: '-') }}</div>
                        </td>
                        <td data-label="LOKASI GUDANG">{{ $archive->location ? $archive->displayLocation() : 'Gudang' }}</td>
                        <td data-label="TGL JATUH TEMPO" class="table__nowrap">
                            <strong>{{ $row['expiry']->format('M Y') }}</strong>
                            <div class="table__meta">Retensi: {{ $archive->retentionDisplay() }}</div>
                        </td>
                        <td data-label="STATUS RETENSI"><span class="badge badge--{{ $row['expired'] ? 'danger' : 'warning' }}">{{ $row['badge'] }}</span></td>
                        <td data-label="" class="table__actions">
                            @if ($canPropose)
                                <x-link-button size="sm" :href="route('destructions.extend_form.archive', $archive)" title="Ajukan Perpanjangan Masa Simpan Berkas">Perpanjangan</x-link-button>
                                <x-link-button size="sm" variant="danger" :href="route('destructions.propose.archive', $archive)" title="Ajukan Pemusnahan Berkas (BAP)">Pemusnahan</x-link-button>
                            @else
                                <span class="text-muted text-sm">Menunggu Dept</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <x-empty-state title="Tidak Ada Berkas Mendekati Masa Expiry (H-30)" message="Semua berkas arsip aktif saat ini memiliki masa simpan lebih dari 30 hari kedepan." icon="check" />
                    </td></tr>
                @endforelse
            </tbody>
        </x-table>
        <x-pagination :paginator="$rows" noun="berkas jatuh tempo" />
    </form>
</x-card>
