<div class="flex-1 mx-[16px] overflow-hidden relative flex items-center h-[24px]">
    <!-- Fade gradient masks on left and right edges for smooth visual transition -->
    <div class="pointer-events-none absolute left-0 top-0 bottom-0 w-[24px] bg-gradient-to-r from-slate-900 to-transparent z-10"></div>
    <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-[24px] bg-gradient-to-l from-slate-900 to-transparent z-10"></div>

    <div class="w-full overflow-hidden whitespace-nowrap select-none cursor-default">
        <div class="dms-ticker-track inline-flex items-center text-[11px] font-mono text-amber-400/90 font-medium tracking-wide">
            <!-- Block 1 -->
            <div class="inline-flex items-center gap-[20px] pr-[20px]">
                @for ($i = 0; $i < 3; $i++)
                    <span class="flex items-center gap-[8px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-amber-400/80 animate-pulse shrink-0 inline-block"></span>
                        <span>Document Management System PT. Indraco Global Indonesia</span>
                    </span>
                    <span class="text-slate-600 text-[9px]">•</span>
                @endfor
            </div>
            <!-- Block 2 (Exact duplicate for seamless continuous loop) -->
            <div class="inline-flex items-center gap-[20px] pr-[20px]" aria-hidden="true">
                @for ($i = 0; $i < 3; $i++)
                    <span class="flex items-center gap-[8px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-amber-400/80 animate-pulse shrink-0 inline-block"></span>
                        <span>Document Management System PT. Indraco Global Indonesia</span>
                    </span>
                    <span class="text-slate-600 text-[9px]">•</span>
                @endfor
            </div>
        </div>
    </div>
</div>

<style>
@keyframes dmsContinuousTicker {
    0% {
        transform: translateX(0%);
    }
    100% {
        transform: translateX(-50%);
    }
}
.dms-ticker-track {
    display: inline-flex;
    white-space: nowrap;
    animation: dmsContinuousTicker 35s linear infinite;
    will-change: transform;
}
.dms-ticker-track:hover {
    animation-play-state: paused;
}
</style>
