<button 
    @click="typeof toggleTheme === 'function' ? toggleTheme() : (theme = (theme === 'dark' ? 'light' : 'dark'), localStorage.setItem('theme', theme), (theme === 'dark' ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')))" 
    type="button" 
    title="Ganti Mode Tampilan (Dark/Light)"
    class="w-[26px] h-[26px] flex items-center justify-center bg-slate-800/90 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-[3px] transition duration-150 active:scale-95 shrink-0 shadow-2xs focus:outline-none"
    aria-label="Ganti Mode Tampilan (Dark/Light)"
>
    <!-- Sun Icon (Shown in Dark Mode) -->
    <svg 
        x-show="theme === 'dark'" 
        x-cloak
        xmlns="http://www.w3.org/2000/svg" 
        class="w-[13px] h-[13px] text-amber-400 stroke-current" 
        viewBox="0 0 24 24" 
        fill="none" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round"
    >
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2"></path>
        <path d="M12 20v2"></path>
        <path d="m4.93 4.93 1.41 1.41"></path>
        <path d="m17.66 17.66 1.41 1.41"></path>
        <path d="M2 12h2"></path>
        <path d="M20 12h2"></path>
        <path d="m6.34 17.66-1.41 1.41"></path>
        <path d="m19.07 4.93-1.41 1.41"></path>
    </svg>

    <!-- Moon Icon (Shown in Light Mode) -->
    <svg 
        x-show="theme !== 'dark'" 
        x-cloak
        xmlns="http://www.w3.org/2000/svg" 
        class="w-[13px] h-[13px] text-sky-300 stroke-current" 
        viewBox="0 0 24 24" 
        fill="none" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round"
    >
        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
    </svg>
</button>
