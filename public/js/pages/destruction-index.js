/* [Form 3] list (destructions/index): typing in the search box of a tab filters the rows of this
   page, like Laravel 7 (<input data-table-search="table-id">, rows carry data-search text); Enter
   submits ?q= and searches every page on the server (P1, form="<tab>-search"). */
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
