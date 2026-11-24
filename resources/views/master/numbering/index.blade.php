{{-- Format Penomoran Box (Laravel 7 master/numbering): live preview (NumberingService::preview, the counter does
     not move), placeholder guide, one inline edit form per format, "Format Baru (F2)" modal. Admin only.
     Laravel 7's search box filtered nothing (the list was server rendered), so it is not repeated. --}}
@extends('layouts.app')

@section('title', 'Format Penomoran Box')

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/pages/master-reopen.js') }}" defer></script>
@endpush

@section('content')
    @if ($errors->any() && old('_form') === 'numbering-create')
        <div data-reopen-modal="numbering-create" hidden></div>
    @endif
    <x-page-header title="Dynamic Custom Box Code Engine" subtitle="Pengaturan Custom Engine Format Penomoran Otomatis Box Arsip">
        <x-slot:actions>
            <x-link-button :href="route('master.numbering')" title="Segarkan Data (F5)"><x-icon name="refresh" />Refresh (F5)</x-link-button>
            <x-button variant="primary" data-modal-open="numbering-create" data-shortcut="F2"><x-icon name="plus" />Format Baru (F2)</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('master.partials.errors')

    <div class="form-grid form-grid--2">
        <x-card title="LIVE PREVIEW KODE BOX AKTIF">
            <p class="text-sm text-muted">Output Format Penomoran Terpasang:</p>
            <p class="mono table__title" data-preview-code>{{ $previewCode }}</p>
            <p class="text-sm text-muted">Simulasi untuk Dept: FIN | Tahun: {{ now()->format('Y') }} | Sequence Counter Selanjutnya</p>
        </x-card>
        <x-card title="PANDUAN PLACEHOLDER PATTERN FORMAT">
            <dl class="meta-grid">
                @foreach (['{COMPANY}' => 'Perusahaan (IND)', '{DEPT}' => 'Departemen (FIN)', '{YEAR}' => 'Tahun Dokumen', '{ROMAN_MONTH}' => 'Bulan Romawi (I-XII)', '{COUNTER}' => 'Nomor Urut Padded'] as $placeholder => $meaning)
                    <div><dt class="mono">{{ $placeholder }}</dt><dd class="text-sm text-muted">{{ $meaning }}</dd></div>
                @endforeach
            </dl>
        </x-card>
    </div>

    <x-card title="ATURAN FORMAT BOX CODE TERDAFTAR SYSTEM">
        <div class="stack">
            @forelse ($formats as $format)
                <form method="POST" action="{{ route('master.numbering.update', $format) }}" class="stack card card__body">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $format->name }}">
                    <input type="hidden" name="original_counter" value="{{ $format->current_counter }}">
                    <div class="cluster">
                        <strong>{{ $format->name }}</strong>
                        @if ($format->is_active)<span class="badge badge--success">FORMAT AKTIF SYSTEM</span>@endif
                        <span class="text-sm text-muted">Counter: <strong>{{ $format->current_counter }}</strong> | Padding: <strong>{{ $format->padding }} Digit</strong></span>
                    </div>
                    <div class="form-grid form-grid--4">
                        <x-field label="PATTERN TEMPLATE" for="pattern-{{ $format->id }}" required>
                            <x-input name="pattern" id="pattern-{{ $format->id }}" :value="$format->pattern" required maxlength="255" class="mono" />
                        </x-field>
                        <x-field label="NILAI COUNTER" for="counter-{{ $format->id }}" required>
                            <x-input name="current_counter" id="counter-{{ $format->id }}" type="number" :value="$format->current_counter" min="0" required />
                        </x-field>
                        <x-field label="PADDING DIGIT" for="padding-{{ $format->id }}" required>
                            <x-input name="padding" id="padding-{{ $format->id }}" type="number" :value="$format->padding" min="1" max="10" required />
                        </x-field>
                        <div class="cluster">
                            <x-checkbox name="is_active" label="Aktifkan" :checked="$format->is_active" />
                            <x-button type="submit" variant="primary">Simpan (Enter)</x-button>
                        </div>
                    </div>
                </form>
            @empty
                <x-empty-state title="Belum ada format penomoran." />
            @endforelse
        </div>
    </x-card>

    <x-modal id="numbering-create" title="Tambah Format Penomoran Baru">
        <form method="POST" action="{{ route('master.numbering.store') }}" class="stack">
            @csrf
            <input type="hidden" name="_form" value="numbering-create">
            <p class="field__label">Data Custom Format</p>
            <x-field label="NAMA DESKRIPSI FORMAT" for="new-name" required error="name">
                <x-input name="name" id="new-name" :value="old('name')" required maxlength="100" placeholder="Format Standar Indraco 2026" />
            </x-field>
            <x-field label="PATTERN TEMPLATE" for="new-pattern" required error="pattern">
                <x-input name="pattern" id="new-pattern" :value="old('pattern')" required maxlength="255" placeholder="{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}" class="mono" />
            </x-field>
            <div class="form-grid form-grid--2">
                <x-field label="NILAI COUNTER AWAL" for="new-counter" required error="current_counter">
                    <x-input name="current_counter" id="new-counter" type="number" :value="old('current_counter', 0)" min="0" required />
                </x-field>
                <x-field label="PADDING DIGIT" for="new-padding" required error="padding">
                    <x-input name="padding" id="new-padding" type="number" :value="old('padding', 4)" min="1" max="10" required />
                </x-field>
            </div>
            <x-checkbox name="is_active" label="Aktifkan format ini secara otomatis" :checked="(bool) old('is_active')" />
            <div class="modal__footer">
                <x-button data-modal-close>Batal (Esc)</x-button>
                <x-button type="submit" variant="primary">Simpan Format (Enter)</x-button>
            </div>
        </form>
    </x-modal>
@endsection
