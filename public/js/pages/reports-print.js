/* Printable report (reports/print): [data-print] button, and ?auto_print=1 (body[data-auto-print])
   opens the print dialog once the page is loaded (Laravel 7: setTimeout 300 ms). */
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-print]')) { window.print(); }
});
window.addEventListener('load', function () {
    if (document.body.hasAttribute('data-auto-print')) { setTimeout(function () { window.print(); }, 300); }
});
