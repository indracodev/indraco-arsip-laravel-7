@extends('layouts.app')

@section('title', 'Format Penomoran Box - DMS PT Indraco')

@section('content')
<div class="w-full space-y-3" x-data="{ 
    openAdd: false,
    searchQuery: '',
    submitting: false,
    formats: {{ json_encode($formats) }},

    get filteredFormats() {
        if (this.searchQuery.trim() === '') return this.formats;
        const q = this.searchQuery.toLowerCase();
        return this.formats.filter(f => 
            (f.name && f.name.toLowerCase().includes(q)) ||
            (f.pattern && f.pattern.toLowerCase().includes(q))
        );
    }
}">

    <!-- DELPHI ACTION RIBBON TOOLBAR & HEADER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 font-mono">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded">
                <i data-lucide="binary" class="w-4 h-4"></i>
            </span>
            <div>
                <h1 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Dynamic Custom Box Code Engine</h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengaturan Custom Engine Format Penomoran Otomatis Box Arsip (TGroupBox Controls)</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- Search Input -->
            <div class="relative w-full sm:w-56">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400"></i>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari pattern/nama... (Ctrl+F)" 
                    class="w-full pl-8 pr-7 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition"
                >
                <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>

            <!-- Refresh Button (F5) -->
            <button @click="window.location.reload()" type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-400 dark:border-slate-600 rounded text-xs font-mono font-bold transition flex items-center gap-1 shadow-sm shrink-0" title="Segarkan Data (F5)">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh (F5)</span>
            </button>

            <!-- Add Format (F2) -->
            <button @click="openAdd = true" type="button" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-mono font-bold text-xs rounded border border-emerald-700 shadow transition flex items-center gap-1 shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>Format Baru (F2)</span>
            </button>
        </div>
    </div>

    <!-- DELPHI TGROUPBOX 1: LIVE PREVIEW & PLACEHOLDERS REFERENCE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 font-mono">
        <!-- Live Preview Panel -->
        <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-slate-900 text-white shadow-sm flex flex-col justify-between">
            <legend class="px-2 font-mono text-xs font-bold text-emerald-400 bg-slate-950 border border-slate-800 rounded shadow-xs flex items-center gap-1.5">
                <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-400"></i>
                LIVE PREVIEW KODE BOX AKTIF
            </legend>
            <div class="space-y-2 py-2">
                <span class="text-[10px] text-slate-400 font-bold block uppercase">Output Format Penomoran Terpasang:</span>
                <div class="p-2.5 bg-slate-950 border border-slate-800 rounded flex items-center gap-3">
                    <i data-lucide="binary" class="w-6 h-6 text-amber-400 shrink-0"></i>
                    <span class="text-lg font-black font-mono text-amber-400 tracking-wider break-all">
                        {{ $previewCode }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400">Simulasi untuk Dept: FIN | Tahun: {{ date('Y') }} | Sequence Counter Selanjutnya</p>
            </div>
        </fieldset>

        <!-- Placeholders Reference Grid -->
        <fieldset class="lg:col-span-2 border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 shadow-sm font-sans">
            <legend class="px-2 font-mono text-xs font-bold text-amber-600 dark:text-amber-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded shadow-xs flex items-center gap-1.5">
                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                PANDUAN PLACEHOLDER PATTERN FORMAT
            </legend>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 font-mono text-xs pt-1">
                <div class="p-2 bg-slate-100 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block text-[11px]">{COMPANY}</span>
                    <span class="text-[10px] text-slate-500 block">Perusahaan (IND)</span>
                </div>
                <div class="p-2 bg-slate-100 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block text-[11px]">{DEPT}</span>
                    <span class="text-[10px] text-slate-500 block">Departemen (FIN)</span>
                </div>
                <div class="p-2 bg-slate-100 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block text-[11px]">{YEAR}</span>
                    <span class="text-[10px] text-slate-500 block">Tahun Dokumen</span>
                </div>
                <div class="p-2 bg-slate-100 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block text-[11px]">{ROMAN_MONTH}</span>
                    <span class="text-[10px] text-slate-500 block">Bulan Romawi (I-XII)</span>
                </div>
                <div class="p-2 bg-slate-100 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800 col-span-2 sm:col-span-1">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block text-[11px]">{COUNTER}</span>
                    <span class="text-[10px] text-slate-500 block">Nomor Urut Padded</span>
                </div>
            </div>
        </fieldset>
    </div>

    <!-- DELPHI TGROUPBOX 2: CONFIGURED FORMATS LIST -->
    <fieldset class="border border-slate-300 dark:border-slate-800 p-3 rounded bg-white dark:bg-slate-950 shadow-sm font-sans space-y-3">
        <legend class="px-2 font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded shadow-xs flex items-center gap-1.5">
            <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
            ATURAN FORMAT BOX CODE TERDAFTAR SYSTEM
        </legend>

        <div class="space-y-3 font-mono">
            @foreach($formats as $fmt)
            <div class="p-3 rounded border {{ $fmt->is_active ? 'border-amber-500/50 bg-amber-500/5 dark:bg-amber-500/10' : 'border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60' }} space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $fmt->name }}</span>
                        @if($fmt->is_active)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40">FORMAT AKTIF SYSTEM</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-500">Counter: <strong>{{ $fmt->current_counter }}</strong> | Padding: <strong>{{ $fmt->padding }} Digit</strong></span>
                </div>

                <form action="{{ route('master.numbering.update', $fmt) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-200 dark:border-slate-800" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="name" value="{{ $fmt->name }}">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase">PATTERN TEMPLATE</label>
                        <input type="text" name="pattern" value="{{ $fmt->pattern }}" required class="w-full p-1.5 bg-white dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-amber-600 dark:text-amber-400 font-bold focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase">NILAI COUNTER</label>
                        <input type="number" name="current_counter" value="{{ $fmt->current_counter }}" min="0" required class="w-full p-1.5 bg-white dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white font-bold focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase">PADDING DIGIT</label>
                        <input type="number" name="padding" value="{{ $fmt->padding }}" min="1" max="10" required class="w-full p-1.5 bg-white dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-mono text-slate-900 dark:text-white font-bold focus:outline-none">
                    </div>

                    <div class="flex items-end justify-between sm:justify-end gap-2">
                        <label class="flex items-center gap-1 text-xs text-slate-700 dark:text-slate-300 font-bold cursor-pointer mb-1.5">
                            <input type="checkbox" name="is_active" value="1" {{ $fmt->is_active ? 'checked' : '' }} class="rounded border-slate-400 text-amber-500">
                            Aktifkan
                        </label>
                        <button type="submit" :disabled="submitting" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded border border-amber-600 transition disabled:opacity-50 inline-flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                            <span x-text="submitting ? '...' : 'Simpan (Enter)'"></span>
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </fieldset>

    <!-- WINDOWS FORM DIALOG MODAL: ADD NUMBERING FORMAT -->
    <div x-show="openAdd" x-cloak 
         x-data="{ posX: 0, posY: 0, isDragging: false, startX: 0, startY: 0, startDrag(e) { if(e.target.closest('button')||e.target.closest('input')||e.target.closest('textarea')||e.target.closest('select')) return; this.isDragging = true; this.startX = e.clientX - this.posX; this.startY = e.clientY - this.posY; }, onDrag(e) { if(!this.isDragging) return; this.posX = e.clientX - this.startX; this.posY = e.clientY - this.startY; }, stopDrag() { this.isDragging = false; }, resetPos() { this.posX = 0; this.posY = 0; } }"
         @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div :style="posX || posY ? 'transform: translate3d(' + posX + 'px, ' + posY + 'px, 0px);' : ''" class="delphi-window bg-slate-100 dark:bg-slate-900 border-2 border-slate-400 dark:border-slate-700 rounded-lg max-w-md w-full shadow-2xl overflow-hidden font-mono">
            <div @mousedown="startDrag($event)" :class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'" class="bg-gradient-to-r from-slate-800 via-slate-700 to-indigo-950 text-white px-3 py-1.5 flex items-center justify-between border-b border-slate-600 font-mono text-xs select-none">
                <span class="flex items-center gap-1.5 font-bold pointer-events-none"><i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-400"></i> frmNumberingAdd : Tambah Format Penomoran Baru</span>
                <div class="flex items-center gap-1">
                    <button x-show="posX !== 0 || posY !== 0" @click="resetPos()" type="button" class="px-1.5 py-0.5 bg-slate-700 hover:bg-amber-600 border border-slate-600 rounded text-amber-300 hover:text-white text-[10px] font-bold transition mr-1" title="Kembalikan Form ke Tengah">Center</button>
                    <button @click="openAdd = false; resetPos()" type="button" class="text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <form action="{{ route('master.numbering.store') }}" method="POST" class="p-4 space-y-3 font-sans text-xs" @submit="submitting = true">
                @csrf
                <fieldset class="border border-slate-300 dark:border-slate-700 p-3 rounded bg-white/80 dark:bg-slate-950/70 space-y-3 font-mono">
                    <legend class="px-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded shadow-sm">Data Custom Format</legend>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NAMA DESKRIPSI FORMAT</label>
                        <input type="text" name="name" required placeholder="Format Standar Indraco 2026" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PATTERN TEMPLATE</label>
                        <input type="text" name="pattern" required placeholder="{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}" class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-amber-600 dark:text-amber-400 focus:outline-none focus:border-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NILAI COUNTER AWAL</label>
                            <input type="number" name="current_counter" value="0" min="0" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">PADDING DIGIT</label>
                            <input type="number" name="padding" value="4" min="1" max="10" required class="w-full p-2 bg-slate-50 dark:bg-slate-900 border border-slate-400 dark:border-slate-700 rounded font-mono text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="pt-1">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-400 text-emerald-600">
                            Aktifkan format ini secara otomatis
                        </label>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-2 pt-2 font-mono">
                    <button type="button" @click="openAdd = false; resetPos()" class="px-3 py-1.5 bg-slate-300 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-bold hover:bg-slate-400">Batal (Esc)</button>
                    <button type="submit" :disabled="submitting" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded transition disabled:opacity-50 flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? 'Memproses...' : 'Simpan Format (Enter)'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


