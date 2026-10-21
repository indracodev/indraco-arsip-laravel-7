/**
 * PT INDRACO DMS - Universal Button Loading Indicator & Anti-Spam Protection
 * 
 * Sistem universal untuk:
 * 1. Menampilkan indikator loading animasi (spinner) pada tombol proses.
 * 2. Menonaktifkan tombol sementara untuk mencegah spam klik / double submission.
 * 3. Mengembalikan status tombol secara otomatis setelah proses (Submit / Fetch / AJAX) selesai.
 */
(function() {
    'use strict';

    var SPINNER_SVG = '<svg class="dms-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
        '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
        '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
        '</svg>';

    var lastClickedButton = null;
    var lastClickedTime = 0;

    /**
     * Mengecek apakah elemen tombol harus dikecualikan dari status loading.
     */
    function isIgnoredButton(btn) {
        if (!btn || !(btn instanceof Element)) return true;

        // Atribut pengecualian eksplisit
        if (btn.hasAttribute('data-no-loading') || btn.classList.contains('no-loading') || btn.hasAttribute('data-skip-loading')) {
            return true;
        }

        // Tombol tutup modal / batal
        if (btn.matches('[data-modal-close], [data-dismiss], .modal-close, .close, [aria-label="Close"], [aria-label="Tutup"]')) {
            return true;
        }

        // Tab switcher atau dropdown toggle tanpa form submit
        if (btn.matches('[role="tab"], [data-toggle="dropdown"], [data-dropdown-toggle], [aria-haspopup="true"]')) {
            return true;
        }

        // Tombol yang hanya membatalkan dialog
        var text = (btn.textContent || '').trim().toLowerCase();
        if ((text === 'batal' || text === 'tutup' || text === 'cancel' || text === 'close') && btn.getAttribute('type') !== 'submit') {
            return true;
        }

        return false;
    }

    /**
     * Mengatur status loading pada elemen tombol
     */
    function setButtonLoading(btn, isLoading, customText) {
        if (!btn || !(btn instanceof Element)) return;

        if (isLoading) {
            if (btn.classList.contains('dms-btn-loading')) return;

            // Simpan kondisi awal tombol
            btn.dataset.dmsOriginalHtml = btn.innerHTML;
            btn.dataset.dmsOriginalDisabled = btn.disabled ? 'true' : 'false';
            btn.dataset.dmsOriginalMinWidth = btn.style.minWidth || '';
            btn.dataset.dmsOriginalPointerEvents = btn.style.pointerEvents || '';

            // Kunci lebar tombol saat ini agar tidak berkedip/menyusut
            var rect = btn.getBoundingClientRect();
            if (rect.width > 0) {
                btn.style.minWidth = Math.ceil(rect.width) + 'px';
            }

            // Atur teks / konten loading
            var loadingText = customText || btn.getAttribute('data-loading-text');

            if (btn.tagName === 'INPUT' && (btn.type === 'submit' || btn.type === 'button')) {
                btn.dataset.dmsOriginalValue = btn.value;
                btn.value = loadingText || 'Memproses...';
            } else {
                var trimmedText = (btn.textContent || '').trim();
                if (loadingText) {
                    btn.innerHTML = SPINNER_SVG + '<span>' + loadingText + '</span>';
                } else if (trimmedText.length === 0) {
                    // Tombol icon-only (misal cuma SVG)
                    btn.classList.add('dms-btn-loading-icon-only');
                    btn.innerHTML = SPINNER_SVG;
                } else {
                    // Sisipkan spinner di bagian depan
                    btn.innerHTML = SPINNER_SVG + ' ' + btn.innerHTML;
                }
            }

            btn.disabled = true;
            btn.classList.add('dms-btn-loading');
            btn.setAttribute('aria-busy', 'true');
        } else {
            if (!btn.classList.contains('dms-btn-loading')) return;

            btn.classList.remove('dms-btn-loading');
            btn.classList.remove('dms-btn-loading-icon-only');

            if (btn.dataset.dmsOriginalHtml !== undefined) {
                btn.innerHTML = btn.dataset.dmsOriginalHtml;
                delete btn.dataset.dmsOriginalHtml;
            }

            if (btn.dataset.dmsOriginalValue !== undefined) {
                btn.value = btn.dataset.dmsOriginalValue;
                delete btn.dataset.dmsOriginalValue;
            }

            if (btn.dataset.dmsOriginalMinWidth !== undefined) {
                btn.style.minWidth = btn.dataset.dmsOriginalMinWidth;
                delete btn.dataset.dmsOriginalMinWidth;
            }

            if (btn.dataset.dmsOriginalPointerEvents !== undefined) {
                btn.style.pointerEvents = btn.dataset.dmsOriginalPointerEvents;
                delete btn.dataset.dmsOriginalPointerEvents;
            }

            btn.disabled = (btn.dataset.dmsOriginalDisabled === 'true');
            delete btn.dataset.dmsOriginalDisabled;
            btn.removeAttribute('aria-busy');
            btn._dmsPendingFetches = 0;
        }
    }

    /**
     * Reset semua tombol di halaman yang sedang dalam status loading.
     */
    function resetAllButtons() {
        var buttons = document.querySelectorAll('.dms-btn-loading');
        buttons.forEach(function(b) {
            setButtonLoading(b, false);
        });

        var forms = document.querySelectorAll('form[data-dms-submitting="true"]');
        forms.forEach(function(f) {
            delete f.dataset.dmsSubmitting;
        });
    }

    // 1. Tangkap setiap klik tombol untuk melacak submitter atau trigger AJAX
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('button, input[type="submit"], input[type="button"], a.btn, a[role="button"]');
        if (!btn) return;

        lastClickedButton = btn;
        lastClickedTime = Date.now();
    }, true);

    // 2. Intercept Form Submission (Standard HTTP Forms)
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;
        if (e.defaultPrevented) return;

        // Cari tombol submit terkait
        var submitter = e.submitter || lastClickedButton;
        if (!submitter || !form.contains(submitter)) {
            submitter = form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
        }

        if (submitter && isIgnoredButton(submitter)) return;

        // Cegah spam double submit
        if (form.dataset.dmsSubmitting === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        form.dataset.dmsSubmitting = 'true';

        if (submitter) {
            setButtonLoading(submitter, true);

            // Jika form membuka tab baru (target="_blank"), pulihkan tombol setelah 2.5 detik
            if (form.getAttribute('target') === '_blank') {
                setTimeout(function() {
                    setButtonLoading(submitter, false);
                    delete form.dataset.dmsSubmitting;
                }, 2500);
            } else {
                // Safety timeout 25 detik jika request jaringan gantung
                setTimeout(function() {
                    if (form.dataset.dmsSubmitting === 'true') {
                        setButtonLoading(submitter, false);
                        delete form.dataset.dmsSubmitting;
                    }
                }, 25000);
            }
        }
    }, false);

    // 3. Tangkap event input invalid (HTML5 client validation failure)
    document.addEventListener('invalid', function(e) {
        var form = e.target.form;
        if (form) {
            delete form.dataset.dmsSubmitting;
            var loadingButtons = form.querySelectorAll('.dms-btn-loading');
            loadingButtons.forEach(function(b) {
                setButtonLoading(b, false);
            });
        }
    }, true);

    // 4. Pulihkan tombol saat halaman di-restore dari bfcache (Browser Back/Forward)
    window.addEventListener('pageshow', function() {
        resetAllButtons();
    });

    // 5. Intercept Universal Fetch (Otomatis handle AJAX / Asynchronous button actions)
    if (window.fetch && typeof window.fetch === 'function') {
        var nativeFetch = window.fetch;
        window.fetch = function() {
            var args = arguments;
            var now = Date.now();
            var targetBtn = null;

            // Jika tombol baru saja diklik (< 400ms yang lalu) dan bukan tombol ignore
            if (lastClickedButton && (now - lastClickedTime < 450) && !isIgnoredButton(lastClickedButton)) {
                targetBtn = lastClickedButton;
            }

            if (targetBtn) {
                targetBtn._dmsPendingFetches = (targetBtn._dmsPendingFetches || 0) + 1;
                if (targetBtn._dmsPendingFetches === 1) {
                    setButtonLoading(targetBtn, true);
                }
            }

            return nativeFetch.apply(this, args).finally(function() {
                if (targetBtn) {
                    targetBtn._dmsPendingFetches = Math.max(0, (targetBtn._dmsPendingFetches || 1) - 1);
                    if (targetBtn._dmsPendingFetches === 0) {
                        setButtonLoading(targetBtn, false);
                    }
                }
            });
        };
    }

    // 6. Public API Global untuk pemanggilan manual jika diperlukan
    window.dmsSetButtonLoading = setButtonLoading;
    window.dmsResetAllButtons = resetAllButtons;
})();
