/* Archive detail (archives/show): file viewer modal + "Kembali".
   - The file comes as base64 JSON (files.preview_data) and is shown from a blob URL (PDF in the
     browser's own viewer, images inline). Not a plain fetch of the PDF: Internet Download Manager
     grabs PDF responses in Chrome, even fetch(), and the page gets an empty 204 (finding R27;
     Laravel 7 preview-stream also used JSON). A 403/404/413 shows the server message in the modal.
     "Buka di Tab Baru" opens the same blob URL; "Unduh" stays a normal attachment link.
   - Used by archives/show and borrowings/index (same #file-viewer markup).
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

    function toBlob(base64, mime) {
        var binary = atob(base64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
        return new Blob([bytes], { type: mime });
    }

    function show(index) {
        var file = files[index];
        if (!file) { return; }
        clear();
        var token = request;
        document.getElementById('file-viewer-title').textContent = file.name;
        var open = document.querySelector('[data-file-open]');
        open.removeAttribute('href');
        document.querySelector('[data-file-download]').href = file.download;
        document.querySelectorAll('[data-file-tabs] [data-file-index]').forEach(function (tab) {
            tab.setAttribute('aria-pressed', String(Number(tab.getAttribute('data-file-index')) === index));
        });
        say('Memuat berkas...');
        fetch(file.data, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (response) { return response.ok ? response.json().catch(function () { return {}; }) : failure(response); })
            .then(function (body) {
                if (token !== request) { return; }
                if (!body.data) { throw new Error('Berkas tidak dapat dibuka di pratinjau. Gunakan tombol "Unduh".'); }
                blobUrl = URL.createObjectURL(toBlob(body.data, body.mime));
                open.href = blobUrl;
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
