@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Katalog & Booking Arsip - DMS PT Indraco')

@section('content')
<div class="space-y-3" x-data="archiveCatalog()">
    @include('archives.partials.catalog_action_toolbar')

    @include('archives.partials.catalog_filter_panel')

    @include('archives.partials.catalog_batch_ribbon')

    @include('archives.partials.catalog_legend_bar')

    <!-- DELPHI TDBGRID SPREADSHEET TABLE CONTAINER -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded shadow-sm relative overflow-hidden font-sans">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-900 dark:to-slate-950 text-[11px] font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 select-none border-b-2 border-slate-300 dark:border-slate-700">
                        <th class="py-2.5 px-3 w-10 text-center border-r border-slate-300 dark:border-slate-700">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-slate-400 dark:border-slate-700 text-amber-500 focus:ring-0">
                        </th>
                        @php
                            $curSort = request('sort', '');
                            $curDir = request('direction', 'desc');
                            $nextDir = $curDir === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'box_number', 'direction' => $curSort === 'box_number' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>NO. BOX ARSIP</span>
                                @if($curSort === 'box_number')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => $curSort === 'title' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>JUDUL BERKAS & DEPT</span>
                                @if($curSort === 'title')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">PERIODE</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">KONDISI FISIK</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">LOKASI RAK GUDANG</th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'retention_expiry_date', 'direction' => $curSort === 'retention_expiry_date' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>MASA SIMPAN (EXPIRY)</span>
                                @if($curSort === 'retention_expiry_date')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $curSort === 'status' ? $nextDir : 'asc']) }}" class="flex items-center gap-1 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                <span>STATUS</span>
                                @if($curSort === 'status')
                                    <span class="text-amber-500 font-black">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-2.5 px-3 border-r border-slate-300 dark:border-slate-700 text-center">
                            <span>BERKAS</span>
                        </th>
                        <th class="py-2.5 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-xs">
                    <!-- LIVE REALTIME PREPENDED ARCHIVES (Point 4: Auto-sorted to top) -->
                    <template x-for="item in liveNewArchives" :key="'live-arc-' + item.id">
                        <tr class="bg-amber-500/10 dark:bg-amber-500/15 hover:bg-amber-500/20 transition duration-300 border-l-4 border-l-amber-500">
                            <td class="py-2.5 px-3 text-center border-r border-slate-200 dark:border-slate-800">
                                <input type="checkbox" :value="item.id" x-model="selected" class="rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0">
                            </td>

                            <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 font-black text-[9px] uppercase tracking-wider animate-pulse">BARU</span>
                                    <span x-text="item.box_number || '- Belum ada code -'"></span>
                                </div>
                            </td>

                            <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                                <div class="space-y-0.5">
                                    <a :href="item.url" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono" x-text="item.title"></a>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono flex-wrap">
                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold" x-text="item.dept_code || 'GEN'"></span>
                                        <template x-if="item.sub_dept_name">
                                            <span class="px-1.5 py-0.2 rounded bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30 font-bold text-[10px]" x-text="item.sub_dept_name"></span>
                                        </template>
                                        <template x-if="item.document_type">
                                            <span class="px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold text-[10px]" x-text="item.document_type"></span>
                                        </template>
                                        <span x-text="'by ' + (item.creator_name || 'User')"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="item.periode_doc || '-'"></td>

                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Baik / Terawat</td>

                            <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="text-slate-600 dark:text-slate-300 font-bold" x-text="item.location || 'Belum Ditentukan'"></span>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap" x-text="item.retention_expiry || '-'"></td>

                            <td class="py-2.5 px-3 font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                      :class="{
                                          'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300': item.status === 'draft',
                                          'bg-amber-500/20 text-amber-800 dark:text-amber-300 border-amber-500/50': item.status === 'pending_verification',
                                          'bg-blue-500/20 text-blue-800 dark:text-blue-300 border-blue-500/50': item.status === 'approved_booked',
                                          'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/50': item.status === 'in_warehouse'
                                      }"
                                      x-text="item.status_label || item.status">
                                </span>
                            </td>

                            <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                                <template x-if="item.has_files">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40">
                                        <i data-lucide="paperclip" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"></i>
                                        <span>Ada</span>
                                    </span>
                                </template>
                                <template x-if="!item.has_files">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-900 text-slate-400 border border-slate-200 dark:border-slate-800">
                                        <span>-</span>
                                    </span>
                                </template>
                            </td>

                            <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                                <a :href="item.url" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </a>
                            </td>
                        </tr>
                    </template>

                    @forelse($archives as $archive)
                    @php
                        $activeBorrowing = $archive->borrowingLogs ? $archive->borrowingLogs->whereIn('status', ['requested', 'dept_approved', 'approved'])->first() : null;
                        $rejectedBorrowing = (!$activeBorrowing && $archive->borrowingLogs) ? $archive->borrowingLogs->where('status', 'rejected')->first() : null;
                        $archiveFiles = [];
                        if ($archive->scan_input_form) {
                            $archiveFiles[] = [
                                'name' => 'Scan Formulir Input',
                                'category' => 'Formulir Pendaftaran Fisik',
                                'url' => app_storage_url($archive->scan_input_form),
                                'stream_url' => app_preview_stream_url($archive->scan_input_form),
                                'raw_path' => $archive->scan_input_form,
                                'filename' => basename($archive->scan_input_form),
                                'ext' => strtolower(pathinfo($archive->scan_input_form, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->file_path) {
                            $archiveFiles[] = [
                                'name' => 'Lampiran Digital Dokumen',
                                'category' => 'Softcopy Dokumen',
                                'url' => app_storage_url($archive->file_path),
                                'stream_url' => app_preview_stream_url($archive->file_path),
                                'raw_path' => $archive->file_path,
                                'filename' => basename($archive->file_path),
                                'ext' => strtolower(pathinfo($archive->file_path, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->scan_approval_input) {
                            $archiveFiles[] = [
                                'name' => 'Scan Approval Input',
                                'category' => 'Bukti Persetujuan PIC',
                                'url' => app_storage_url($archive->scan_approval_input),
                                'stream_url' => app_preview_stream_url($archive->scan_approval_input),
                                'raw_path' => $archive->scan_approval_input,
                                'filename' => basename($archive->scan_approval_input),
                                'ext' => strtolower(pathinfo($archive->scan_approval_input, PATHINFO_EXTENSION)),
                            ];
                        }
                        if ($archive->scan_extension_form) {
                            $archiveFiles[] = [
                                'name' => 'Scan Form Perpanjangan',
                                'category' => 'Perpanjangan Masa Simpan',
                                'url' => app_storage_url($archive->scan_extension_form),
                                'stream_url' => app_preview_stream_url($archive->scan_extension_form),
                                'raw_path' => $archive->scan_extension_form,
                                'filename' => basename($archive->scan_extension_form),
                                'ext' => strtolower(pathinfo($archive->scan_extension_form, PATHINFO_EXTENSION)),
                            ];
                        }
                        // Lampiran berkas formulir permohonan / persetujuan penarikan
                        if ($archive->borrowingLogs && $archive->borrowingLogs->isNotEmpty()) {
                            $existingPaths = array_column($archiveFiles, 'raw_path');
                            foreach ($archive->borrowingLogs as $bLogItem) {
                                if (!empty($bLogItem->effective_approval_file) && !in_array($bLogItem->effective_approval_file, $existingPaths)) {
                                    $existingPaths[] = $bLogItem->effective_approval_file;
                                    $archiveFiles[] = [
                                        'name' => 'Scan Formulir Approval Penarikan',
                                        'category' => 'Formulir Persetujuan Penarikan',
                                        'url' => app_storage_url($bLogItem->effective_approval_file),
                                        'stream_url' => app_preview_stream_url($bLogItem->effective_approval_file),
                                        'raw_path' => $bLogItem->effective_approval_file,
                                        'filename' => basename($bLogItem->effective_approval_file),
                                        'ext' => strtolower(pathinfo($bLogItem->effective_approval_file, PATHINFO_EXTENSION)),
                                    ];
                                    break;
                                }
                            }
                        }
                        $hasFiles = count($archiveFiles) > 0;

                        // Determine Global Workflow Row Styles & Badges
                        $rowColorClass = '';
                        $isNewSubmission = false;

                        if ($archive->status === 'draft') {
                            if (!empty($archive->rejection_note)) {
                                $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.08] dark:bg-rose-500/15 hover:bg-rose-500/20';
                            } else {
                                $rowColorClass = 'border-l-2 border-l-slate-300 dark:border-l-slate-700 bg-slate-50/40 dark:bg-slate-900/20 hover:bg-slate-100/60';
                            }
                        } elseif ($archive->status === 'pending_verification') {
                            $rowColorClass = 'border-l-4 border-l-amber-500 bg-amber-500/[0.07] dark:bg-amber-500/15 hover:bg-amber-500/20';
                            $isNewSubmission = true;
                        } elseif ($activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out'])) {
                            $rowColorClass = 'border-l-4 border-l-purple-500 bg-purple-500/[0.07] dark:bg-purple-500/15 hover:bg-purple-500/20';
                        } elseif ($rejectedBorrowing && $archive->status === 'in_warehouse') {
                            $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.05] dark:bg-rose-500/10 hover:bg-rose-500/15';
                        } elseif ($archive->status === 'pending_extension') {
                            $rowColorClass = 'border-l-4 border-l-blue-500 bg-blue-500/[0.07] dark:bg-blue-500/15 hover:bg-blue-500/20';
                        } elseif (in_array($archive->status, ['pending_destruction', 'destroyed'])) {
                            $rowColorClass = 'border-l-4 border-l-rose-500 bg-rose-500/[0.07] dark:bg-rose-500/15 hover:bg-rose-500/20';
                        } elseif ($archive->status === 'in_warehouse') {
                            $rowColorClass = 'border-l-2 border-l-emerald-500/50 hover:bg-emerald-500/5';
                        } else {
                            $rowColorClass = 'hover:bg-slate-50 dark:hover:bg-slate-900/30';
                        }
                    @endphp
                    <tr class="transition duration-150 {{ $rowColorClass }}">
                        <td class="py-2.5 px-3 text-center border-r border-slate-200 dark:border-slate-800">
                            @php
                                $isPulledOrActive = $activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out']);
                            @endphp
                            <input type="checkbox" :value="{{ $archive->id }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0"
                                   @if($isPulledOrActive) title="Arsip sedang ditarik / keluar dari gudang (tidak dapat ditarik kembali)" @endif>
                        </td>

                        <td class="py-2.5 px-3 font-mono text-amber-700 dark:text-amber-400 font-extrabold border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($isNewSubmission)
                                    <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 font-black text-[9px] uppercase tracking-wider animate-pulse shadow-xs" title="Pengajuan Arsip Baru Menunggu Verifikasi">BARU</span>
                                @elseif($activeBorrowing || in_array($archive->status, ['borrowed', 'taken', 'out']))
                                    <span class="px-1.5 py-0.2 rounded bg-purple-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Berkas Sedang Ditarik">DITARIK</span>
                                @elseif($rejectedBorrowing && $archive->status === 'in_warehouse')
                                    <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[9px] uppercase tracking-wider shadow-xs animate-pulse" title="Pengajuan Penarikan Berkas Ditolak Gudang">TOLAK TARIK</span>
                                @elseif($archive->status === 'pending_extension')
                                    <span class="px-1.5 py-0.2 rounded bg-blue-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Menunggu Konfirmasi Perpanjangan">PERPANJANG</span>
                                @elseif(in_array($archive->status, ['pending_destruction', 'destroyed']))
                                    <span class="px-1.5 py-0.2 rounded bg-rose-500 text-white font-black text-[9px] uppercase tracking-wider shadow-xs" title="Pemusnahan Berkas">MUSNAH</span>
                                @endif

                                @if($archive->box_number)
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-500"></i>
                                        <span>{{ $archive->box_number }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 italic font-normal text-[11px]">- Belum ada box code -</span>
                                @endif
                            </div>
                        </td>

                        <td class="py-2.5 px-3 border-r border-slate-200 dark:border-slate-800">
                            <div class="space-y-0.5">
                                <a href="{{ route('archives.show', $archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition block font-mono">
                                    {{ $archive->title }}
                                </a>
                                @if($archive->status === 'draft' && !empty($archive->rejection_note))
                                    <div class="mt-1 p-1.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-[10px] font-mono flex items-start gap-1.5 shadow-2xs">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div>
                                            <span class="font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Catatan Revisi Gudang:</span>
                                            <span class="block text-slate-700 dark:text-slate-300">{{ $archive->rejection_note }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($rejectedBorrowing && $archive->status === 'in_warehouse')
                                    <div class="mt-1 p-1.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-[10px] font-mono flex items-start gap-1.5 shadow-2xs">
                                        <i data-lucide="ban" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div>
                                            <span class="font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Penarikan Ditolak Gudang:</span>
                                            <span class="block text-slate-700 dark:text-slate-300">{{ $rejectedBorrowing->approval_notes ?: 'Formulir atau berkas permohonan penarikan tidak memenuhi syarat.' }}</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono flex-wrap">
                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold">
                                        {{ $archive->department->code ?? 'GEN' }}
                                    </span>
                                    @if($archive->subDepartment)
                                        <span class="px-1.5 py-0.2 rounded bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30 font-bold text-[10px]" title="Sub-Departemen: {{ $archive->subDepartment->name }}">
                                            {{ $archive->subDepartment->code ?? $archive->subDepartment->name }}
                                        </span>
                                    @endif
                                    @if($archive->document_type)
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold text-[10px]" title="Tipe Dokumen">
                                            {{ $archive->document_type }}
                                        </span>
                                    @endif
                                    <span>by {{ $archive->creator->name ?? 'User' }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->effective_periode }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            {{ $archive->physical_condition }}
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if(in_array($archive->status, ['taken', 'borrowed', 'out']))
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 font-bold text-left">
                                    <i data-lucide="log-out" class="w-3.5 h-3.5 text-purple-500 shrink-0"></i>
                                    <span>- (Sudah Ditarik)</span>
                                </div>
                            @elseif($archive->location)
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                <button 
                                    type="button"
                                    @click="openQuickSlotModal({
                                        id: {{ $archive->id }},
                                        title: {{ json_encode($archive->title) }},
                                        box_number: {{ json_encode($archive->box_number ?: 'Belum ada code') }},
                                        department_id: {{ $archive->department_id ?? 0 }},
                                        department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        department_name: {{ json_encode($archive->department->name ?? '') }}
                                    })"
                                    class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold hover:underline cursor-pointer text-left"
                                    title="Klik untuk ubah / lihat penempatan slot rak 2D"
                                >
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $archive->display_location }}</span>
                                </button>
                                @else
                                <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold text-left">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <span>{{ $archive->display_location }}</span>
                                </div>
                                @endif
                            @else
                                @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                                    @if(in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension']))
                                    <button 
                                        type="button" 
                                        @click="openQuickSlotModal({
                                            id: {{ $archive->id }},
                                            title: {{ json_encode($archive->title) }},
                                            box_number: {{ json_encode($archive->box_number ?: 'Belum ada code') }},
                                            department_id: {{ $archive->department_id ?? 0 }},
                                            department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                            department_name: {{ json_encode($archive->department->name ?? '') }}
                                        })"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/40 animate-pulse hover:bg-rose-500/25 transition shadow-xs cursor-pointer" 
                                        title="Lokasi belum ditentukan! Klik untuk buka Alokasi Slot Rak 2D Cepat"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                        <span class="underline decoration-dotted underline-offset-2">Belum Ditentukan</span>
                                        <i data-lucide="map-pin" class="w-3 h-3 text-rose-500"></i>
                                    </button>
                                    @else
                                    <button 
                                        type="button" 
                                        disabled
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-200 dark:border-slate-700 opacity-60 cursor-not-allowed select-none" 
                                        title="Lokasi belum dapat ditentukan: Harap verifikasi & setujui pengajuan arsip terlebih dahulu pada tombol Antrean Verifikasi"
                                    >
                                        <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                                        <span>Belum Ditentukan</span>
                                    </button>
                                    @endif
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30 select-none" title="Lokasi rak gudang belum ditentukan">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>Belum Ditentukan</span>
                                    <i data-lucide="map-pin" class="w-3 h-3 text-rose-400"></i>
                                </span>
                                @endif
                            @endif
                        </td>

                        <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($archive->retention_expiry_date)
                                @php
                                    $expiryEndOfMonth = \Carbon\Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
                                    $isExpired = now()->gt($expiryEndOfMonth);
                                    $daysToExpiry = \Carbon\Carbon::today()->diffInDays($expiryEndOfMonth, false);
                                    $isSameMonth = !$isExpired && ($daysToExpiry <= 30);
                                    $isNear = !$isExpired && ($daysToExpiry <= 90);
                                    $approvedExt = $archive->latest_approved_extension;
                                @endphp
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($isExpired)
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 font-extrabold animate-warning-blink">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                                                <span>{{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}</span>
                                                <span class="text-[10px] font-bold text-rose-700 dark:text-rose-300">(Lewat {{ abs($daysToExpiry) }} Hari)</span>
                                            </div>
                                        @elseif($isSameMonth)
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 font-bold">
                                                <i data-lucide="clock" class="w-3 h-3 text-amber-500 shrink-0"></i>
                                                <span>{{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}</span>
                                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-300">(H-{{ $daysToExpiry }})</span>
                                            </div>
                                        @elseif($isNear)
                                            <span class="text-amber-600 dark:text-amber-400 font-semibold">
                                                {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}
                                                ({{ $archive->retention_display }})
                                            </span>
                                        @else
                                            <span class="text-slate-700 dark:text-slate-300 font-medium">
                                                {{ \Carbon\Carbon::parse($archive->retention_expiry_date)->format('M Y') }}
                                                ({{ $archive->retention_display }})
                                            </span>
                                        @endif

                                        @if($approvedExt)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30" title="Masa simpan telah diperpanjang {{ $approvedExt->duration_label }}">
                                                <i data-lucide="clock" class="w-2.5 h-2.5 text-purple-500"></i>
                                                <span>{{ $approvedExt->duration_label }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 font-mono border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if(auth()->user()->isSuperAdmin())
                                <button 
                                    type="button" 
                                    @click="openSuperAdminStatusModal({
                                        id: {{ $archive->id }},
                                        box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                        raw_box_number: {{ json_encode($archive->box_number) }},
                                        title: {{ json_encode($archive->title) }},
                                        department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                        department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        status: {{ json_encode($archive->status) }},
                                        status_label: {{ json_encode($archive->status_label ?? $archive->status) }},
                                        location: {{ json_encode($archive->display_location) }},
                                        has_scan_input: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
                                        has_file_path: {{ !empty($archive->file_path) ? 'true' : 'false' }},
                                        has_approval: {{ !empty($archive->scan_approval_input) ? 'true' : 'false' }},
                                        files: {{ json_encode($archiveFiles) }},
                                        scan_input_form_url: {{ json_encode(app_storage_url($archive->scan_input_form)) }},
                                        file_path_url: {{ json_encode(app_storage_url($archive->file_path)) }}
                                    })"
                                    class="px-2 py-0.5 rounded text-[10px] font-bold inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group border"
                                    :class="{
                                        'bg-slate-200 hover:bg-slate-300 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': '{{ $archive->status }}' === 'draft',
                                        'bg-amber-500/20 hover:bg-amber-500/35 text-amber-800 dark:text-amber-300 border-amber-500/50': '{{ $archive->status }}' === 'pending_verification',
                                        'bg-blue-500/20 hover:bg-blue-500/35 text-blue-800 dark:text-blue-300 border-blue-500/50': '{{ $archive->status }}' === 'approved_booked',
                                        'bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-800 dark:text-emerald-300 border-emerald-500/50': '{{ $archive->status }}' === 'in_warehouse',
                                        'bg-purple-500/20 hover:bg-purple-500/35 text-purple-800 dark:text-purple-300 border-purple-500/50': '{{ $archive->status }}' === 'pending_extension',
                                        'bg-indigo-500/20 hover:bg-indigo-500/35 text-indigo-800 dark:text-indigo-300 border-indigo-500/50': '{{ $archive->status }}' === 'borrowed' || '{{ $archive->status }}' === 'taken',
                                        'bg-orange-500/20 hover:bg-orange-500/35 text-orange-800 dark:text-orange-300 border-orange-500/50': '{{ $archive->status }}' === 'pending_destruction',
                                        'bg-rose-500/20 hover:bg-rose-500/35 text-rose-800 dark:text-rose-300 border-rose-500/50': '{{ $archive->status }}' === 'destroyed'
                                    }"
                                    title="Super Admin: Klik untuk ubah status workflow bebas"
                                >
                                    @if($archive->status === 'draft')
                                        <i data-lucide="edit-3" class="w-3 h-3 text-slate-600 dark:text-slate-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Draft</span>
                                    @elseif($archive->status === 'pending_verification')
                                        <i data-lucide="shield-check" class="w-3 h-3 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Antrean Verifikasi</span>
                                    @elseif($archive->status === 'approved_booked')
                                        <i data-lucide="check" class="w-3 h-3 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Approved / Booked</span>
                                    @elseif($archive->status === 'in_warehouse')
                                        <i data-lucide="archive" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Di Gudang</span>
                                    @elseif($archive->status === 'pending_extension')
                                        <i data-lucide="clock" class="w-3 h-3 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Pengajuan Perpanjangan</span>
                                    @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                        <i data-lucide="log-out" class="w-3 h-3 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Keluar (Out)</span>
                                    @elseif($archive->status === 'pending_destruction')
                                        <i data-lucide="shield-alert" class="w-3 h-3 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">{{ $archive->status_label }}</span>
                                    @elseif($archive->status === 'destroyed')
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform"></i>
                                        <span class="underline decoration-dotted underline-offset-2">Dimusnahkan</span>
                                    @endif
                                    <i data-lucide="sliders" class="w-2.5 h-2.5 opacity-60 ml-0.5 text-amber-500"></i>
                                </button>
                            @else
                                @if($archive->status === 'draft')
                                    @if(!empty($archive->rejection_note))
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-300 dark:border-rose-700 inline-flex items-center gap-1 shadow-xs" title="Catatan Revisi Gudang: {{ $archive->rejection_note }}">
                                            <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600 dark:text-rose-400"></i>
                                            <span>Perlu Revisi</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">Draft</span>
                                    @endif
                                @elseif($archive->status === 'pending_verification')
                                    @if(auth()->user()->isPicGudang())
                                        <button 
                                            type="button" 
                                            @click="openVerifyModal({
                                                id: {{ $archive->id }},
                                                title: {{ json_encode($archive->title) }},
                                                department_id: {{ $archive->department_id ?? 0 }},
                                                department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                creator: {{ json_encode($archive->creator->name ?? 'User') }},
                                                periode: {{ json_encode($archive->effective_periode) }},
                                                condition: {{ json_encode($archive->physical_condition) }},
                                                files: {{ json_encode($archiveFiles) }},
                                                has_files: {{ $hasFiles ? 'true' : 'false' }},
                                                edit_url: {{ json_encode(route('archives.edit', ['archive' => $archive->id])) }}
                                            })"
                                            class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 hover:bg-amber-500/35 text-amber-800 dark:text-amber-300 border border-amber-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk verifikasi pengajuan & generate Nomor Box Arsip"
                                        >
                                            <i data-lucide="shield-check" class="w-3 h-3 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">Antrean Verifikasi</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/40">Antrean Verifikasi</span>
                                    @endif
                                @elseif($archive->status === 'approved_booked')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/40">Approved / Booked</span>
                                @elseif($archive->status === 'in_warehouse')
                                    @if(auth()->user()->isPicGudang())
                                        <button 
                                            type="button" 
                                            @click="openCheckoutModal({
                                                id: {{ $archive->id }},
                                                box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                                title: {{ json_encode($archive->title) }},
                                                department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                location: {{ json_encode($archive->display_location) }}
                                            })"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 hover:bg-emerald-500/35 text-emerald-800 dark:text-emerald-300 border border-emerald-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk proses pengeluaran berkas (Out / Keluar)"
                                        >
                                            <i data-lucide="log-out" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">Di Gudang</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/40">Di Gudang</span>
                                    @endif
                                @elseif($archive->status === 'pending_extension')
                                    <a href="{{ route('destructions.index', ['view' => 'extensions']) }}" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40 inline-flex items-center gap-1 hover:bg-purple-500/20 transition" title="Lihat di Antrean Perpanjangan Gudang">
                                        <i data-lucide="clock" class="w-3 h-3 text-purple-500"></i>
                                        <span>Pengajuan Perpanjangan</span>
                                    </a>
                                @elseif($archive->status === 'borrowed' || $archive->status === 'taken')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/40 inline-flex items-center gap-1">
                                        <i data-lucide="log-out" class="w-3 h-3 text-purple-500"></i>
                                        <span>Keluar (Out)</span>
                                    </span>
                                @elseif($archive->status === 'pending_destruction')
                                    @if(auth()->user()->isPicGudang())
                                            <button 
                                                type="button" 
                                                @click="openSuperAdminStatusModal({
                                                    id: {{ $archive->id }},
                                                    box_number: {{ json_encode($archive->box_number ?: 'Pending') }},
                                                    raw_box_number: {{ json_encode($archive->box_number) }},
                                                    title: {{ json_encode($archive->title) }},
                                                    department: {{ json_encode($archive->department->name ?? 'Dept') }},
                                                    department_code: {{ json_encode($archive->department->code ?? 'GEN') }},
                                                    status: {{ json_encode($archive->status) }},
                                                    status_label: {{ json_encode($archive->status_label ?? $archive->status) }},
                                                    location: {{ json_encode($archive->display_location) }},
                                                    has_scan_input: {{ !empty($archive->scan_input_form) ? 'true' : 'false' }},
                                                    has_file_path: {{ !empty($archive->file_path) ? 'true' : 'false' }},
                                                    has_approval: {{ !empty($archive->scan_approval_input) ? 'true' : 'false' }}
                                                })"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-500/20 hover:bg-orange-500/35 text-orange-800 dark:text-orange-300 border border-orange-500/50 inline-flex items-center gap-1 transition shadow-xs cursor-pointer hover:scale-105 group"
                                            title="Klik untuk proses / ubah status Pengajuan Pemusnahan"
                                        >
                                            <i data-lucide="shield-alert" class="w-3 h-3 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform"></i>
                                            <span class="underline decoration-dotted underline-offset-2">{{ $archive->status_label }}</span>
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-500/10 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300 border border-orange-500/40 inline-flex items-center gap-1">
                                            <i data-lucide="shield-alert" class="w-3 h-3 text-orange-500"></i>
                                            <span>{{ $archive->status_label }}</span>
                                        </span>
                                    @endif
                                @elseif($archive->status === 'destroyed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/40">Dimusnahkan</span>
                                @endif
                            @endif
                        </td>

                        <!-- BERKAS DIGITAL & SCAN PREVIEW COLUMN -->
                        <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            @if($hasFiles)
                                <button 
                                    type="button" 
                                    @click="dmsPreviewFile({
                                        box_number: {{ json_encode($archive->box_number ?: 'DRAFT-BOX') }},
                                        title: {{ json_encode($archive->title) }},
                                        department: {{ json_encode($archive->department->code ?? 'GEN') }},
                                        files: {{ json_encode($archiveFiles) }}
                                    })"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40 transition shadow-xs cursor-pointer group"
                                    title="Klik untuk preview berkas digital & scan formulir ({{ count($archiveFiles) }} berkas)"
                                >
                                    <i data-lucide="paperclip" class="w-3 h-3 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                    <span class="underline decoration-dotted underline-offset-2">Ada</span>
                                    <span class="text-[9px] px-1 py-0.2 rounded-full bg-emerald-600 text-white font-black leading-none ml-0.5">{{ count($archiveFiles) }}</span>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 select-none">
                                    <i data-lucide="minus-circle" class="w-3 h-3 text-slate-400 dark:text-slate-600"></i>
                                    <span>Tidak Ada</span>
                                </span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $activeBorrowing)
                                    <button type="button" 
                                            @click="dispatchModalLog = {
                                                id: {{ $activeBorrowing->id }},
                                                archive_title: {{ json_encode($archive->title) }},
                                                box_number: {{ json_encode($archive->box_number ?: 'DRAFT-BOX') }},
                                                location: {{ json_encode($archive->display_location) }},
                                                borrower_name: {{ json_encode($activeBorrowing->borrower->name ?? 'User') }},
                                                borrower_dept: {{ json_encode($activeBorrowing->borrower->department->name ?? 'Dept') }},
                                                purpose: {{ json_encode($activeBorrowing->purpose) }},
                                                expected_return_date: {{ json_encode($activeBorrowing->expected_return_date ? \Carbon\Carbon::parse($activeBorrowing->expected_return_date)->format('d/m/Y') : 'Hanya Diambil (Permanen)') }},
                                                approval_url: {{ json_encode($activeBorrowing->effective_approval_file ? app_storage_url($activeBorrowing->effective_approval_file) : null) }},
                                                approval_stream: {{ json_encode($activeBorrowing->effective_approval_file ? app_preview_stream_url($activeBorrowing->effective_approval_file) : null) }},
                                                approval_raw: {{ json_encode($activeBorrowing->effective_approval_file) }},
                                                approval_ext: {{ json_encode($activeBorrowing->effective_approval_file ? strtolower(pathinfo($activeBorrowing->effective_approval_file, PATHINFO_EXTENSION)) : 'pdf') }}
                                            }"
                                            title="Keluarkan Berkas Fisik Berdasarkan Ajuan Penarikan PIC Dept" 
                                            class="px-2.5 py-1 rounded bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white border border-purple-400 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-md">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-white"></i>
                                        <span>Keluarkan Arsip</span>
                                    </button>
                                @endif

                                {{-- Tombol Penarikan Box di Gudang (Hanya jika tersimpan di gudang & tidak sedang ditarik) --}}
                                @if(!$activeBorrowing && $archive->status === 'in_warehouse' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id)))
                                    @if($rejectedBorrowing)
                                    <a href="{{ route('borrowings.create', array_merge(['archive_ids' => $archive->id, 'from' => 'archives'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Penarikan sebelumnya ditolak. Klik untuk ajukan penarikan ulang dengan melengkapi syarat">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3 text-slate-950"></i>
                                        <span>Tarik Ulang</span>
                                    </a>
                                    @else
                                    <a href="{{ route('borrowings.create', array_merge(['archive_ids' => $archive->id, 'from' => 'archives'], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-purple-500/15 hover:bg-purple-500/25 text-purple-700 dark:text-purple-300 border border-purple-500/40 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="Ajukan Penarikan Box Ini">
                                        <i data-lucide="corner-up-right" class="w-3 h-3 text-purple-600 dark:text-purple-400"></i>
                                        <span>Tarik</span>
                                    </a>
                                    @endif
                                @elseif($activeBorrowing && (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id))
                                    <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold text-[10px] inline-flex items-center gap-1 select-none" title="Sedang dalam proses penarikan">
                                        <i data-lucide="clock" class="w-2.5 h-2.5 text-purple-500"></i>
                                        <span>Sedang Ditarik</span>
                                    </span>
                                @endif

                                @if(!auth()->user()->isPicGudang() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || (auth()->user()->isPicDept() && (int)$archive->department_id === (int)auth()->user()->department_id && $archive->status === 'draft')))
                                <a href="{{ route('archives.edit', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded {{ !empty($archive->rejection_note) ? 'bg-rose-600 hover:bg-rose-500 text-white border border-rose-700 animate-pulse' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 border border-amber-600' }} font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs" title="{{ !empty($archive->rejection_note) ? 'Perbaiki Pengajuan Sesuai Catatan Revisi Gudang' : 'Edit Data Berkas Arsip' }}">
                                    <i data-lucide="{{ !empty($archive->rejection_note) ? 'alert-circle' : 'edit-3' }}" class="w-3 h-3"></i>
                                    <span>{{ !empty($archive->rejection_note) ? 'Revisi & Ajukan' : 'Edit' }}</span>
                                </a>

                                {{-- Tombol Hapus Draft Arsip (Poin 3) --}}
                                <form action="{{ route('archives.destroy', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            data-confirm="Apakah Anda yakin ingin menghapus data draft arsip [{{ $archive->title }}]?"
                                            data-confirm-title="Hapus Draft Berkas Arsip"
                                            data-confirm-type="danger"
                                            data-confirm-btn="Ya, Hapus"
                                            class="px-2 py-0.5 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 font-bold text-[11px] transition inline-flex items-center gap-1 shadow-xs cursor-pointer" title="Hapus Draft Berkas Arsip">
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-500"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                                @endif

                                @if(!auth()->user()->isPicDept())
                                    @php
                                        $hasRackLocation = !empty($archive->warehouse_location_id);
                                        $canPrintLabel = in_array($archive->status, ['approved_booked', 'in_warehouse', 'pending_extension']) && $hasRackLocation;
                                    @endphp
                                    @if(!$canPrintLabel)
                                        <button 
                                            type="button" 
                                            disabled 
                                            title="{{ !$hasRackLocation ? 'Label dinonaktifkan: Lokasi rak gudang belum ditentukan (tahap verifikasi/approval atau belum check-in ke rak)' : 'Label dinonaktifkan: Hanya dapat dicetak untuk arsip yang tersimpan di rak gudang atau perpanjangan (status saat ini: ' . $archive->status_label . ')' }}" 
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-800 text-[11px] font-bold inline-flex items-center gap-1 shadow-2xs opacity-40 cursor-not-allowed"
                                        >
                                            <i data-lucide="printer" class="w-3 h-3 text-slate-400 dark:text-slate-600"></i>
                                            <span>Label</span>
                                        </button>
                                    @elseif($hasFiles)
                                        <button type="button" @click="window.openLabelModal({{ $archive->id }})" title="Cetak Label Box Container" class="px-2 py-0.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer">
                                            <i data-lucide="printer" class="w-3 h-3 text-amber-500"></i>
                                            <span>Label</span>
                                        </button>
                                    @else
                                        <button 
                                            type="button" 
                                            @click="openIncompleteFileAlert({
                                                title: {{ json_encode($archive->title) }},
                                                box_number: {{ json_encode($archive->box_number ?: 'Draft Pengajuan') }},
                                                edit_url: {{ json_encode(route('archives.edit', ['archive' => $archive->id])) }}
                                            })" 
                                            title="Label Tidak Dapat Dicetak: Berkas belum lengkap!" 
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-300 dark:border-slate-700 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer hover:bg-rose-500/10 hover:text-rose-600 hover:border-rose-500/30 opacity-70"
                                        >
                                            <i data-lucide="printer" class="w-3 h-3 text-slate-400 dark:text-slate-500"></i>
                                            <span>Label</span>
                                        </button>
                                    @endif
                                @endif
                                <a href="{{ route('archives.show', array_merge(['archive' => $archive->id], request()->has('embed') ? ['embed' => 1] : [])) }}" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-amber-500 text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs">
                                    <span>Detail</span>
                                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-10 text-center font-mono text-slate-500 dark:text-slate-400 space-y-2">
                            <i data-lucide="folder-search" class="w-10 h-10 mx-auto text-slate-400 dark:text-slate-600"></i>
                            <p class="text-xs font-bold">Tidak ada berkas arsip yang ditemukan berdasarkan kriteria pencarian ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- DELPHI DBNAVIGATOR / TSTATUSBAR PAGINATION FOOTER -->
        <div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-300 dark:border-slate-800 px-3 py-2 flex flex-col sm:flex-row items-center justify-between gap-2 font-mono text-xs text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2 text-[11px]">
                <i data-lucide="database" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Menampilkan <strong>{{ $archives->firstItem() ?? 0 }}</strong> - <strong>{{ $archives->lastItem() ?? 0 }}</strong> dari <strong>{{ $archives->total() }}</strong> total berkas arsip</span>
            </div>

            <div>
                {{ $archives->links('vendor.pagination.tailwind') }}
            </div>
        </div>
    </div>


    <!-- MODULAR MODAL PARTIALS (CLEAN ARCHITECTURE) -->
    @include('archives.partials.modals.dispatch_modal')
    @include('archives.partials.modals.quick_slot_modal')
    @include('archives.partials.modals.incomplete_modal')
    @include('archives.partials.modals.verify_modal')
    @include('archives.partials.modals.checkout_modal')
    @include('archives.partials.modals.superadmin_status_modal')
    @include('archives.partials.modals.import_modal')
</div>
@endsection

@push('scripts')
@include('archives.partials.catalog_scripts')
@endpush
