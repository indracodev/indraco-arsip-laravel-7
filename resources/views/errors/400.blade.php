{{-- 400 (S3: Host header not in app.allowed_hosts). Static on purpose: with an untrusted Host
     asset()/url()/route() and the guest layout would throw again, so only root-relative paths. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permintaan Tidak Valid</title>
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
</head>
<body>
<main class="guest">
    <div class="guest__box">
        <h1>400 - Permintaan Tidak Valid</h1>
        <p>Server tidak dapat memproses permintaan ini. Buka aplikasi lewat alamat server yang resmi (alamat IP atau nama PC server), bukan alamat lain.</p>
    </div>
</main>
</body>
</html>
