/* Modals and the central confirm dialog (no native confirm/alert, PROJECT_RULES_LAN_FRONTEND_OPS §6.3).
   - [data-modal-open="id"] opens #id, [data-modal-close] closes, Esc / click outside closes.
   - window.showConfirmModal({title, message, type, confirmText, cancelText}) -> Promise<boolean>
   - <button|a data-confirm="message" data-confirm-title data-confirm-type data-confirm-btn data-confirm-cancel>
   Same contract as Laravel 7 layouts/partials/confirm_modal.blade.php. */
(function () {
    var stack = [];

    function open(id, opener) {
        var modal = document.getElementById(id);
        if (!modal || !modal.hidden) { return; }
        modal.hidden = false;
        stack.push({ modal: modal, opener: opener || document.activeElement });
        document.body.classList.add('modal-open');
        /* First VISIBLE target: a hidden button (e.g. the alert's cancel) cannot take focus, and
           querySelector on a selector list returns the first match in document order. */
        var focus = ['[autofocus]', '.modal__footer .btn--primary', '.modal__footer .btn--danger', 'input, select, textarea, button']
            .map(function (selector) { return focusables(modal, selector)[0]; }).filter(Boolean)[0];
        if (focus) { focus.focus(); } else { modal.querySelector('.modal__dialog').focus(); }
    }

    function focusables(modal, selector) {
        return Array.prototype.filter.call(modal.querySelectorAll(selector || 'a[href], input, select, textarea, button, [tabindex]:not([tabindex="-1"])'), function (el) {
            return !el.disabled && el.getClientRects().length > 0;
        });
    }

    function close(modal) {
        if (!modal || modal.hidden) { return; }
        modal.hidden = true;
        var index = -1;
        stack.forEach(function (entry, i) { if (entry.modal === modal) { index = i; } });
        var entry = index >= 0 ? stack.splice(index, 1)[0] : null;
        if (!stack.length) { document.body.classList.remove('modal-open'); }
        modal.dispatchEvent(new CustomEvent('modal-closed'));
        if (entry && entry.opener && entry.opener.focus) { entry.opener.focus(); }
    }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-modal-open]');
        if (opener) { e.preventDefault(); open(opener.getAttribute('data-modal-open'), opener); return; }
        var closer = e.target.closest('[data-modal-close]');
        if (closer) { close(closer.closest('.modal')); return; }
        if (e.target.classList && e.target.classList.contains('modal')) { close(e.target); }
    });

    document.addEventListener('keydown', function (e) {
        if (!stack.length) { return; }
        var modal = stack[stack.length - 1].modal;
        if (e.key === 'Escape') {
            e.preventDefault();
            close(modal);
            return;
        }
        /* Focus trap: Tab / Shift+Tab cycle inside the top modal (also pulls focus back in). */
        if (e.key !== 'Tab') { return; }
        var items = focusables(modal);
        if (!items.length) { e.preventDefault(); return; }
        var first = items[0], last = items[items.length - 1];
        if (!modal.contains(document.activeElement)) { e.preventDefault(); first.focus(); } else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    var pending = null;

    window.showConfirmModal = function (options) {
        options = options || {};
        var modal = document.getElementById('confirm-modal');
        if (!modal) { return Promise.resolve(false); }
        var type = options.type || 'danger';
        modal.className = 'modal modal--' + type;
        modal.querySelector('[data-confirm-title]').textContent = options.title || (type === 'danger' ? 'Konfirmasi Hapus' : 'Konfirmasi Tindakan');
        modal.querySelector('[data-confirm-message]').textContent = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        var ok = modal.querySelector('[data-confirm-ok]');
        ok.textContent = options.confirmText || (type === 'danger' ? 'Ya, Hapus' : 'Ya, Lanjutkan');
        ok.className = 'btn ' + (type === 'danger' ? 'btn--danger' : 'btn--primary');
        var cancel = modal.querySelector('[data-confirm-cancel]');
        cancel.hidden = options.cancelText === false;
        cancel.textContent = options.cancelText || 'Batal';
        if (pending) { pending(false); }
        return new Promise(function (resolve) {
            pending = resolve;
            open('confirm-modal');
        });
    };

    function settle(result) {
        var resolve = pending;
        pending = null;
        if (resolve) { resolve(result); }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('confirm-modal');
        if (!modal) { return; }
        modal.querySelector('[data-confirm-ok]').addEventListener('click', function () { settle(true); close(modal); });
        modal.addEventListener('modal-closed', function () { settle(false); });
    });

    /* Replacement for alert(): one button, resolves when closed. */
    window.showAlertModal = function (message, title, type) {
        return window.showConfirmModal({ title: title || 'Pemberitahuan Sistem', message: String(message), type: type || 'warning', confirmText: 'Mengerti', cancelText: false });
    };

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) { return; }
        e.preventDefault();
        e.stopPropagation();
        window.showConfirmModal({
            title: trigger.getAttribute('data-confirm-title'),
            message: trigger.getAttribute('data-confirm'),
            type: trigger.getAttribute('data-confirm-type') || 'danger',
            confirmText: trigger.getAttribute('data-confirm-btn'),
            cancelText: trigger.getAttribute('data-confirm-cancel') || 'Batal'
        }).then(function (confirmed) {
            if (!confirmed) { return; }
            var form = trigger.form || trigger.closest('form');
            if (form && (trigger.tagName === 'BUTTON' || trigger.type === 'submit')) {
                if (form.requestSubmit) { form.requestSubmit(trigger.type === 'submit' ? trigger : undefined); } else { form.submit(); }
            } else if (trigger.tagName === 'A' && trigger.href) {
                window.location.href = trigger.href;
            } else {
                trigger.dispatchEvent(new CustomEvent('confirmed', { bubbles: true }));
            }
        });
    }, true);

    window.dmsModal = { open: open, close: function (id) { close(document.getElementById(id)); } };
})();
