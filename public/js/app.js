/* Shell behaviour shared by every page (deferred, before Alpine). */
(function () {
    /* Anti double-submit: disable the submit buttons and show a spinner on the one used.
       A disabled button is left out of the submitted data, so the clicked button's name=value
       (e.g. submit_action=draft) is copied into a hidden input first. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || form.hasAttribute('data-no-lock')) { return; }
        var submitter = e.submitter || form.querySelector('[type="submit"]');
        var copy = form.querySelector('input[data-submitter-copy]');
        if (copy) { copy.remove(); }
        if (submitter && submitter.name) {
            copy = document.createElement('input');
            copy.type = 'hidden';
            copy.name = submitter.name;
            copy.value = submitter.value;
            copy.setAttribute('data-submitter-copy', '');
            form.appendChild(copy);
        }
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

    /* Keyboard shortcuts kept from Laravel 7: an element with data-shortcut="F2" is clicked on F2; Ctrl+K focuses topbar search. */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && !e.altKey && e.key.toLowerCase() === 'k') {
            var search = document.getElementById('topbar-search');
            if (search && search.offsetParent !== null) {
                e.preventDefault();
                search.focus();
                search.select();
            }
            return;
        }
        if (document.body.classList.contains('modal-open') || e.ctrlKey || e.altKey || e.metaKey) { return; }
        var target = document.querySelector('[data-shortcut="' + e.key + '"]');
        if (!target) { return; }
        var typing = /^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement && document.activeElement.tagName);
        if (e.key === 'Escape' && (typing || document.querySelector('[data-nav-group][aria-expanded="true"]'))) { return; }
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

    /* Top menu bar groups (keyboard accessible, no hover menus). */
    function closeGroups(except) {
        document.querySelectorAll('[data-nav-group][aria-expanded="true"]').forEach(function (button) {
            if (button !== except) { button.setAttribute('aria-expanded', 'false'); }
        });
    }
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-nav-group]');
        if (toggle) {
            closeGroups(toggle);
            toggle.setAttribute('aria-expanded', toggle.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
            return;
        }
        if (!e.target.closest('.nav__sub')) { closeGroups(null); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        var open = document.querySelector('[data-nav-group][aria-expanded="true"]');
        if (open) { open.setAttribute('aria-expanded', 'false'); open.focus(); }
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
        /* Menu drawer below 1024px. */
        window.Alpine.data('shell', function () {
            return {
                drawer: false,
                toggleDrawer: function () { this.drawer = !this.drawer; },
                closeDrawer: function () { this.drawer = false; }
            };
        });

        /* "Pengaturan Tampilan" (per browser): font size (Laravel 7 presets, 10-26px slider),
           theme (Terang / Gelap / Ikuti Sistem; default Terang) and notification sound (public/js/prefs.js,
           sound.js). Font and theme apply live; all are stored on "Simpan", reverted on close. */
        window.Alpine.data('displaySettings', function () {
            var prefs = window.dmsPrefs;
            var saved = { size: prefs.font, theme: prefs.theme, sound: window.dmsSound ? window.dmsSound.enabled() : true };
            return {
                /* Top bar toggle / Alt+T stores the theme at once: keep it (closing must not revert it). */
                init: function () {
                    var self = this;
                    window.addEventListener('dms-theme', function (e) { self.theme = e.detail; self.saved.theme = e.detail; });
                },
                size: saved.size,
                theme: saved.theme,
                sound: saved.sound,
                saved: saved,
                presets: [[10, 'Mini'], [14, 'Kecil'], [19, 'Standar'], [21, 'Besar'], [23, 'X-Large']],
                themes: [['light', 'Terang'], ['dark', 'Gelap'], ['system', 'Ikuti Sistem']],
                apply: function (px) {
                    this.size = Math.min(26, Math.max(10, parseInt(px, 10) || 14));
                    prefs.setFont(this.size);
                },
                setTheme: function (choice) {
                    this.theme = choice;
                    prefs.setTheme(choice);
                },
                testSound: function () {
                    if (window.dmsSound) { window.dmsSound.play(true); }
                },
                save: function () {
                    this.saved = { size: this.size, theme: this.theme, sound: this.sound };
                    try {
                        localStorage.setItem('app_font_size', this.size + 'px');
                        localStorage.setItem('dms_theme', this.theme);
                        localStorage.setItem('dms_sound', this.sound ? '1' : '0');
                    } catch (e) { /* storage blocked */ }
                    this.saveFontToAccount(this.size);
                    window.dmsModal.close('settings-modal');
                },
                /* Font size also goes to the account (users.font_size, other PCs); the browser copy
                   above keeps working if the request fails. */
                saveFontToAccount: function (size) {
                    var token = document.querySelector('meta[name="csrf-token"]');
                    if (!token || !window.fetch) { return; }
                    fetch('/profile/font-size', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token.content },
                        body: JSON.stringify({ font_size: size })
                    }).then(function (res) {
                        if (res.ok) { document.documentElement.setAttribute('data-user-font-size', size); return; }
                        window.showAlertModal('Ukuran font tersimpan di browser ini, tetapi gagal disimpan ke akun. Coba simpan lagi.', 'Pengaturan Tampilan');
                    }).catch(function () { /* offline: kept in this browser */ });
                },
                revert: function () {
                    this.apply(this.saved.size);
                    this.setTheme(this.saved.theme);
                    this.sound = this.saved.sound;
                }
            };
        });
    });
})();
