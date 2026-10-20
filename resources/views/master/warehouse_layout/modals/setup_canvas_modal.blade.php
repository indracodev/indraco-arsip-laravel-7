    <!-- Modal Setup Dimensi Canvas Workspace -->
    <div x-show="canvasResizeModalOpen" x-cloak class="fixed inset-0 z-[99999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 font-mono">
        <div @click.away="canvasResizeModalOpen = false" class="bg-slate-900 border border-slate-800 rounded p-5 max-w-md w-full shadow-2xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                <h3 class="text-xs font-bold text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="scaling" class="w-4 h-4 text-cyan-400"></i>
                    Ubah Dimensi Canvas Workspace
                </h3>
                <button @click="canvasResizeModalOpen = false" type="button" class="text-slate-400 hover:text-white cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form @submit.prevent="submitCanvasResize()" class="space-y-3 text-xs font-semibold text-slate-300">
                <p class="text-slate-400 text-xs font-medium font-sans">
                    Atur ukuran area lembar kerja (Workspace Canvas 2D) sesuai luas denah fisik gudang:
                </p>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-slate-300 text-[10px] font-bold uppercase mb-1">Lebar Canvas (W px):</label>
                        <input type="number" min="600" max="4000" step="50" x-model.number="canvasResizeForm.width" class="w-full px-2.5 py-1 bg-slate-950 border border-slate-800 rounded font-mono text-white text-xs font-bold focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 text-[10px] font-bold uppercase mb-1">Tinggi Canvas (H px):</label>
                        <input type="number" min="500" max="3000" step="50" x-model.number="canvasResizeForm.height" class="w-full px-2.5 py-1 bg-slate-950 border border-slate-800 rounded font-mono text-white text-xs font-bold focus:border-cyan-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 text-[10px] font-bold uppercase mb-1">Preset Ukuran Standar:</label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button type="button" @click="setPresetCanvasSize(950, 750)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded font-mono font-bold transition text-center border border-slate-700 cursor-pointer">
                            950 × 750 px (Std)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(1200, 900)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded font-mono font-bold transition text-center border border-slate-700 cursor-pointer">
                            1200 × 900 px (Med)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(1600, 1000)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded font-mono font-bold transition text-center border border-slate-700 cursor-pointer">
                            1600 × 1000 px (Luas)
                        </button>
                        <button type="button" @click="setPresetCanvasSize(2000, 1400)" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded font-mono font-bold transition text-center border border-slate-700 cursor-pointer">
                            2000 × 1400 px (XL)
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="canvasResizeModalOpen = false" class="px-3.5 py-1 bg-slate-800 text-slate-400 hover:text-white rounded font-bold border border-slate-700 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-1 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded border border-cyan-700 shadow-xs flex items-center gap-1 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Terapkan Dimensi
                    </button>
                </div>
            </form>
        </div>
    </div>