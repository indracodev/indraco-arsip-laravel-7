/* Per-browser display preferences, applied before first paint (loaded in <head>, not deferred):
   - font size: the account's value <html data-user-font-size> (users.font_size, logged-in pages)
     wins and seeds localStorage "app_font_size" (same key as Laravel 7, 10-26px), so the login
     page on this PC uses it too; otherwise localStorage; default = <html data-font-size>
     (app_settings.app_font_size);
   - theme: localStorage "dms_theme" = light | dark | system (default: light, owner decision;
     the Laravel 7 key "theme" is read when dms_theme is unset) -> <html data-theme="light|dark">.
     One-click toggle: any [data-theme-toggle] button or Alt+T (Laravel 7 shortcut) switches
     light <-> dark and stores it at once; "dms-theme" event keeps the settings modal in sync;
   - breakpoints in rem: the viewport width at 14px-equivalent (width x 14 / font size) sets
     .bp-sm/.bp-md/.bp-lg (>= 320/768/1024) and .bp-lt-* (below) on <html>, so a large font
     switches to the drawer / table cards earlier instead of overflowing (layout.css).
   window.dmsPrefs.setFont(px) / setTheme(choice) / toggleTheme() apply live (settings modal, public/js/app.js).
   The old "dms_layout" key (left sidebar option, dropped: top menu only) is removed. */
(function () {
    var root = document.documentElement;
    var system = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var stored = {};
    var account = root.getAttribute('data-user-font-size');
    try {
        if (account) { localStorage.setItem('app_font_size', account + 'px'); }
        stored.font = account || localStorage.getItem('app_font_size');
        stored.theme = localStorage.getItem('dms_theme') || localStorage.getItem('theme'); /* L7 key: light | dark */
        localStorage.removeItem('dms_layout');
    } catch (e) { /* storage blocked: keep the defaults */ }

    var prefs = window.dmsPrefs = {
        font: 14,
        theme: 'light',
        setFont: function (size) {
            var px = parseInt(size, 10);
            if (px >= 10 && px <= 26) {
                prefs.font = px;
                root.style.fontSize = px + 'px';
                prefs.fit();
            }
        },
        setTheme: function (choice) {
            prefs.theme = choice === 'light' || choice === 'dark' ? choice : 'system';
            var dark = prefs.theme === 'dark' || (prefs.theme === 'system' && !!system && system.matches);
            root.setAttribute('data-theme', dark ? 'dark' : 'light');
            prefs.label();
            prefs.syncImages();
        },
        /* Top bar / login button / Alt+T: the opposite of what is shown now, stored immediately. */
        toggleTheme: function () {
            var choice = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            prefs.setTheme(choice);
            try { localStorage.setItem('dms_theme', choice); } catch (e) { /* storage blocked */ }
            window.dispatchEvent(new CustomEvent('dms-theme', { detail: choice }));
        },
        label: function () {
            var text = root.getAttribute('data-theme') === 'dark' ? 'Ganti ke mode terang (Alt+T)' : 'Ganti ke mode gelap (Alt+T)';
            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                button.setAttribute('aria-label', text);
                button.setAttribute('title', text);
            });
        },
        /* Swap theme-aware images (<img data-img-src-light="..." data-img-src-dark="...">). */
        syncImages: function () {
            var attr = root.getAttribute('data-theme') === 'dark' ? 'data-img-src-dark' : 'data-img-src-light';
            document.querySelectorAll('img[data-img-src-light][data-img-src-dark]').forEach(function (img) {
                var next = img.getAttribute(attr);
                if (next && img.getAttribute('src') !== next) { img.setAttribute('src', next); }
            });
        },
        /* Desktop-first canvas (min 1024x720, owner decision): keep desktop layout active. */
        fit: function () {
            root.classList.add('bp-sm', 'bp-md', 'bp-lg');
            root.classList.remove('bp-lt-sm', 'bp-lt-md', 'bp-lt-lg');
        }
    };

    prefs.setFont(root.getAttribute('data-font-size') || '14px');
    prefs.setFont(stored.font);
    prefs.fit();
    prefs.setTheme(stored.theme || 'light'); /* nothing stored = light (owner decision), not the OS theme */
    document.addEventListener('DOMContentLoaded', function () {
        prefs.label();
        prefs.syncImages();
    });
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-theme-toggle]')) { prefs.toggleTheme(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.altKey && !e.ctrlKey && !e.metaKey && (e.key === 't' || e.key === 'T')) {
            e.preventDefault();
            prefs.toggleTheme();
        }
    });
    if (system && system.addEventListener) {
        system.addEventListener('change', function () { prefs.setTheme(prefs.theme); });
    }
})();
