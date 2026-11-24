{{-- Create form filled from an Excel file (Laravel 7 create.blade.php "EXCEL IMPORT NOTIFICATION BANNER"). --}}
@php($excelImport = session('excel_import'))
@if (is_array($excelImport))
    <div class="notice notice--success" role="status">
        <x-icon name="upload" />
        <div class="notice__body">
            <strong>Data Pengajuan Berhasil Dimuat dari File Excel!</strong>
            <p class="text-sm">Sebanyak <strong class="mono">{{ $excelImport['boxes'] }} Box ({{ $excelImport['items'] }} Berkas)</strong> telah dimuat ke formulir pengajuan. Silakan periksa kelengkapan, cetak Form Pengajuan jika diperlukan, lalu ajukan ke Gudang.</p>
        </div>
        <span class="badge badge--success">Impor Excel</span>
    </div>
@endif
