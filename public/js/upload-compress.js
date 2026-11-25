/* Automatic in-browser compression before upload (context/decisions.md "Kompres otomatis upload").
   Wired to every <x-file-input> whose accept list has .pdf (FileStorage::rules uploads), marked
   data-compress. A file above 500 KB (FileStorage::MAX_KB, unchanged on the server) is replaced:
   JPG/PNG -> smaller JPEG; PDF -> pages rendered with pdf.js (loaded lazily, only here) and written
   into a new PDF of JPEG images. Text of digital PDFs becomes an image (accepted for scans).
   The capture listener runs before the page's own @change handler, stops it, and re-dispatches
   `change` once the input holds the compressed file, so page scripts need no change. */
(function () {
    'use strict';

    var LIMIT = 500 * 1024, TARGET = 480 * 1024, PAGE_OVERHEAD = 600, MAX_PAGES = 30;
    var MAX_SIDE = 2000, MIN_SIDE = 400, PDF_DPI = 150;
    var QUALITIES = [0.8, 0.65, 0.5, 0.38, 0.3];
    var script = document.currentScript;
    var pdfjs = null, redispatching = false;

    function size(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB' : Math.round(bytes / 1024) + ' KB';
    }

    function fail(message) { var error = new Error(message); error.userMessage = true; return error; }

    function toJpeg(canvas, quality) { return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', quality); }); }

    function release(canvas) { canvas.width = 0; canvas.height = 0; }

    /* Full size at falling JPEG quality, then 20 % smaller each round, until it fits the budget
       or the longest side would drop below MIN_SIDE (null = cannot fit). One canvas at a time. */
    async function fit(source, width, height, budget) {
        var scale = Math.min(1, MAX_SIDE / Math.max(width, height));
        for (;;) {
            var w = Math.max(1, Math.round(width * scale)), h = Math.max(1, Math.round(height * scale));
            var canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            var context = canvas.getContext('2d');
            context.fillStyle = '#fff'; // PNG transparency would turn black in JPEG
            context.fillRect(0, 0, w, h);
            context.drawImage(source, 0, 0, w, h);
            for (var i = 0; i < QUALITIES.length; i++) {
                var blob = await toJpeg(canvas, QUALITIES[i]);
                if (blob && blob.size <= budget) { release(canvas); return { blob: blob, width: w, height: h }; }
            }
            release(canvas);
            if (Math.max(w, h) * 0.8 < MIN_SIDE) { return null; }
            scale *= 0.8;
        }
    }

    async function compressImage(file) {
        var bitmap;
        try { bitmap = await createImageBitmap(file); } catch (e) { throw fail('Gambar tidak dapat dibaca. Pilih berkas lain.'); }
        var out = await fit(bitmap, bitmap.width, bitmap.height, TARGET);
        bitmap.close();
        if (!out) { throw fail('Gambar tidak dapat dikompres hingga 500 KB. Pilih berkas lain.'); }
        return new File([out.blob], file.name.replace(/\.[^.]*$/, '') + '.jpg', { type: 'image/jpeg' });
    }

    function loadPdfjs() {
        pdfjs = pdfjs || import(script.dataset.pdfjs).then(function (lib) {
            lib.GlobalWorkerOptions.workerSrc = script.dataset.pdfjsWorker;
            return lib;
        }, function (error) { pdfjs = null; throw error; });
        return pdfjs;
    }

    async function openPdf(lib, file) {
        var task = lib.getDocument({ data: new Uint8Array(await file.arrayBuffer()), isEvalSupported: false, enableXfa: false, useSystemFonts: false, verbosity: 0 });
        try {
            return { task: task, pdf: await task.promise };
        } catch (e) {
            task.destroy();
            throw fail(e && e.name === 'PasswordException'
                ? 'PDF terkunci kata sandi dan tidak dapat dikompres. Buka kuncinya atau pilih berkas lain.'
                : 'PDF rusak atau tidak dapat dibaca. Pilih berkas lain.');
        }
    }

    /* Pages one by one (low-spec clients): render at ~150 dpi, encode to fit an even share of what
       is left of the target, free the canvas, next page. */
    async function compressPdf(file, show) {
        // pdf.js 4 (page + worker) needs Promise.withResolvers: Chromium/WebView2 >= 119, Firefox >= 121.
        if (!Promise.withResolvers) { throw fail('Browser ini terlalu lama untuk kompres PDF otomatis. Perbarui browser atau kecilkan PDF secara manual.'); }
        var lib;
        try { lib = await loadPdfjs(); } catch (e) { throw fail('Modul kompres PDF gagal dimuat. Muat ulang halaman lalu coba lagi.'); }
        var opened = await openPdf(lib, file), pdf = opened.pdf, total = pdf.numPages, pages = [], left = TARGET - 1024;
        try {
            if (total > MAX_PAGES) { throw fail('PDF berisi ' + total + ' halaman; kompres otomatis maksimal ' + MAX_PAGES + ' halaman. Pisahkan berkas atau kecilkan secara manual.'); }
            for (var n = 1; n <= total; n++) {
                show('Mengompres… halaman ' + n + '/' + total);
                var page = await pdf.getPage(n);
                var box = page.getViewport({ scale: 1 }); // points, page rotation applied
                var viewport = page.getViewport({ scale: Math.min(PDF_DPI / 72, MAX_SIDE / Math.max(box.width, box.height)) });
                var canvas = document.createElement('canvas');
                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                await page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
                page.cleanup();
                var out = await fit(canvas, canvas.width, canvas.height, left / (total - n + 1) - PAGE_OVERHEAD);
                release(canvas);
                if (!out) { throw fail('PDF terlalu besar atau terlalu banyak halaman untuk dikompres hingga 500 KB. Kurangi jumlah halaman atau pindai ulang dengan resolusi lebih rendah.'); }
                left -= out.blob.size + PAGE_OVERHEAD;
                pages.push({ jpeg: new Uint8Array(await out.blob.arrayBuffer()), width: out.width, height: out.height, pointsW: box.width, pointsH: box.height });
            }
        } finally {
            opened.task.destroy();
        }
        return new File([buildPdf(pages)], file.name, { type: 'application/pdf' });
    }

    /* Minimal PDF 1.4: one page per JPEG (/DCTDecode), page size = original page size in points.
       Objects: 1 catalog, 2 page tree, then per page: page, content stream, image. */
    function buildPdf(pages) {
        var parts = [], offsets = [], length = 0;
        function add(chunk) { parts.push(chunk); length += chunk.length; } // strings are ASCII only
        function obj(id, body) { offsets[id] = length; add(id + ' 0 obj\n' + body + '\nendobj\n'); }
        function pt(value) { return Math.round(value * 100) / 100; }

        add('%PDF-1.4\n');
        add(new Uint8Array([37, 226, 227, 207, 211, 10])); // binary marker comment
        obj(1, '<< /Type /Catalog /Pages 2 0 R >>');
        obj(2, '<< /Type /Pages /Kids [' + pages.map(function (p, i) { return (3 + i * 3) + ' 0 R'; }).join(' ') + '] /Count ' + pages.length + ' >>');
        pages.forEach(function (p, i) {
            var id = 3 + i * 3, w = pt(p.pointsW), h = pt(p.pointsH);
            var content = 'q ' + w + ' 0 0 ' + h + ' 0 0 cm /Im0 Do Q';
            obj(id, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + w + ' ' + h + '] /Resources << /XObject << /Im0 ' + (id + 2) + ' 0 R >> >> /Contents ' + (id + 1) + ' 0 R >>');
            obj(id + 1, '<< /Length ' + content.length + ' >>\nstream\n' + content + '\nendstream');
            offsets[id + 2] = length;
            add((id + 2) + ' 0 obj\n<< /Type /XObject /Subtype /Image /Width ' + p.width + ' /Height ' + p.height
                + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + p.jpeg.length + ' >>\nstream\n');
            add(p.jpeg);
            add('\nendstream\nendobj\n');
        });
        var count = 3 + pages.length * 3, xref = length, table = 'xref\n0 ' + count + '\n0000000000 65535 f \n';
        for (var k = 1; k < count; k++) { table += String(offsets[k]).padStart(10, '0') + ' 00000 n \n'; }
        add(table + 'trailer\n<< /Size ' + count + ' /Root 1 0 R >>\nstartxref\n' + xref + '\n%%EOF\n');
        return new Blob(parts, { type: 'application/pdf' });
    }

    async function run(input, file, isPdf) {
        var status = document.getElementById(input.id + '_compress_status');
        var form = input.form, job = input.dmsCompressJob = (input.dmsCompressJob || 0) + 1;
        var buttons = form ? Array.prototype.filter.call(form.querySelectorAll('[type="submit"]'), function (b) { return !b.disabled; }) : [];
        function show(text, isError) {
            if (!status || input.dmsCompressJob !== job) { return; }
            status.textContent = text;
            status.hidden = !text;
            status.className = isError ? 'field__error' : 'field__hint';
        }
        buttons.forEach(function (b) { b.disabled = true; });
        if (form) { form.dataset.compressing = String((Number(form.dataset.compressing) || 0) + 1); }
        show('Mengompres…');
        var out = null, message = 'Berkas gagal dikompres. Pilih berkas lain.';
        try {
            out = isPdf ? await compressPdf(file, show) : await compressImage(file);
        } catch (error) {
            if (error && error.userMessage) { message = error.message; }
        }
        buttons.forEach(function (b) { b.disabled = false; });
        if (form) { var busy = Number(form.dataset.compressing) - 1; if (busy > 0) { form.dataset.compressing = String(busy); } else { delete form.dataset.compressing; } }
        if (input.dmsCompressJob !== job) { return; } // another file was picked meanwhile
        if (out) {
            var transfer = new DataTransfer();
            transfer.items.add(out);
            input.files = transfer.files;
            show('Dikompres: ' + size(file.size) + ' → ' + size(out.size));
        } else {
            input.value = '';
            show(message, true);
        }
        redispatching = true;
        try { input.dispatchEvent(new Event('change', { bubbles: true })); } finally { redispatching = false; }
    }

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (redispatching || !input.matches || !input.matches('input[type="file"][data-compress]')) { return; }
        var file = input.files && input.files[0];
        var status = document.getElementById(input.id + '_compress_status');
        input.dmsCompressJob = (input.dmsCompressJob || 0) + 1; // cancels a running job's result
        if (status) { status.hidden = true; status.textContent = ''; }
        if (!file || file.size <= LIMIT) { return; }
        var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
        var isImage = /^image\/(jpeg|png)$/.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
        if (!isPdf && !isImage) { return; } // the page / server rejects other types
        e.stopImmediatePropagation();
        run(input, file, isPdf);
    }, true);

    /* Enter key / other submit paths while a file is still being compressed. */
    document.addEventListener('submit', function (e) {
        if (e.target.dataset && e.target.dataset.compressing) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);
})();
