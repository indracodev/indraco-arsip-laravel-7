{{-- 
    DMS PT INDRACO — Global File Preview Lightbox Modal Component
    Handles instant live previews & deep inspection globally for:
    1. Images (JPG, JPEG, PNG, WEBP, GIF, SVG, BMP) with Zoom In/Out, Rotate, Wheel Zoom, & Drag-to-Pan (Geser)
    2. PDFs via Mozilla PDF.js Canvas Engine (100% Anti-IDM POST Stream)
    3. Text & Data Files (TXT, CSV, LOG, JSON, XML, MD) with live monospace content reader
    4. Office & Archive Files (DOCX, XLSX, PPTX, ZIP, RAR) with formatted inspection card
    5. Multi-file Archive Inspection with Tab Switching ribbon
--}}
<div x-data="filePreviewModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-file-preview-modal.window="openModal($event.detail)"
     @keydown.escape.window="closeModal()"
     @keydown.f.window="if(isOpen) toggleFullscreen()"
     class="fixed inset-0 z-[999999] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md select-none font-sans"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">

    {{-- Backdrop Click --}}
    <div class="fixed inset-0" @click="closeModal()"></div>

    {{-- Modal Box Container --}}
    <div class="relative w-full flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden z-10 text-slate-900 dark:text-white transition-all duration-200"
         :class="isFullscreen ? 'fixed inset-0 rounded-none h-screen max-w-none' : 'max-w-6xl h-[94vh] rounded-3xl'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Header Bar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-950/95 shrink-0 font-mono">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                {{-- Type Icon Badge --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border"
                     :class="{
                         'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400': fileType === 'image',
                         'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400': fileType === 'pdf',
                         'bg-cyan-500/10 border-cyan-500/30 text-cyan-600 dark:text-cyan-400': fileType === 'text',
                         'bg-blue-500/10 border-blue-500/30 text-blue-600 dark:text-blue-400': fileType === 'other'
                     }">
                    <template x-if="fileType === 'image'">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'pdf'">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'text'">
                        <i data-lucide="file-code" class="w-5 h-5"></i>
                    </template>
                    <template x-if="fileType === 'other'">
                        <i data-lucide="paperclip" class="w-5 h-5"></i>
                    </template>
                </div>

                {{-- Document Title & Meta --}}
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs sm:text-sm font-bold truncate text-slate-900 dark:text-white" x-text="fileName || 'Pratinjau Dokumen'"></h3>
                        <template x-if="archiveBox">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30 shrink-0" x-text="archiveBox"></span>
                        </template>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 truncate">
                        <span class="px-1.5 py-0.2 rounded font-bold uppercase"
                              :class="{
                                  'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300': fileType === 'image',
                                  'bg-rose-500/15 text-rose-700 dark:text-rose-300': fileType === 'pdf',
                                  'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300': fileType === 'text',
                                  'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300': fileType === 'other'
                              }" 
                              x-text="fileExt ? fileExt.toUpperCase() : fileType.toUpperCase()">
                        </span>
                        <template x-if="archiveTitle">
                            <span>• <span class="font-medium text-slate-700 dark:text-slate-300" x-text="archiveTitle"></span></span>
                        </template>
                        <template x-if="archiveDept">
                            <span>(<span x-text="archiveDept"></span>)</span>
                        </template>
                        <template x-if="fileSize">
                            <span>• <span x-text="fileSize"></span></span>
                        </template>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-show="isOptimized">• Resolusi Tinggi</span>
                    </div>
                </div>
            </div>

            {{-- Action Controls (Zoom / Rotate / Fullscreen / Download / External / Close) --}}
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                {{-- Image Interactive Inspection Tools --}}
                <template x-if="fileType === 'image'">
                    <div class="flex items-center gap-1 bg-slate-200/80 dark:bg-slate-800 rounded-xl p-1 text-slate-700 dark:text-slate-300">
                        <button type="button" @click="zoomOut()" title="Perkecil (-)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span class="text-[10px] font-mono font-bold px-1 min-w-[42px] text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" @click="zoomIn()" title="Perbesar (+)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        <button type="button" @click="rotateCcw()" title="Putar 90° Berlawanan Jarum Jam (Kiri)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                        <button type="button" @click="rotateCw()" title="Putar 90° Searah Jarum Jam (Kanan)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.85.99 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        </button>
                        <button type="button" @click="flip180()" title="Balik 180° (Putar Balik Terbalik)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer flex items-center gap-1 text-[10px] font-mono font-bold px-1.5 text-amber-600 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                            <span>180°</span>
                        </button>
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        <button type="button" @click="resetView()" title="Reset Tampilan (Fit 1:1, Orientasi 0°)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                        </button>
                    </div>
                </template>

                {{-- PDF Interactive Inspection Tools (Pagination, Zoom & Rotation) --}}
                <template x-if="fileType === 'pdf' && !loadingPdf && !pdfError">
                    <div class="flex items-center gap-1 bg-slate-200/80 dark:bg-slate-800 rounded-xl p-1 text-slate-700 dark:text-slate-300">
                        {{-- Prev Page --}}
                        <button type="button" @click="pdfPrevPage()" :disabled="pdfPageNum <= 1" title="Halaman Sebelumnya" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <span class="text-[10px] font-mono font-bold px-1 min-w-[48px] text-center" x-text="pdfPageNum + ' / ' + pdfTotalPages"></span>
                        {{-- Next Page --}}
                        <button type="button" @click="pdfNextPage()" :disabled="pdfPageNum >= pdfTotalPages" title="Halaman Berikutnya" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        {{-- Zoom Out --}}
                        <button type="button" @click="pdfZoomOut()" title="Perkecil (-)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span class="text-[10px] font-mono font-bold px-1 min-w-[40px] text-center" x-text="Math.round(pdfScale * 100) + '%'"></span>
                        {{-- Zoom In --}}
                        <button type="button" @click="pdfZoomIn()" title="Perbesar (+)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        {{-- Rotate CCW --}}
                        <button type="button" @click="rotatePdfCcw()" title="Putar 90° Berlawanan Jarum Jam (Kiri)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                        {{-- Rotate CW --}}
                        <button type="button" @click="rotatePdfCw()" title="Putar 90° Searah Jarum Jam (Kanan)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.85.99 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        </button>
                        {{-- Flip 180 --}}
                        <button type="button" @click="flipPdf180()" title="Balik 180° (Putar Balik Terbalik)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer flex items-center gap-1 text-[10px] font-mono font-bold px-1.5 text-amber-600 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                            <span>180°</span>
                        </button>
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        {{-- Reset Fit Zoom & Rotation --}}
                        <button type="button" @click="pdfResetZoom()" title="Reset Ukuran & Orientasi Normal" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded-lg transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                        </button>
                    </div>
                </template>

                {{-- Fullscreen Toggle --}}
                <button type="button" @click="toggleFullscreen()" :title="isFullscreen ? 'Kembalikan Ukuran (F)' : 'Layar Penuh (F)'" 
                   class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl transition border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="w-4 h-4"></i>
                </button>

                {{-- Download Button (Opsional / Prioritas Preview) --}}
                <button type="button" 
                   @click="downloadCurrentFile()" 
                   title="Unduh Berkas Dokumen" 
                   class="p-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl transition border border-emerald-500/30 cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-mono">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span class="hidden md:inline">Unduh</span>
                </button>

                {{-- Close Button --}}
                <button type="button" @click="closeModal()" title="Tutup (ESC)" 
                        class="p-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl transition border border-rose-500/20 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Multi-File Selection Ribbon (if multiple files exist for this archive) --}}
        <template x-if="fileList && fileList.length > 1">
            <div class="flex items-center gap-2 px-4 sm:px-6 py-2 bg-slate-100/90 dark:bg-slate-950/70 border-b border-slate-200 dark:border-slate-800 overflow-x-auto shrink-0 font-mono text-xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0 mr-1">Berkas:</span>
                <template x-for="(f, idx) in fileList" :key="idx">
                    <button type="button"
                            @click="selectFileIndex(idx)"
                            :class="activeFileIndex === idx ? 'bg-amber-500 text-slate-950 font-black shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold border border-slate-200 dark:border-slate-700'"
                            class="px-3 py-1 rounded-lg transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                        <i data-lucide="file-check" class="w-3.5 h-3.5" x-show="f.name && f.name.toLowerCase().includes('formulir')"></i>
                        <i data-lucide="paperclip" class="w-3.5 h-3.5" x-show="!f.name || !f.name.toLowerCase().includes('formulir')"></i>
                        <span x-text="f.name || ('Berkas ' + (idx + 1))"></span>
                        <span class="text-[9px] uppercase opacity-75 font-mono" x-text="'(' + (f.ext || 'FILE') + ')'"></span>
                    </button>
                </template>
            </div>
        </template>

        {{-- Content Viewer Area --}}
        <div class="relative flex-1 overflow-hidden bg-slate-950 flex items-center justify-center">
            
            {{-- 1. Image Viewer with Zoom, Rotation & Drag-to-Pan (Geser) + Full Scroll --}}
            <template x-if="fileType === 'image'">
                <div class="w-full h-full relative overflow-auto flex justify-center items-start p-3 sm:p-4 select-none"
                     :class="isDragging ? 'cursor-grabbing' : (zoom > 1.0 ? 'cursor-grab' : 'cursor-default')"
                     @wheel.prevent="onWheel($event)"
                     @mousedown="startDrag($event)"
                     @mousemove="onDrag($event)"
                     @mouseup="stopDrag()"
                     @mouseleave="stopDrag()">

                    {{-- Image Canvas Wrapper: Stretch width kanan-kiri dan scroll vertikal jika tinggi melebihi modal --}}
                    <div class="min-w-full min-h-full flex justify-center items-start w-fit mx-auto pb-16">
                        <img :src="fileType === 'image' ? fileUrl : ''" 
                             :alt="fileName"
                             class="select-none pointer-events-none rounded-lg shadow-2xl transition-transform duration-75 block"
                             :class="zoom === 1.0 && rotation === 0 && panX === 0 && panY === 0 ? 'w-full max-w-full h-auto' : 'max-w-none'"
                             :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}) rotate(${rotation}deg); transform-origin: center center;`"
                        />
                    </div>

                    {{-- Floating Inspection Hint Badge --}}
                    <div class="absolute bottom-4 left-4 z-20 pointer-events-none flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/80 backdrop-blur-md border border-slate-800 text-[10px] font-mono text-slate-300">
                        <i data-lucide="move" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>Klik & Tahan Mouse untuk Menggeser Dokumen</span>
                        <span class="text-slate-500">|</span>
                        <span>Scroll Mouse untuk Zoom</span>
                    </div>

                    {{-- Quick Reset Floating Button when panned or zoomed --}}
                    <div x-show="zoom !== 1.0 || panX !== 0 || panY !== 0" x-cloak class="absolute bottom-4 right-4 z-20">
                        <button type="button" @click="resetView()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold font-mono text-[11px] rounded-xl shadow-lg transition flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                            <span>Kembalikan Posisi Normal</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- 2. PDF Viewer (Mozilla PDF.js Canvas Engine - 100% Anti-IDM) --}}
            <template x-if="fileType === 'pdf'">
                <div class="w-full h-full bg-slate-900 relative flex flex-col overflow-hidden select-none" id="dms-pdf-container">
                    {{-- Loading State --}}
                    <div x-show="loadingPdf" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90 backdrop-blur-xs z-10 space-y-3 font-mono">
                        <div class="w-9 h-9 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-bold text-slate-300">Merender pratinjau dokumen PDF live...</p>
                        <p class="text-[11px] text-slate-500">Mozilla PDF.js Engine • Anti-IDM</p>
                    </div>

                    {{-- Error State Fallback --}}
                    <div x-show="!loadingPdf && pdfError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900 z-10 p-6 text-center space-y-3 font-mono">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/30">
                            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                        </div>
                        <p class="text-xs text-slate-300 max-w-sm" x-text="pdfError"></p>
                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" @click="downloadCurrentFile()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Unduh Dokumen</span>
                            </button>
                        </div>
                    </div>

                    {{-- PDF Canvas View Container: with inner wrapper for full height/width scroll without clipping --}}
                    <div id="dms-pdf-scroll-area" class="flex-1 w-full min-h-0 overflow-auto p-4 sm:p-6" x-show="!loadingPdf && !pdfError">
                        <div class="min-w-full min-h-full flex justify-center items-start w-fit mx-auto pb-16">
                            <canvas id="dms-pdf-canvas" class="shrink-0 max-w-none rounded-lg shadow-2xl bg-white border border-slate-700 block"></canvas>
                        </div>
                    </div>

                    {{-- Floating Bottom Page Switcher (for Multi-Page Documents) & Quick Rotate Tools --}}
                    <div x-show="!loadingPdf && !pdfError" x-cloak class="sticky bottom-2 z-20 flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/85 backdrop-blur-md border border-slate-800 text-[11px] font-mono text-slate-300 shadow-xl">
                        <template x-if="pdfTotalPages > 1">
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="pdfPrevPage()" :disabled="pdfPageNum <= 1" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                                    ◀ Hal Sebelumnya
                                </button>
                                <span class="px-2 font-bold text-amber-400" x-text="pdfPageNum + ' / ' + pdfTotalPages"></span>
                                <button type="button" @click="pdfNextPage()" :disabled="pdfPageNum >= pdfTotalPages" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                                    Hal Berikutnya ▶
                                </button>
                                <span class="text-slate-600 mx-0.5">|</span>
                            </div>
                        </template>

                        {{-- Quick Rotation in Bottom Bar --}}
                        <button type="button" @click="rotatePdfCcw()" title="Putar 90° Kiri (Berlawanan Jarum Jam)" class="p-1 hover:bg-slate-800 rounded transition cursor-pointer text-slate-300">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                        <button type="button" @click="rotatePdfCw()" title="Putar 90° Kanan (Searah Jarum Jam)" class="p-1 hover:bg-slate-800 rounded transition cursor-pointer text-slate-300">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.85.99 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        </button>
                        <button type="button" @click="flipPdf180()" title="Balik 180° (Putar Balik Terbalik)" class="px-1.5 py-0.5 hover:bg-slate-800 rounded transition cursor-pointer flex items-center gap-1 font-bold text-[10px] text-amber-400">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                            <span>180°</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- 3. Text / Code / CSV Viewer --}}
            <template x-if="fileType === 'text'">
                <div class="w-full h-full bg-slate-950 p-4 sm:p-6 overflow-auto font-mono text-xs text-slate-200">
                    <div x-show="loadingText" class="w-full h-full flex flex-col items-center justify-center space-y-3">
                        <div class="w-8 h-8 border-3 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs text-slate-400">Membaca teks dokumen...</p>
                    </div>
                    <div x-show="!loadingText && textError" class="w-full h-full flex flex-col items-center justify-center text-center p-6 space-y-2">
                        <i data-lucide="alert-circle" class="w-8 h-8 text-rose-500"></i>
                        <p class="text-xs text-slate-300" x-text="textError"></p>
                    </div>
                    <div x-show="!loadingText && !textError" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 overflow-auto max-h-full whitespace-pre font-mono leading-relaxed select-text shadow-inner">
                        <code x-text="textContent"></code>
                    </div>
                </div>
            </template>

            {{-- 4. Non-previewable / Office / Archive Documents --}}
            <template x-if="fileType === 'other'">
                <div class="text-center p-8 max-w-md space-y-4 font-mono">
                    <div class="w-16 h-16 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center mx-auto border border-blue-500/20">
                        <i data-lucide="file-check" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-base truncate" x-text="fileName"></h4>
                        <p class="text-xs text-slate-400 mt-1">Dokumen ini berformat biner / arsip (<span class="uppercase font-bold text-cyan-400" x-text="fileExt"></span>) dan dapat diunduh untuk dibuka pada aplikasi terkait.</p>
                    </div>
                    <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-xs text-slate-300">
                        <span class="text-emerald-400 font-bold">✓ Integritas Berkas Valid:</span> File tersimpan aman di server penyimpanan arsip.
                    </div>
                    <div class="pt-2">
                        <a :href="fileUrl" target="_blank" download class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg transition cursor-pointer">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Unduh / Buka Dokumen</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer Status Bar --}}
        <div class="px-4 sm:px-6 py-2.5 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400 shrink-0">
            <span class="flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Sistem Pratinjau Terpadu PT Indraco</span>
            </span>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline">Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">F</kbd> untuk Layar Penuh</span>
                <span>Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">ESC</kbd> untuk Menutup</span>
            </div>
        </div>
    </div>
</div>

@include('layouts.partials.file_preview_scripts')
