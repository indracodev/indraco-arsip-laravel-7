{{-- One-click light/dark theme toggle (also Alt+T); label/title synced by public/js/prefs.js. --}}
<x-button
    variant="ghost"
    {{ $attributes->class(['btn--icon', 'theme-toggle']) }}
    data-theme-toggle="data-theme-toggle"
    aria-label="Ganti ke mode gelap (Alt+T)"
    title="Ganti ke mode gelap (Alt+T)"
><x-icon name="moon" class="theme-toggle__moon" /><x-icon name="sun" class="theme-toggle__sun" /></x-button>

