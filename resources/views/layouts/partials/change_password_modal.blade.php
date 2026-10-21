<!-- ========================================================= -->
<!-- MODAL GANTI PASSWORD MANDIRI USER (DMS PT INDRACO) -->
<!-- ========================================================= -->
<div 
    id="change-password-modal-wrapper"
    x-data="changePasswordModalComponent()" 
    x-show="isOpen" 
    x-cloak 
    @open-change-password-modal.window="open()"
    @keydown.escape.window="close()" 
    class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-xs select-none"
    style="display: none;"
>
    <!-- Modal Dialog Window (Delphi / Windows Form Style) -->
    <div 
        @click.outside="close()"
        class="bg-slate-100 dark:bg-slate-900 border-2 border-indigo-500/60 dark:border-indigo-500/40 rounded-xl shadow-2xl max-w-md w-full flex flex-col overflow-hidden text-slate-800 dark:text-slate-100 font-sans"
    >
        <!-- Modal Header Toolbar -->
        <div class="px-4 py-2.5 bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950 text-white border-b border-indigo-500/40 flex items-center justify-between shrink-0 font-mono">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-indigo-500/20 text-indigo-400 border border-indigo-500/40 rounded">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                </span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">
                        frmChangePassword : Ganti Password Saya
                    </h3>
                    <p class="text-[10px] text-slate-300 font-sans">
                        Perbarui kata sandi akun {{ auth()->user()->name ?? 'Pengguna' }}
                    </p>
                </div>
            </div>

            <button 
                type="button" 
                @click="close()" 
                class="text-slate-400 hover:text-white transition p-1 rounded hover:bg-slate-800"
                title="Tutup (Esc)"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Body Form -->
        <form action="{{ route('profile.change_password') }}" method="POST" class="p-4 space-y-3 text-xs" @submit="submitting = true">
            @csrf

            <!-- Alert Validation Errors -->
            @if ($errors->has('current_password') || $errors->has('password'))
                <div class="p-2.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-mono space-y-1">
                    @foreach ($errors->get('current_password') as $err)
                        <div class="flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i> {{ $err }}</div>
                    @endforeach
                    @foreach ($errors->get('password') as $err)
                        <div class="flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i> {{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white dark:bg-slate-950/80 space-y-2.5">
                <legend class="px-2 font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">
                    Kredensial Keamanan Akun
                </legend>

                <!-- Current Password -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PASSWORD SAAT INI <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            :type="showCurrent ? 'text' : 'password'" 
                            name="current_password" 
                            required 
                            placeholder="Masukkan password Anda saat ini"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        >
                        <button 
                            type="button" 
                            @click="showCurrent = !showCurrent" 
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            tabindex="-1"
                        >
                            <i :data-lucide="showCurrent ? 'eye-off' : 'eye'" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PASSWORD BARU <span class="text-rose-500">* (Min. 6 Karakter)</span>
                    </label>
                    <div class="relative">
                        <input 
                            :type="showNew ? 'text' : 'password'" 
                            name="password" 
                            required 
                            minlength="6"
                            placeholder="Ketik password baru minimal 6 karakter"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        >
                        <button 
                            type="button" 
                            @click="showNew = !showNew" 
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            tabindex="-1"
                        >
                            <i :data-lucide="showNew ? 'eye-off' : 'eye'" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        ULANGI PASSWORD BARU <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            :type="showConfirm ? 'text' : 'password'" 
                            name="password_confirmation" 
                            required 
                            minlength="6"
                            placeholder="Ketik ulang password baru Anda"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        >
                        <button 
                            type="button" 
                            @click="showConfirm = !showConfirm" 
                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            tabindex="-1"
                        >
                            <i :data-lucide="showConfirm ? 'eye-off' : 'eye'" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            </fieldset>

            <!-- Actions Footer -->
            <div class="flex items-center justify-between pt-2 font-mono">
                <span class="text-[10px] text-slate-500 dark:text-slate-400">
                    * Wajib diisi semua
                </span>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="close()" 
                        class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded text-xs font-bold transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        :disabled="submitting" 
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded border border-indigo-700 shadow-sm flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <i data-lucide="check" class="w-3.5 h-3.5" x-show="!submitting"></i>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Password Baru'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function changePasswordModalComponent() {
    return {
        isOpen: @json($errors->has('current_password') || $errors->has('password')),
        showCurrent: false,
        showNew: false,
        showConfirm: false,
        submitting: false,

        open() {
            this.isOpen = true;
            this.showCurrent = false;
            this.showNew = false;
            this.showConfirm = false;
            this.submitting = false;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        close() {
            this.isOpen = false;
            this.submitting = false;
        }
    };
}
</script>

@if(session('password_success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        alert(@json(session('password_success')));
    });
</script>
@endif
