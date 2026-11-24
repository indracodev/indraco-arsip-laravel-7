{{-- Validation errors of a modal form on the master pages (the form is reopened with the old input by the page JS). --}}
@if ($errors->any())
    <div class="notice notice--danger" role="alert">
        <div class="notice__body">
            <strong>Data belum tersimpan:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
