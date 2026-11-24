{{--
    Printable report (Laravel 7 reports/print): A4 landscape, small logo + title + print time, the
    report table, footer. Always light (no prefs.js); paper units in public/css/pages/report-print.css
    (B8: Laravel 7 linked a missing css/app.css). ?auto_print=1 opens the print dialog (reports-print.js).
--}}
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - PT INDRACO</title>
    @foreach (['css/tokens.css', 'css/base.css', 'css/components.css', 'css/pages/report-print.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ \App\Support\Asset::url($stylesheet) }}">
    @endforeach
    <link rel="icon" type="image/png" href="{{ \App\Support\Asset::url('images/icon-192.png') }}">
    <script src="{{ \App\Support\Asset::url('js/pages/reports-print.js') }}" defer></script>
</head>
<body @if ($autoPrint) data-auto-print @endif>
<main class="rpt-page">
    <div class="rpt-bar cluster no-print">
        <strong class="rpt-bar__title">Pratinjau Laporan PDF - PT INDRACO</strong>
        <x-button variant="primary" data-print><x-icon name="printer" />Cetak / Simpan PDF (Ctrl+P)</x-button>
        <x-link-button :href="route('reports.index', ['type' => $type])">Kembali</x-link-button>
    </div>

    <article class="rpt">
        <header class="rpt__head">
            <div class="cluster">
                <img class="rpt__logo" src="{{ \App\Support\Asset::url('images/logo-indraco.png') }}" alt="Logo Indraco">
                <h1 class="rpt__title">{{ $title }}</h1>
            </div>
            <span>Periode Print: <strong>{{ $printedAt }}</strong></span>
        </header>

        <table class="rpt__table">
            <thead><tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="rpt__empty">Tidak ada rekaman data untuk laporan ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer class="rpt__foot">&copy; {{ date('Y') }} PT INDRACO JAYA PERKASA - Dokumen Sistem Terpusat Halaman 1 dari 1</footer>
    </article>
</main>
</body>
</html>
