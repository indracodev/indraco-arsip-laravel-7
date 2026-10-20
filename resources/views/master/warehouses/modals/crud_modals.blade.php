    <!-- WINDOWS FORM DIALOG MODAL 1: ADD WAREHOUSE -->
    <div x-show="openAddWarehouse" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="plus" class="w-3.5 h-3.5 text-amber-400"></i> frmWarehouseAdd : Tambah Gedung Gudang Baru</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddWarehouse = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.warehouses.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Data Form Gudang</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE GUDANG (CTH: GUDANG-C)</label>
                        <input type="text" name="code" required placeholder="GUDANG-C" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA GUDANG</label>
                        <input type="text" name="name" required placeholder="Gudang Depo Gedangan Blok C" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT LOKASI GUDANG</label>
                        <textarea name="address" rows="2" placeholder="Kawasan Industri Indraco..." class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddWarehouse = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Memproses...' : 'Simpan Gudang (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 2: EDIT WAREHOUSE -->
    <template x-if="editWarehouseItem">
        <div x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
             @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmWarehouseEdit : Edit Data Gudang</span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editWarehouseItem = null; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/warehouses') }}/' + editWarehouseItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Form Perubahan Gudang</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE GUDANG</label>
                            <input type="text" name="code" :value="editWarehouseItem.code" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-amber-600 dark:text-amber-400 uppercase font-bold focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA GUDANG</label>
                            <input type="text" name="name" :value="editWarehouseItem.name" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ALAMAT LOKASI</label>
                            <textarea name="address" rows="2" x-text="editWarehouseItem.address" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"></textarea>
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editWarehouseItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Gudang (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- WINDOWS FORM DIALOG MODAL 3: ADD RACK LOCATION -->
    <div x-show="openAddLocation" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-400"></i> frmLocationAdd : Tambah Slot Rak Gudang</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAddLocation = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.warehouses.locations.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                    <legend class="px-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Data Form Slot Rak</legend>

                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PILIH GUDANG TARGET</label>
                        <select name="warehouse_id" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-bold">
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.code + ' - ' + wh.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE RAK</label>
                            <input type="text" name="rack_code" required placeholder="RAK-A3" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE BARIS/SHELF</label>
                            <input type="text" name="shelf_code" required placeholder="BARIS-01" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KAPASITAS MAKSIMAL BOX</label>
                        <input type="number" name="box_capacity" value="100" min="1" max="1000" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAddLocation = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Menambahkan...' : 'Tambah Rak (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WINDOWS FORM DIALOG MODAL 4: EDIT RACK LOCATION -->
    <template x-if="editLocationItem">
        <div x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
             @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
             class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
                <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                    <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i> frmLocationEdit : Edit Data Slot Rak</span>
                    <div class="flex items-center gap-1">
                        <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                        <button @click="editLocationItem = null; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                </div>

                <form :action="'{{ url('/master/warehouses/locations') }}/' + editLocationItem.id" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3">
                        <legend class="px-2 font-mono text-xs font-bold text-amber-700 dark:text-amber-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-xs">Form Perubahan Slot Rak</legend>

                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">GUDANG / RUANGAN TARGET</label>
                            <select name="warehouse_id" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-bold">
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" :selected="wh.id == editLocationItem.warehouse_id" x-text="wh.code + ' - ' + wh.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE RAK</label>
                                <input type="text" name="rack_code" :value="editLocationItem.rack_code" required placeholder="RAK-A1" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KODE BARIS/SHELF</label>
                                <input type="text" name="shelf_code" :value="editLocationItem.shelf_code" required placeholder="BARIS-01" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white uppercase focus:outline-none focus:border-amber-500">
                            </div>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">KAPASITAS MAKSIMAL BOX</label>
                            <input type="number" name="box_capacity" :value="editLocationItem.box_capacity" min="1" max="5000" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-2 pt-2 font-mono">
                        <button type="button" @click="editLocationItem = null" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? 'Memperbarui...' : 'Update Rak (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>