/* Box label page (archives/labels): QR of each sticker with the local qrcode.min.js (Laravel 7
   print_sticker: 76 px, level M), "Format" toggles sticker 10x10 / form A5, "Cetak Label" prints,
   "Kembali" closes a window opened by the app or goes back in history (Laravel 7). */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof QRCode !== 'undefined') {
        document.querySelectorAll('[data-code]').forEach(function (el) {
            new QRCode(el, { text: el.getAttribute('data-code'), width: 76, height: 76, correctLevel: QRCode.CorrectLevel.M });
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-label-print]')) { window.print(); return; }
        var format = e.target.closest('[data-label-format]');
        if (format) {
            var a5 = document.body.classList.toggle('mode-a5');
            format.textContent = a5 ? '📋 Format: Form A5' : '📋 Format: Stiker 10x10';
            return;
        }
        if (e.target.closest('[data-label-back]')) {
            if (window.opener) { e.preventDefault(); window.close(); } else if (window.history.length > 1) { e.preventDefault(); window.history.back(); }
        }
    });
});
