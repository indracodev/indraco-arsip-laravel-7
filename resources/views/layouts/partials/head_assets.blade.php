<!-- PT INDRACO DMS - 100% Offline Asset Suite (Zero External Dependency) -->
<link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">
<link rel="shortcut icon" href="{{ asset('images/icon_indraco.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/icon_indraco.png') }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
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
<script src="{{ asset('js/file-uploader-preview.js') }}"></script>
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

        var failedCount = 0;
        var checkTimer = null;

        function runHeartbeat() {
            var controller = new AbortController();
            var timeout = setTimeout(function() { controller.abort(); }, 2500);

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
            if (failedCount >= 2) {
                if (isTauri) {
                    window.location.replace('http://tauri.localhost/index.html?offline=1');
                } else {
                    showOfflineOverlay();
                }
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
                        Layanan arsip tidak merespons (server mati atau kabel jaringan terlepas). Mencoba menghubungkan kembali otomatis dalam <strong id="indracoCountdownText" style="color:#2563eb;font-family:monospace;font-size:15px;">5</strong> detik...
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
                            window.location.reload();
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

        checkTimer = setInterval(runHeartbeat, 3000);
        window.addEventListener('offline', handleDisconnect);
    })();
</script>
