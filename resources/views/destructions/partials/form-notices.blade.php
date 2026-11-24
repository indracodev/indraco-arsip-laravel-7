{{-- Department lock notice (PIC) + validation summary of the propose / extend forms ($what = Pemusnahan | Perpanjangan). --}}
@php($formUser = auth()->user())
@if ($formUser->role === \App\Models\User::ROLE_PIC_DEPT)
    <div class="notice notice--warning">
        <div class="notice__body"><strong>Filter Keamanan Departemen Terkunci:</strong> Menampilkan berkas arsip milik <strong>{{ $formUser->department->name ?? 'Departemen Anda' }} ({{ $formUser->department->code ?? 'DEPT' }})</strong>.</div>
    </div>
@endif

@if ($errors->any())
    <div class="notice notice--danger" role="alert">
        <div class="notice__body">
            <strong>Pengajuan {{ $what }} Gagal Diproses. Silakan periksa formulir berikut:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
