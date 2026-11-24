/* [Form 3] list (destructions/index): the search box of each tab filters its table rows in the
   page, like Laravel 7 (<input data-table-search="table-id">, rows carry data-search text). */
(function () {
    document.addEventListener('input', function (e) {
        var id = e.target.getAttribute && e.target.getAttribute('data-table-search');
        var table = id && document.getElementById(id);
        if (!table) { return; }
        var q = e.target.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr[data-search]').forEach(function (row) {
            row.hidden = q !== '' && row.getAttribute('data-search').indexOf(q) === -1;
        });
    });
})();
