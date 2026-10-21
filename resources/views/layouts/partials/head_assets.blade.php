<!-- PT INDRACO DMS - 100% Offline Asset Suite (Zero External Dependency) -->
<link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">
<link rel="shortcut icon" href="{{ asset('images/icon_indraco.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/icon_indraco.png') }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/dms-button-indicator.css') }}?v={{ @filemtime(public_path('css/dms-button-indicator.css')) }}">
<script>
    // Ensure default theme is always 'light' (never fallback to OS system dark mode)
    (function() {
        var storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
<script src="{{ asset('js/vendor/tailwindcss.js') }}"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    mono: ['JetBrains Mono', 'Consolas', 'Courier New', 'monospace'],
                }
            }
        }
    }
</script>
<script src="{{ asset('js/vendor/lucide.min.js') }}"></script>
<script src="{{ asset('js/vendor/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdf.worker.min.js') }}";
    }
</script>
<script src="{{ asset('js/file-uploader-preview.js') }}?v={{ @filemtime(public_path('js/file-uploader-preview.js')) }}"></script>
<script src="{{ asset('js/dms-button-indicator.js') }}?v={{ @filemtime(public_path('js/dms-button-indicator.js')) }}"></script>
<script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>

<script>
    // PT INDRACO DMS - Global Diagnostics & Error Helper
    window.dmsCopyError = function(errorText) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(errorText).then(function() {
                alert('Detail error berhasil disalin ke clipboard!');
            });
        }
    };
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && (event.reason.name === 'AbortError' || event.reason.message === 'The user aborted a request.')) {
            return;
        }
        console.warn('DMS Global Exception Caught:', event.reason);
    });

    // PT INDRACO DMS - Desktop & Browser Client Watchdog
    // Memastikan jika server mati, aplikasi langsung menampilkan UI reconnect elegan (bukan error bawaan browser)
    (function() {
        var isTauri = !!(window.__TAURI__ || window.__TAURI_INTERNALS__);
        var isClientMode = isTauri || 
                           window.location.search.indexOf('dms_client=desktop') !== -1 ||
                           document.cookie.indexOf('dms_client=desktop') !== -1;

        if (window.location.search.indexOf('dms_client=desktop') !== -1) {
            document.cookie = "dms_client=desktop; path=/; max-age=86400; SameSite=Lax";
        }

        // Store active path to localStorage so upon reconnect or reload, client returns to this exact page
        try {
            var curPath = window.location.pathname;
            if (curPath && curPath !== '/' && curPath !== '/login' && curPath !== '/logout') {
                localStorage.setItem('indraco_dms_last_active_path', curPath + window.location.search);
            }
        } catch (e) {}

        var failedCount = 0;
        var checkTimer = null;

        function runHeartbeat() {
            var controller = new AbortController();
            var timeout = setTimeout(function() { controller.abort(); }, 5000);

            fetch('/api/health/ping?_t=' + Date.now(), {
                method: 'GET',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            }).then(function(res) {
                clearTimeout(timeout);
                if (res.ok) {
                    failedCount = 0;
                    removeOfflineOverlay();
                } else {
                    handleDisconnect();
                }
            }).catch(function() {
                clearTimeout(timeout);
                handleDisconnect();
            });
        }

        function handleDisconnect() {
            failedCount++;
            if (failedCount >= 3) {
                // Ensure current page path is preserved before showing offline state
                try {
                    var curPath = window.location.pathname;
                    if (curPath && curPath !== '/' && curPath !== '/login' && curPath !== '/logout') {
                        localStorage.setItem('indraco_dms_last_active_path', curPath + window.location.search);
                    }
                } catch (e) {}

                // Keep user in-place on current form page instead of dumping out to initial screen!
                showOfflineOverlay();
            }
        }

        function showOfflineOverlay() {
            if (document.getElementById('indracoDmsOfflineOverlay')) return;

            var overlay = document.createElement('div');
            overlay.id = 'indracoDmsOfflineOverlay';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;background:rgba(241,243,246,0.96);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:20px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#334155;-webkit-font-smoothing:antialiased;';

            overlay.innerHTML = `
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 12px 30px -8px rgba(15,23,42,0.08);padding:38px 32px;max-width:500px;width:100%;text-align:center;">
                    <div style="margin-bottom:20px;">
                        <div style="width:56px;height:56px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,0.03);margin-bottom:12px;">
                            <img src="/images/icon_indraco.png" alt="PT Indraco" style="width:36px;height:36px;object-fit:contain;" onerror="this.src='/images/logo-indraco.png'">
                        </div>
                        <h1 style="font-size:16px;font-weight:700;color:#1e293b;letter-spacing:0.02em;margin:0 0 2px 0;">DMS PT INDRACO</h1>
                        <p style="font-size:12px;color:#64748b;margin:0;">Sistem Manajemen Dokumen & Arsip Digital</p>
                    </div>

                    <div style="width:50px;height:50px;border-radius:50%;background:#fff1f2;border:1px solid #fecdd3;display:inline-flex;align-items:center;justify-content:center;color:#e11d48;margin-bottom:14px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                            <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                            <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                            <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                            <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                            <line x1="12" y1="20" x2="12.01" y2="20"></line>
                        </svg>
                    </div>

                    <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 6px 0;">Sambungan Server Terputus</h2>
                    <div style="display:inline-block;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:600;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;margin-bottom:14px;">Jalur: Tidak Terhubung</div>

                    <p style="font-size:13px;line-height:1.55;color:#475569;margin:0 0 22px 0;">
                        Layanan arsip tidak merespons (server mati atau kabel jaringan terlepas). Data input formulir Anda tetap aman di halaman ini. Mencoba menghubungkan kembali otomatis dalam <strong id="indracoCountdownText" style="color:#2563eb;font-family:monospace;font-size:15px;">5</strong> detik...
                    </p>

                    <div style="display:flex;gap:10px;justify-content:center;">
                        <button id="btnIndracoRetryNow" style="background:#2563eb;color:#ffffff;border:none;border-radius:10px;padding:10px 22px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 6px rgba(37,99,235,0.25);transition:background 0.2s;">Hubungkan Sekarang</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            var countdown = 5;
            var cdInterval = setInterval(function() {
                countdown--;
                var cdEl = document.getElementById('indracoCountdownText');
                if (cdEl) cdEl.textContent = countdown;
                if (countdown <= 0) {
                    countdown = 5;
                    checkServerRecovery();
                }
            }, 1000);

            var retryBtn = document.getElementById('btnIndracoRetryNow');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    checkServerRecovery();
                });
            }

            function checkServerRecovery() {
                var btn = document.getElementById('btnIndracoRetryNow');
                if (btn) btn.textContent = 'Menghubungkan...';

                fetch('/api/health/ping?_t=' + Date.now(), { cache: 'no-store' })
                    .then(function(r) {
                        if (r.ok) {
                            clearInterval(cdInterval);
                            removeOfflineOverlay();
                            // Data in current DOM remains 100% safe without having to reload!
                        } else {
                            if (btn) btn.textContent = 'Hubungkan Sekarang';
                        }
                    })
                    .catch(function() {
                        if (btn) btn.textContent = 'Hubungkan Sekarang';
                    });
            }
        }

        function removeOfflineOverlay() {
            var el = document.getElementById('indracoDmsOfflineOverlay');
            if (el) el.remove();
        }

        checkTimer = setInterval(runHeartbeat, 8000);
        window.addEventListener('offline', handleDisconnect);

        // Warn user before closing window/tab if inside a form editing page or active form input
        window.addEventListener('beforeunload', function(e) {
            if (window.formSubmitted) return;

            var formModal = document.getElementById('form-pengajuan-print-document');
            var isModalOpen = formModal && formModal.offsetParent !== null;

            // Check if active form has unsaved user inputs
            var activeForm = document.getElementById('archiveCreateForm') || document.querySelector('form[action*="borrowings.store"]');
            var isDirtyForm = false;
            if (activeForm) {
                var inputs = activeForm.querySelectorAll('input[type="text"]:not([readonly]), textarea');
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].value && inputs[i].value.trim().length > 0) {
                        isDirtyForm = true;
                        break;
                    }
                }
            }

            if (isModalOpen || isDirtyForm) {
                e.preventDefault();
                e.returnValue = 'Data pengajuan formulir belum disimpan ke server. Apakah Anda yakin ingin meninggalkan atau memuat ulang halaman?';
                return e.returnValue;
            }
        });

        // Global Keyboard Shortcuts (F5, Ctrl+R, Ctrl+Shift+R) Protection inside Iframes & Pages
        window.addEventListener('keydown', function(e) {
            var isCtrlR = (e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R');
            var isF5 = e.key === 'F5';

            if (isF5 || isCtrlR) {
                // If inside an MDI child iframe, refresh only this iframe without losing the parent desktop shell
                if (window.self !== window.top) {
                    if (e.shiftKey) {
                        // Ctrl+Shift+R: Notify parent desktop to persist state and reload
                        try {
                            window.parent.postMessage({ type: 'DMS_HARD_REFRESH' }, '*');
                        } catch (err) {}
                    } else {
                        // Regular F5 or Ctrl+R: Refresh only this active iframe form in-place
                        e.preventDefault();
                        window.location.reload();
                    }
                }
            }
        });
    })();

    // PT INDRACO DMS - Cross-Frame & Desktop Client Unified Print Engine
    (function() {
        function getCleanPrintHtml(sourceEl) {
            if (!sourceEl) return '';

            // Blur active element to ensure in-progress edits/events commit
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                try { document.activeElement.blur(); } catch (e) {}
            }

            var clone = sourceEl.cloneNode(true);

            // 1. Sync live form control values (inputs, textareas, selects) 1-to-1 from source to clone
            var srcInputs = sourceEl.querySelectorAll('input, textarea, select');
            var cloneInputs = clone.querySelectorAll('input, textarea, select');
            for (var m = 0; m < srcInputs.length && m < cloneInputs.length; m++) {
                var sNode = srcInputs[m];
                var cNode = cloneInputs[m];
                if (!sNode || !cNode) continue;
                if (sNode.tagName === 'INPUT') {
                    if (sNode.type === 'checkbox' || sNode.type === 'radio') {
                        if (sNode.checked) {
                            cNode.setAttribute('checked', 'checked');
                            cNode.checked = true;
                        } else {
                            cNode.removeAttribute('checked');
                            cNode.checked = false;
                        }
                    } else {
                        cNode.setAttribute('value', sNode.value || '');
                        cNode.value = sNode.value || '';
                    }
                } else if (sNode.tagName === 'TEXTAREA') {
                    cNode.textContent = sNode.value || '';
                    cNode.value = sNode.value || '';
                } else if (sNode.tagName === 'SELECT') {
                    cNode.value = sNode.value;
                }
            }

            // 2. Remove all <template> tags (Alpine x-for templates, etc.) so they don't break outside Alpine scope
            var templates = clone.querySelectorAll('template');
            for (var i = 0; i < templates.length; i++) {
                if (templates[i].parentNode) {
                    templates[i].parentNode.removeChild(templates[i]);
                }
            }

            // 3. Remove all Alpine.js directives, event bindings, and editing attributes from all nodes
            var allElements = [clone].concat(Array.prototype.slice.call(clone.querySelectorAll('*')));
            for (var i = 0; i < allElements.length; i++) {
                var node = allElements[i];
                if (!node.attributes) continue;

                var toRemove = [];
                for (var j = 0; j < node.attributes.length; j++) {
                    var attr = node.attributes[j];
                    var name = attr.name.toLowerCase();
                    // Strip all Alpine directives (x-*), event bindings (@*), dynamic bindings (:*), and editing flags
                    if (name.indexOf('x-') === 0 || 
                        name.indexOf('@') === 0 || 
                        name.indexOf(':') === 0 || 
                        name === 'contenteditable' || 
                        name === 'tabindex') {
                        toRemove.push(attr.name);
                    }
                }
                for (var k = 0; k < toRemove.length; k++) {
                    node.removeAttribute(toRemove[k]);
                }
            }

            return clone.outerHTML;
        }

        var defaultPrintStyles = '<style>' +
            '@page { size: A4 portrait; margin: 5mm 8mm 5mm 8mm; } ' +
            '* { box-sizing: border-box !important; box-shadow: none !important; text-shadow: none !important; filter: none !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } ' +
            'html, body { margin: 0 !important; padding: 0 !important; background: #ffffff !important; color: #000000 !important; font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important; } ' +
            '#form-pengajuan-print-document { width: 100% !important; max-width: 100% !important; min-height: 0 !important; height: auto !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; background: #ffffff !important; color: #000000 !important; font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important; font-size: 9pt !important; display: block !important; page-break-inside: avoid !important; break-inside: avoid !important; page-break-after: avoid !important; break-after: avoid !important; } ' +
            '#form-pengajuan-print-document table { width: 100% !important; border-collapse: collapse !important; page-break-inside: avoid !important; break-inside: avoid !important; } ' +
            '#form-pengajuan-print-document tr { page-break-inside: avoid !important; break-inside: avoid !important; } ' +
            '#form-pengajuan-print-document th, #form-pengajuan-print-document td { border-color: #000000 !important; color: #000000 !important; } ' +
            '.dms-print-container { width: 100% !important; margin: 0 !important; padding: 0 !important; page-break-after: avoid !important; break-after: avoid !important; } ' +
            '#dms-global-print-portal, #dms-global-print-portal * { visibility: visible !important; color: #000000 !important; } ' +
            '</style>';

        window.dmsPrint = function(elementOrId, customStyles) {
            var el = null;
            if (typeof elementOrId === 'string') {
                el = document.getElementById(elementOrId);
            } else if (elementOrId instanceof HTMLElement) {
                el = elementOrId;
            }

            var targetDoc = el || document.getElementById('form-pengajuan-print-document') || document.body;
            var cleanHtml = getCleanPrintHtml(targetDoc);
            var styles = customStyles || defaultPrintStyles;

            // In desktop client or MDI shell, forms are hosted in an iframe
            if (window.parent && window.parent !== window) {
                try {
                    window.parent.postMessage({
                        type: 'DMS_EXECUTE_PRINT',
                        html: cleanHtml,
                        title: document.title,
                        styles: styles
                    }, '*');
                    return;
                } catch (e) {
                    console.warn('Cross-frame print dispatch failed:', e);
                }
            }

            // Normal / Top window context: isolate cleanly into global print portal
            var portal = document.getElementById('dms-global-print-portal');
            if (!portal) {
                portal = document.createElement('div');
                portal.id = 'dms-global-print-portal';
                document.body.appendChild(portal);
            }
            portal.innerHTML = styles + '<div class="dms-print-container">' + cleanHtml + '</div>';
            document.body.classList.add('has-dms-print-portal');
            window.focus();
            setTimeout(function() {
                (nativePrint || window.print).call(window);
                setTimeout(function() {
                    document.body.classList.remove('has-dms-print-portal');
                }, 300);
            }, 100);
        };

        // If top-level shell window, receive and fulfill print requests from child iframes
        if (window === window.top) {
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'DMS_EXECUTE_PRINT') {
                    var sourceWindow = event.source;
                    var portal = document.getElementById('dms-global-print-portal');
                    if (!portal) {
                        portal = document.createElement('div');
                        portal.id = 'dms-global-print-portal';
                        document.body.appendChild(portal);
                    }
                    portal.innerHTML = (event.data.styles || defaultPrintStyles) + '<div class="dms-print-container">' + (event.data.html || '') + '</div>';
                    document.body.classList.add('has-dms-print-portal');
                    window.focus();
                    setTimeout(function() {
                        try {
                            window.print();
                        } finally {
                            document.body.classList.remove('has-dms-print-portal');
                            if (portal) portal.innerHTML = '';
                            try {
                                if (sourceWindow && typeof sourceWindow.postMessage === 'function') {
                                    sourceWindow.postMessage({ type: 'DMS_PRINT_COMPLETED' }, '*');
                                }
                            } catch (err) {}
                        }
                    }, 150);
                }
            });
        }

        // Child window listener for print completion from parent shell
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'DMS_PRINT_COMPLETED') {
                document.body.classList.remove('has-dms-print-portal');
                window.focus();
            }
        });

        // Cleanup print portal on afterprint
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('has-dms-print-portal');
            var portal = document.getElementById('dms-global-print-portal');
            if (portal) portal.innerHTML = '';
        });

        // Intercept Ctrl+P shortcut when print preview modal is open
        window.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                var printDoc = document.getElementById('form-pengajuan-print-document');
                if (printDoc && printDoc.offsetParent !== null) {
                    e.preventDefault();
                    window.dmsPrint(printDoc);
                }
            }
        });

        // Wrap default window.print so any direct call (even third-party) routes cleanly
        var nativePrint = window.print;
        window.print = function() {
            var printDoc = document.getElementById('form-pengajuan-print-document');
            if (printDoc && printDoc.offsetParent !== null && !document.body.classList.contains('has-dms-print-portal')) {
                window.dmsPrint(printDoc);
                return;
            }
            if (window.parent && window.parent !== window) {
                window.dmsPrint();
                return;
            }
            nativePrint.call(window);
        };
    })();
</script>

<style>
@media screen {
    #dms-global-print-portal {
        display: none !important;
    }
}
@media print {
    body.has-dms-print-portal > *:not(#dms-global-print-portal) {
        display: none !important;
    }
    #dms-global-print-portal {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        z-index: 99999999 !important;
    }
    #dms-global-print-portal,
    #dms-global-print-portal * {
        visibility: visible !important;
        color: #000000 !important;
    }
}
</style>
