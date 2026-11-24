/* Archive detail (archives/show): file viewer modal + "Kembali".
   - The file is fetched and shown from a blob URL (PDF in the browser's own viewer, images inline):
     download managers such as IDM do not grab it (Laravel 7 used a base64 JSON stream + PDF.js),
     and a 403/404 shows the server message inside the modal instead of an error page.
   - "Kembali" goes back in history when the previous page is ours (catalog filters kept), as in Laravel 7. */
(function () {
    var files = [];
    var blobUrl = null;
    var request = 0;

    function stage() { return document.querySelector('[data-file-stage]'); }

    function clear() {
        request++;
        if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; }
        if (stage()) { stage().textContent = ''; }
    }

    function say(text) {
        var p = document.createElement('p');
        p.className = 'text-muted';
        p.textContent = text;
        stage().textContent = '';
        stage().appendChild(p);
    }

    function failure(response) {
        return response.json().catch(function () { return {}; }).then(function (body) {
            throw new Error(body.message || 'Berkas tidak dapat dibuka (HTTP ' + response.status + ').');
        });
    }

    function show(index) {
        var file = files[index];
        if (!file) { return; }
        clear();
        var token = request;
        document.getElementById('file-viewer-title').textContent = file.name;
        document.querySelector('[data-file-open]').href = file.preview;
        document.querySelector('[data-file-download]').href = file.download;
        document.querySelectorAll('[data-file-tabs] [data-file-index]').forEach(function (tab) {
            tab.setAttribute('aria-pressed', String(Number(tab.getAttribute('data-file-index')) === index));
        });
        say('Memuat berkas...');
        fetch(file.preview, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (response) { return response.ok ? response.blob() : failure(response); })
            .then(function (blob) {
                if (token !== request) { return; }
                blobUrl = URL.createObjectURL(blob);
                var node = document.createElement(file.type === 'pdf' ? 'iframe' : 'img');
                if (file.type === 'pdf') { node.title = file.name; } else { node.alt = file.name; }
                node.src = blobUrl;
                stage().textContent = '';
                stage().appendChild(node);
            })
            .catch(function (error) { if (token === request) { say(error.message); } });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-file-index]');
        if (trigger) { show(Number(trigger.getAttribute('data-file-index'))); return; }
        var back = e.target.closest('[data-back]');
        if (back && document.referrer.indexOf(location.origin + '/') === 0 && history.length > 1) {
            e.preventDefault();
            history.back();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var data = document.getElementById('file-viewer-files');
        if (data) { files = JSON.parse(data.textContent); }
        var modal = document.getElementById('file-viewer');
        if (modal) { modal.addEventListener('modal-closed', clear); }
    });
})();
