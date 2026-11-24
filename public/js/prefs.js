/* Per-browser display preference, applied before first paint (loaded in <head>, not deferred):
   font size, localStorage "app_font_size" (same key as Laravel 7), 10-26px;
   default = <html data-font-size> (app_settings.app_font_size).
   The old "dms_layout" key (left sidebar option, dropped: top menu only) is removed. */
(function () {
    var root = document.documentElement;
    var size = root.getAttribute('data-font-size') || '14px';
    try {
        size = localStorage.getItem('app_font_size') || size;
        localStorage.removeItem('dms_layout');
    } catch (e) { /* storage blocked: keep the default */ }
    var px = parseInt(size, 10);
    if (px >= 10 && px <= 26) {
        root.style.fontSize = px + 'px';
    }
})();
