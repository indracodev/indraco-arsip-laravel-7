<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>body{font:14px system-ui,sans-serif;margin:0;display:grid;place-items:center;min-height:100vh;background:#f8fafc;color:#0f172a}</style>
</head>
<body>
    <main>
        <h1>{{ config('app.name') }}</h1>
        <p>Server Laravel {{ app()->version() }} aktif. Halaman aplikasi sedang dibangun ulang.</p>
    </main>
</body>
</html>
