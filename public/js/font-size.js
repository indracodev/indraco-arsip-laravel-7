/* Per-user font size, applied before first paint (loaded in <head>, not deferred).
   Stored per browser under the same key as Laravel 7 ("app_font_size", 10-26px);
   default = <html data-font-size> (app_settings.app_font_size). */
(function () {
    var root = document.documentElement;
    var size = root.getAttribute('data-font-size') || '14px';
    try {
        size = localStorage.getItem('app_font_size') || size;
    } catch (e) { /* storage blocked: keep the default */ }
    var px = parseInt(size, 10);
    if (px >= 10 && px <= 26) {
        root.style.fontSize = px + 'px';
    }
})();
