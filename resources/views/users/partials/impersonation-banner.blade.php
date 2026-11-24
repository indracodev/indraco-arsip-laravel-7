{{-- Laravel 7 IMPERSONATION BANNER (layouts/app.blade.php): shown while an admin is logged in as another user. --}}
@if (session()->has(\App\Http\Controllers\ImpersonateController::SESSION_KEY))
    @php
        $impersonator = \App\Models\User::query()->find(session(\App\Http\Controllers\ImpersonateController::SESSION_KEY), ['id', 'name']);
        $current = auth()->user()->loadMissing('department:id,code');
    @endphp
    <div class="notice notice--warning" role="status">
        <div class="notice__body">
            Mode Impersonasi Aktif: Anda sedang login sebagai <strong>{{ $current->name }}</strong>
            ({{ $current->roleLabel() }}{{ $current->department ? ' - '.$current->department->code : '' }})
            @if ($impersonator)
                | Akun Asli: <strong>{{ $impersonator->name }}</strong> (Super Admin)
            @endif
        </div>
        <form method="POST" action="{{ route('impersonate.leave') }}">
            @csrf
            <x-button type="submit" variant="primary" size="sm"><x-icon name="logout" />Kembali ke SuperAdmin</x-button>
        </form>
    </div>
@endif
