/* Per-browser display preferences, applied before first paint (loaded in <head>, not deferred):
   - font size: localStorage "app_font_size" (same key as Laravel 7), 10-26px;
     default = <html data-font-size> (app_settings.app_font_size)
   - menu layout: localStorage "dms_layout" = "side" (default, left sidebar) | "top" (top menu bar) */
(function () {
    var root = document.documentElement;
    var size = root.getAttribute('data-font-size') || '14px';
    var layout = 'side';
    try {
        size = localStorage.getItem('app_font_size') || size;
        layout = localStorage.getItem('dms_layout') === 'top' ? 'top' : 'side';
    } catch (e) { /* storage blocked: keep the defaults */ }
    var px = parseInt(size, 10);
    if (px >= 10 && px <= 26) {
        root.style.fontSize = px + 'px';
    }
    root.setAttribute('data-layout', layout);
})();
