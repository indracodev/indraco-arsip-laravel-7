/* Reopen the modal of a server-rendered form after a validation error: <div data-reopen-modal="id" hidden>. */
document.addEventListener('DOMContentLoaded', function () {
    var marker = document.querySelector('[data-reopen-modal]');
    if (marker && window.dmsModal) { window.dmsModal.open(marker.getAttribute('data-reopen-modal')); }
});
