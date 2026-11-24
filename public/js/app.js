/* Shell behaviour shared by every page (deferred, before Alpine). */
(function () {
    /* Anti double-submit: disable the submit buttons and show a spinner on the one used. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute('data-mockup')) {
            e.preventDefault();
            window.showAlertModal('Ini mockup untuk persetujuan tampilan: formulir belum aktif dan tidak menyimpan apa pun.', 'Mockup', 'info');
            return;
        }
        if (e.defaultPrevented || form.hasAttribute('data-no-lock')) { return; }
        var submitter = e.submitter || form.querySelector('[type="submit"]');
        form.querySelectorAll('[type="submit"]').forEach(function (button) { button.disabled = true; });
        if (submitter) { submitter.classList.add('is-loading'); }
    });

    /* Back/forward cache: a page restored from history must not keep locked buttons. */
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        document.querySelectorAll('form [type="submit"]').forEach(function (button) {
            button.disabled = false;
            button.classList.remove('is-loading');
        });
    });

    /* Keyboard shortcuts kept from Laravel 7: an element with data-shortcut="F2" is clicked on F2. */
    document.addEventListener('keydown', function (e) {
        if (document.body.classList.contains('modal-open') || e.ctrlKey || e.altKey || e.metaKey) { return; }
        var target = document.querySelector('[data-shortcut="' + e.key + '"]');
        if (!target) { return; }
        var typing = /^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement && document.activeElement.tagName);
        if (e.key === 'Escape' && typing) { return; }
        e.preventDefault();
        target.click();
    });

    /* Show / hide a password field (Laravel 7 login had the same toggle). */
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-toggle-password]');
        if (!toggle) { return; }
        var input = document.getElementById(toggle.getAttribute('data-toggle-password'));
        if (input) { input.type = input.type === 'password' ? 'text' : 'password'; }
    });

    document.addEventListener('DOMContentLoaded', function () {
        /* Flash toasts: close button, auto-hide after 6 s. */
        document.querySelectorAll('.toast').forEach(function (toast) {
            var hide = function () {
                toast.classList.add('is-hiding');
                setTimeout(function () { toast.remove(); }, 150);
            };
            var button = toast.querySelector('[data-toast-close]');
            if (button) { button.addEventListener('click', hide); }
            if (toast.hasAttribute('data-autohide')) { setTimeout(hide, 6000); }
        });
    });

    /* PWA: service worker only on secure contexts (https / localhost). */
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () { /* optional */ });
        });
    }

    document.addEventListener('alpine:init', function () {
        /* Sidebar drawer below 1024px. */
        window.Alpine.data('shell', function () {
            return {
                drawer: false,
                toggleDrawer: function () { this.drawer = !this.drawer; },
                closeDrawer: function () { this.drawer = false; }
            };
        });

        /* "Pengaturan Tampilan": font size per user (browser), Laravel 7 presets and 10-26px slider. */
        window.Alpine.data('fontSize', function () {
            var root = document.documentElement;
            var current = parseInt(root.style.fontSize || root.getAttribute('data-font-size'), 10) || 14;
            return {
                size: current,
                saved: current,
                presets: [[10, 'Mini'], [14, 'Kecil'], [19, 'Standar'], [21, 'Besar'], [23, 'X-Large']],
                apply: function (px) {
                    this.size = Math.min(26, Math.max(10, parseInt(px, 10) || 14));
                    root.style.fontSize = this.size + 'px';
                },
                save: function () {
                    this.saved = this.size;
                    try { localStorage.setItem('app_font_size', this.size + 'px'); } catch (e) { /* storage blocked */ }
                    window.dmsModal.close('settings-modal');
                },
                revert: function () { this.apply(this.saved); }
            };
        });
    });
})();
