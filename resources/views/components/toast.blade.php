{{-- Session flash messages as toasts (included once by the layouts). Keys as in Laravel 7. --}}
@php
    $tones = ['success' => 'success', 'info' => 'info', 'status' => 'info', 'warning' => 'warning', 'error' => 'danger'];
    $messages = collect($tones)->filter(fn ($tone, $key) => session()->has($key));
@endphp
@if ($messages->isNotEmpty())
    <div class="toasts" aria-live="polite">
        @foreach ($messages as $key => $tone)
            <div class="toast toast--{{ $tone }}" role="{{ $tone === 'danger' ? 'alert' : 'status' }}" data-autohide>
                <span class="toast__text">{{ session($key) }}</span>
                <x-button variant="ghost" size="sm" class="btn--icon" data-toast-close aria-label="Tutup"><x-icon name="x" /></x-button>
            </div>
        @endforeach
    </div>
@endif
