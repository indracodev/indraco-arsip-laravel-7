@props([
    'name' => 'file',
    'id' => null,
    'label' => 'Unggah Dokumen',
    'required' => false,
    'badge' => null,
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'maxSizeMB' => 2,
    'helperText' => 'Format: PDF, JPG, PNG (Maksimal 2MB).',
    'existingUrl' => null,
    'existingLabel' => 'Lihat File Tersimpan',
    'color' => 'amber'
])

@php
    $inputId = $id ?: $name;
@endphp

<div x-data="dmsFileUploader({
        name: '{{ $name }}',
        id: '{{ $inputId }}',
        accept: '{{ $accept }}',
        required: {{ $required ? 'true' : 'false' }},
        maxSizeMB: {{ $maxSizeMB }},
        label: '{{ $label }}',
        existingUrl: {{ json_encode($existingUrl) }},
        existingName: {{ json_encode($existingLabel) }}
     })"
     class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2 font-mono text-xs">

    {{-- Label & Required / Optional Status Badge --}}
    <div class="flex items-center justify-between">
        <label for="{{ $inputId }}" class="text-[11px] font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
            <span>{{ $label }}</span>
            @if($required)
                <span class="text-rose-500 font-black">*</span>
            @endif
        </label>
        @if($badge)
            <span class="px-1.5 py-0.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded text-[9px] font-bold uppercase">
                {{ $badge }}
            </span>
        @elseif($required)
            <span class="px-1.5 py-0.5 bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded text-[9px] font-bold">
                WAJIB
            </span>
        @else
            <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded text-[9px] font-bold">
                OPSIONAL
            </span>
        @endif
    </div>

    {{-- Existing File Banner (e.g. on Edit Form) --}}
    @if($existingUrl)
    <div class="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 truncate">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
            <span class="truncate font-medium text-[11px]">{{ $existingLabel }}</span>
        </div>
        <button type="button" 
                @click="openExistingPreview()"
                class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded font-bold text-[10px] transition shrink-0 inline-flex items-center gap-1">
            <i data-lucide="eye" class="w-3 h-3"></i>
            <span>Pratinjau</span>
        </button>
    </div>
    @endif

    {{-- File Input Control (Hidden when file is already selected to keep UI clean, or visible) --}}
    <div x-show="!hasFile">
        <input 
            type="file" 
            name="{{ $name }}" 
            id="{{ $inputId }}" 
            accept="{{ $accept }}"
            @change="handleFileInput($event)"
            @if($required && !$existingUrl) required @endif
            class="w-full px-2.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-[11px] text-slate-700 dark:text-slate-300 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-amber-500/20 file:text-amber-800 dark:file:text-amber-300 hover:file:bg-amber-500/30 cursor-pointer transition"
        >
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ $helperText }}</p>
    </div>

    {{-- Optimizing & Compressing Progress Bar --}}
    <div x-show="isOptimizing" x-cloak class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-2">
        <div class="flex items-center justify-between text-[11px] text-amber-700 dark:text-amber-300 font-bold">
            <span class="flex items-center gap-1.5">
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin text-amber-600"></i>
                <span>Mengoptimalkan ukuran gambar dokumen...</span>
            </span>
            <span class="text-[10px] text-amber-600">Menjaga ketajaman teks</span>
        </div>
        <div class="w-full bg-amber-500/20 h-1.5 rounded-full overflow-hidden">
            <div class="bg-amber-500 h-full animate-indeterminate rounded-full"></div>
        </div>
    </div>

    {{-- Error / Exceeded Size Alert --}}
    <div x-show="isOverLimit && errorMessage" x-cloak class="p-2.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-600 dark:text-rose-400 text-[11px] flex items-start gap-2">
        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <p class="font-bold" x-text="errorMessage"></p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400">Silakan kompres berkas atau pilih dokumen dengan ukuran maksimal {{ $maxSizeMB }} MB.</p>
        </div>
    </div>

    {{-- LIVE PREVIEW CARD (When File Selected) --}}
    <div x-show="hasFile" x-cloak class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-xs space-y-2">
        <div class="flex items-center justify-between gap-3">
            
            {{-- Left Preview Area (Thumbnail or Format Badge) --}}
            <div class="flex items-center gap-3 min-w-0">
                
                {{-- 1. Image Thumbnail Preview with Zoom Glass --}}
                <template x-if="fileCategory === 'image'">
                    <div @click="openPreview()" 
                         title="Klik untuk perbesar pratinjau"
                         class="relative w-14 h-14 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 cursor-pointer group bg-slate-950">
                        <img :src="fileUrl" :alt="fileName" class="w-full h-full object-cover group-hover:scale-110 transition duration-150">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white">
                            <i data-lucide="zoom-in" class="w-4 h-4"></i>
                        </div>
                    </div>
                </template>

                {{-- 2. PDF Icon Card --}}
                <template x-if="fileCategory === 'pdf'">
                    <div @click="openPreview()" 
                         title="Klik untuk pratinjau PDF"
                         class="w-14 h-14 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex flex-col items-center justify-center shrink-0 cursor-pointer hover:bg-rose-500/20 transition">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                        <span class="text-[8px] font-black uppercase tracking-wider mt-0.5">PDF</span>
                    </div>
                </template>

                {{-- 3. Office / ZIP / Other Icon --}}
                <template x-if="fileCategory !== 'image' && fileCategory !== 'pdf'">
                    <div class="w-14 h-14 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-400 flex flex-col items-center justify-center shrink-0">
                        <i data-lucide="file-check" class="w-6 h-6"></i>
                        <span class="text-[8px] font-black uppercase tracking-wider mt-0.5" x-text="fileCategory"></span>
                    </div>
                </template>

                {{-- File Metadata & Quality Badges --}}
                <div class="min-w-0 space-y-0.5">
                    <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="fileName"></h5>
                    <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                        {{-- Size badge --}}
                        <span class="px-1.5 py-0.2 rounded font-mono font-bold"
                              :class="isOverLimit ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30' : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'"
                              x-text="fileSizeFormatted">
                        </span>

                        {{-- Optimization badge if compressed --}}
                        <template x-if="wasOptimized">
                            <span class="px-1.5 py-0.2 bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30 rounded font-bold"
                                  title="Gambar otomatis dioptimalkan agar ringan tanpa mengurangi keterbacaan teks">
                                ⚡ Teroptimasi dari <span x-text="originalSizeFormatted"></span>
                            </span>
                        </template>

                        {{-- Quality statement --}}
                        <template x-if="!isOverLimit && fileCategory === 'image'">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold hidden sm:inline">
                                • Teks Jelas & Tajam
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Right Actions (Preview Button & Clear Button) --}}
            <div class="flex items-center gap-1.5 shrink-0">
                {{-- Preview Button (Lightbox for Image or Modal for PDF) --}}
                <button type="button" 
                        @click="openPreview()"
                        title="Buka Pratinjau Dokumen Layar Penuh"
                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-[10px] font-bold border border-slate-300 dark:border-slate-700 transition inline-flex items-center gap-1 cursor-pointer">
                    <i data-lucide="eye" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span class="hidden sm:inline">Pratinjau</span>
                </button>

                {{-- Remove / Clear Button --}}
                <button type="button" 
                        @click="clearFile()"
                        title="Hapus berkas ini dan pilih file lain"
                        class="p-1.5 hover:bg-rose-500/10 text-slate-400 hover:text-rose-500 rounded-lg transition border border-transparent hover:border-rose-500/20 cursor-pointer">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>
