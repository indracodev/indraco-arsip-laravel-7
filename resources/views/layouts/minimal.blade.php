{{-- Temporary plain layout (Phase 2). Replaced by the design system in Phase 3. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; padding: 16px; background: #f4f5f7; color: #1f2937; }
        main { max-width: 420px; margin: 40px auto; background: #fff; padding: 24px; border: 1px solid #d1d5db; border-radius: 6px; }
        label { display: block; margin-top: 12px; font-size: 14px; font-weight: 600; }
        input { width: 100%; box-sizing: border-box; padding: 8px; margin-top: 4px; font-size: 15px; }
        button { margin-top: 16px; padding: 8px 16px; font-size: 15px; cursor: pointer; }
        .alert { padding: 8px 12px; margin-bottom: 12px; border-radius: 4px; background: #e0f2fe; }
        .alert-error, .error { color: #b91c1c; }
        .alert-error { background: #fee2e2; }
        .error { display: block; font-size: 13px; margin-top: 4px; }
    </style>
</head>
<body>
<main>
    @foreach (['success', 'info', 'warning', 'status'] as $flash)
        @if (session($flash))
            <div class="alert" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach
    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>
</body>
</html>
