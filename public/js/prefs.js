/* Per-browser display preferences, applied before first paint (loaded in <head>, not deferred):
   - font size: localStorage "app_font_size" (same key as Laravel 7), 10-26px; default =
     <html data-font-size> (app_settings.app_font_size);
   - theme: localStorage "dms_theme" = light | dark | system (default: follows the OS setting,
     prefers-color-scheme) -> <html data-theme="light|dark">;
   - breakpoints in rem: the viewport width at 14px-equivalent (width x 14 / font size) sets
     .bp-sm/.bp-md/.bp-lg (>= 320/768/1024) and .bp-lt-* (below) on <html>, so a large font
     switches to the drawer / table cards earlier instead of overflowing (layout.css).
   window.dmsPrefs.setFont(px) / setTheme(choice) apply live (settings modal, public/js/app.js).
   The old "dms_layout" key (left sidebar option, dropped: top menu only) is removed. */
(function () {
    var root = document.documentElement;
    var system = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var stored = {};
    try {
        stored.font = localStorage.getItem('app_font_size');
        stored.theme = localStorage.getItem('dms_theme') || localStorage.getItem('theme'); /* L7 key: light | dark */
        localStorage.removeItem('dms_layout');
    } catch (e) { /* storage blocked: keep the defaults */ }

    var prefs = window.dmsPrefs = {
        font: 14,
        theme: 'system',
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
        },
        fit: function () {
            var width = window.innerWidth * 14 / prefs.font;
            [['sm', 320], ['md', 768], ['lg', 1024]].forEach(function (bp) {
                root.classList.toggle('bp-' + bp[0], width >= bp[1]);
                root.classList.toggle('bp-lt-' + bp[0], width < bp[1]);
            });
        }
    };

    prefs.setFont(root.getAttribute('data-font-size') || '14px');
    prefs.setFont(stored.font);
    prefs.fit();
    prefs.setTheme(stored.theme);
    window.addEventListener('resize', prefs.fit);
    if (system && system.addEventListener) {
        system.addEventListener('change', function () { prefs.setTheme(prefs.theme); });
    }
})();
