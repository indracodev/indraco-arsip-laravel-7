{{--
    Printable "Form Pengajuan" (A4), Laravel 7 components/form-pengajuan-modal.blade.php: same
    paper, text and columns; the left panel (screen only) edits the header/approval fields, table
    cells are editable on the paper like Laravel 7. Filled on the server from the saved archive
    (App\Services\ArchiveFormPrint), so the data is on the page even before Alpine runs.
    Always light: prefs.js (theme / font size) is not loaded.
--}}
@php
    $types = ['Simpan', 'Penarikan', 'Pemusnahan', 'Perpanjangan'];
    $date = fn (string $ymd): string => implode('-', array_reverse(explode('-', $ymd)));
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Pengajuan - {{ $archive->title }}</title>
    @foreach (['css/tokens.css', 'css/base.css', 'css/components.css', 'css/pages/form-print.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
    @endforeach
    <link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
    <script src="{{ \App\Support\Asset::url('js/vendor/alpine.min.js') }}" defer></script>
</head>
<body>
<main class="print-page" x-data="{ f: {{ \Illuminate\Support\Js::from($fields) }} }">
    <aside class="card print-panel no-print stack">
        <div class="cluster">
            <x-button variant="primary" @click="window.print()" title="Cetak langsung ke printer atau simpan sebagai PDF">Cetak Dokumen (Print / PDF)</x-button>
            <x-link-button :href="$backUrl">Kembali</x-link-button>
        </div>
        <p class="text-sm text-muted">Pratinjau formulir resmi A4. Kolom tabel terisi otomatis dari data box dan dokumen yang tersimpan, dan masih bisa diubah langsung di kertas sebelum dicetak.</p>
        <label class="field"><span class="field__label">NO. FORM</span>
            <input class="control" type="text" x-model="f.no_form" placeholder="Contoh: FPD/2026/10/001 (opsional)"></label>
        <label class="field"><span class="field__label">TANGGAL PENGAJUAN</span>
            <input class="control" type="date" x-model="f.tanggal_pengajuan"></label>
        <label class="field"><span class="field__label">DEPARTEMEN / SUB DEPT.</span>
            <input class="control" type="text" x-model="f.dept_display"></label>
        <label class="field"><span class="field__label">NAMA PIC / PEMOHON</span>
            <input class="control" type="text" x-model="f.pic_pemohon" placeholder="Ketik nama lengkap pemohon..."></label>
        <fieldset class="field">
            <legend class="field__label">TIPE PENGAJUAN</legend>
            <div class="cluster">
                @foreach ($types as $type)
                    <label class="checkbox"><input type="radio" value="{{ $type }}" x-model="f.tipe"><span>{{ $type }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <label class="field"><span class="field__label">CATATAN FORMULIR</span>
            <textarea class="control" rows="2" x-model="f.catatan" placeholder="Kondisi fisik Box dan dokumen..."></textarea></label>
        <label class="field"><span class="field__label">Disetujui oleh (Atasan Langsung)</span>
            <input class="control" type="text" x-model="f.approval_atasan" placeholder="Nama Atasan Langsung"></label>
        <label class="field"><span class="field__label">Diketahui oleh (Pimpinan Departemen)</span>
            <input class="control" type="text" x-model="f.approval_pimpinan" placeholder="Nama Pimpinan Departemen"></label>
    </aside>

    <article class="fp" id="form-pengajuan-print-document">
        <div class="fp__head">
            <img class="fp__logo" src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="INDRACO">
            <div class="fp__title">
                <h1>Form Pengajuan</h1>
                DMS (Document Management System)
            </div>
        </div>

        <table class="fp__table fp__meta">
            <colgroup><col class="fp__w28"><col class="fp__w26"><col class="fp__w24"><col class="fp__w22"></colgroup>
            <tbody>
                <tr>
                    <td class="fp__label">No. Form</td>
                    <td class="fp__mono" x-text="f.no_form">{{ $fields['no_form'] }}</td>
                    <td class="fp__label">Tanggal Pengajuan</td>
                    <td x-text="f.tanggal_pengajuan ? f.tanggal_pengajuan.split('-').reverse().join('-') : ''">{{ $date($fields['tanggal_pengajuan']) }}</td>
                </tr>
                <tr>
                    <td class="fp__label" title="Departemen/ Sub Dept.">Departemen/ Sub Dept.</td>
                    <td x-text="f.dept_display">{{ $fields['dept_display'] }}</td>
                    <td class="fp__label">Nama PIC/ Pemohon</td>
                    <td x-text="f.pic_pemohon">{{ $fields['pic_pemohon'] }}</td>
                </tr>
            </tbody>
        </table>

        <div class="fp__types">
            <span>Tipe Pengajuan :</span>
            @foreach ($types as $type)
                <span class="fp__type"><span class="fp__check" x-text="f.tipe === @js($type) ? '✓' : ''">{{ $fields['tipe'] === $type ? '✓' : '' }}</span><span>{{ $type }}</span></span>
            @endforeach
        </div>

        <table class="fp__table fp__boxes">
            <thead>
                <tr>
                    <th class="fp__col-no">No.</th>
                    <th>Nama / Judul Box</th>
                    <th class="fp__col-sub">Sub-Dept</th>
                    <th class="fp__col-masa">Masa Simpan<br>(Bulan)</th>
                    <th class="fp__col-wide">Periode Akhir<br>(MM-YYYY)</th>
                    <th class="fp__col-wide">Label Box</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['no'] }}</td>
                        <td class="fp__left" contenteditable="true">{{ $row['title'] }}</td>
                        <td contenteditable="true">{{ $row['sub_dept'] }}</td>
                        <td class="fp__nowrap" contenteditable="true">{{ $row['masa_simpan'] }}</td>
                        <td class="fp__nowrap fp__mono" contenteditable="true">{{ $row['period_end'] }}</td>
                        <td contenteditable="true"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="fp__note">
            <div>Catatan (misal kondisi fisik Box dan dokumen/ *optional) :</div>
            <div class="fp__line" x-text="f.catatan">{{ $fields['catatan'] }}</div>
            <div class="fp__line" x-show="!f.catatan.trim()" x-cloak></div>
        </div>

        <table class="fp__table fp__signs">
            <tbody>
                <tr>
                    @foreach ([['Diajukan oleh,', 'pic_pemohon'], ['Disetujui oleh,', 'approval_atasan'], ['Diketahui oleh,', 'approval_pimpinan']] as [$label, $key])
                        <td>
                            <div class="fp__sign-label">{{ $label }}</div>
                            <div class="fp__sign-space"></div>
                            <div class="fp__sign-name">(&nbsp;<span x-text="f.{{ $key }}">{{ $fields[$key] }}</span>&nbsp;)</div>
                            <div class="fp__sign-date">Tgl. ....................</div>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <div class="fp__guide">
            <div>Petunjuk Pengisian :</div>
            <ol>
                <li>Centang pada tipe pengajuan yang tersedia (Simpan/ Penarikan/ Pemusnahan/ Perpanjangan).</li>
                <li>Isi <strong>Nama/ Judul Box</strong> dan <strong>Masa Simpan Box</strong> untuk pengajuan simpan.</li>
                <li>Isi <strong>Label Box</strong> saat pengajuan Penarikan/ Pemusnahan/ Perpanjangan.</li>
                <li><strong>Masa Simpan Box</strong> di hitung bulanan (maksimal 60 bulan untuk umum dan 120 bulan khusus dept. FAT).</li>
                <li>Dalam satu pengajuan dibatasi maksimal 10 box.</li>
                <li>Approval =
                    <ul>
                        <li>✸ Diajukan oleh <strong>Pemohon</strong></li>
                        <li>✸ Disetujui oleh <strong>Atasan Langsung</strong></li>
                        <li>✸ Diketahui oleh <strong>Pimpinan Departemen</strong></li>
                    </ul>
                </li>
            </ol>
        </div>
    </article>
</main>
</body>
</html>
