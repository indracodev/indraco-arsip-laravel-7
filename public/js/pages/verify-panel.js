/* Archive detail, warehouse panels (partials/show-side-gudang): a modal whose form came back with
   validation errors ([data-open-on-load]) opens again so the messages are visible. */
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.querySelector('.modal[data-open-on-load]');
    if (modal && window.dmsModal) { window.dmsModal.open(modal.id); }
});
