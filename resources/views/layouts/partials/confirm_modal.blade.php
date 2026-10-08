{{-- 
    DMS PT INDRACO — Global Confirmation Modal Component
    Replaces native browser confirm() popups with a modern, theme-aligned modal.
    Accessible globally via:
    1. HTML Attribute: <button type="submit" data-confirm="Pesan konfirmasi..." data-confirm-title="Judul" data-confirm-type="danger|warning|success|info">
    2. Javascript: const ok = await window.showConfirmModal({ title, message, type, confirmText, cancelText });
--}}
<div x-data="confirmModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-confirm-modal.window="openModal($event.detail)"
     @keydown.escape.window="cancel()"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm select-none"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">

    {{-- Backdrop Click --}}
    <div class="fixed inset-0" @click="cancel()"></div>

    {{-- Modal Card --}}
    <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden z-10 text-slate-900 dark:text-white"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Accent Glow --}}
        <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full blur-2xl pointer-events-none opacity-40"
             :class="{
                 'bg-rose-500': type === 'danger',
                 'bg-amber-500': type === 'warning',
                 'bg-emerald-500': type === 'success',
                 'bg-blue-500': type === 'info'
             }"></div>

        <div class="flex items-start gap-4">
            {{-- Icon Badge --}}
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border"
                 :class="{
                     'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400': type === 'danger',
                     'bg-amber-500/10 border-amber-500/30 text-amber-600 dark:text-amber-400': type === 'warning',
                     'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400': type === 'success',
                     'bg-blue-500/10 border-blue-500/30 text-blue-600 dark:text-blue-400': type === 'info'
                 }">
                {{-- Danger Icon (Alert Triangle) --}}
                <template x-if="type === 'danger'">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                {{-- Warning Icon (Alert Circle) --}}
                <template x-if="type === 'warning'">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </template>
                {{-- Success Icon (Check Circle) --}}
                <template x-if="type === 'success'">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                {{-- Info Icon (Info) --}}
                <template x-if="type === 'info'">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </template>
            </div>

            {{-- Text Content --}}
            <div class="flex-1 min-w-0 pt-0.5">
                <h3 class="text-base font-extrabold tracking-tight" x-text="title"></h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400 font-medium whitespace-pre-line" x-text="message"></p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button type="button" 
                    x-show="cancelText"
                    @click="cancel()"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                    x-text="cancelText">
            </button>

            <button type="button" 
                    @click="confirm()"
                    class="px-5 py-2.5 rounded-xl text-xs font-black shadow-lg transition flex items-center gap-2 cursor-pointer"
                    :class="{
                        'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20': type === 'danger',
                        'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20': type === 'warning',
                        'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20': type === 'success',
                        'bg-blue-600 hover:bg-blue-500 text-white shadow-blue-600/20': type === 'info'
                    }"
                    x-text="confirmText">
            </button>
        </div>
    </div>
</div>

<script>
    function confirmModalHandler() {
        return {
            isOpen: false,
            title: 'Konfirmasi Tindakan',
            message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
            confirmText: 'Ya, Lanjutkan',
            cancelText: 'Batal',
            type: 'danger',
            resolveCallback: null,

            openModal(detail) {
                this.title = detail.title || (detail.type === 'danger' ? 'Konfirmasi Hapus' : 'Konfirmasi Tindakan');
                this.message = detail.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                this.type = detail.type || 'danger';
                this.confirmText = detail.confirmText || (this.type === 'danger' ? 'Ya, Hapus' : 'Ya, Lanjutkan');
                this.cancelText = detail.cancelText !== undefined ? detail.cancelText : 'Batal';
                this.resolveCallback = detail.resolve || null;
                this.isOpen = true;
            },

            confirm() {
                this.isOpen = false;
                if (typeof this.resolveCallback === 'function') {
                    this.resolveCallback(true);
                    this.resolveCallback = null;
                }
            },

            cancel() {
                this.isOpen = false;
                if (typeof this.resolveCallback === 'function') {
                    this.resolveCallback(false);
                    this.resolveCallback = null;
                }
            }
        };
    }

    // Global Javascript Helper
    window.showConfirmModal = function(options) {
        return new Promise(function(resolve) {
            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: Object.assign({}, options || {}, { resolve: resolve })
            }));
        });
    };

    // Global Javascript Helper for Alert/Notifications (PROJECT_RULES 6.3)
    window.showNotificationModal = function(optionsOrMsg, title, type) {
        var opts = {};
        if (typeof optionsOrMsg === 'string') {
            opts = {
                title: title || 'Pemberitahuan Sistem',
                message: optionsOrMsg,
                type: type || 'warning',
                confirmText: 'Mengerti',
                cancelText: false
            };
        } else {
            opts = Object.assign({
                title: 'Pemberitahuan Sistem',
                type: 'warning',
                confirmText: 'Mengerti',
                cancelText: false
            }, optionsOrMsg || {});
        }
        return window.showConfirmModal(opts);
    };
    window.showAlertModal = window.showNotificationModal;

    // Unobtrusive Global Click Delegator for data-confirm attributes
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;

        // Prevent immediate submission or navigation
        e.preventDefault();
        e.stopPropagation();

        var message = trigger.getAttribute('data-confirm');
        var title = trigger.getAttribute('data-confirm-title') || null;
        var type = trigger.getAttribute('data-confirm-type') || 'danger';
        var confirmText = trigger.getAttribute('data-confirm-btn') || null;
        var cancelText = trigger.getAttribute('data-confirm-cancel') || 'Batal';

        window.showConfirmModal({
            title: title,
            message: message,
            type: type,
            confirmText: confirmText,
            cancelText: cancelText
        }).then(function(confirmed) {
            if (!confirmed) return;

            // Execute original target action
            if (trigger.tagName === 'BUTTON' || (trigger.tagName === 'INPUT' && trigger.type === 'submit')) {
                var form = trigger.form || trigger.closest('form');
                if (form) {
                    // Temporarily remove data-confirm to prevent loop, then submit
                    form.submit();
                }
            } else if (trigger.tagName === 'A' && trigger.href) {
                window.location.href = trigger.href;
            }
        });
    }, true);
</script>
