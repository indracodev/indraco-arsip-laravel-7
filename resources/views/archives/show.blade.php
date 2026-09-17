@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Detail Arsip - ' . $archive->title)

@section('content')
<div class="space-y-[10px]">
    <!-- DELPHI TOP TITLE PANEL & ACTION BAR -->
    <div class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[8px] flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-[8px]">
            <a href="{{ route('archives.index') }}" title="Kembali ke Katalog Berkas" class="p-[4px] bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-[3px] border border-slate-300 dark:border-slate-700 transition">
                <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
            </a>
            <div>
                <h1 class="text-[13px] font-mono font-black uppercase text-slate-900 dark:text-white tracking-wide flex items-center gap-[6px]">
                    <i data-lucide="file-text" class="w-[15px] h-[15px] text-amber-600 dark:text-amber-400"></i>
                    <span>Detail Berkas Arsip</span>
                    <span class="text-amber-600 dark:text-amber-400 font-extrabold">[{{ $archive->box_number ?? 'DRAFT' }}]</span>
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                    {{ $archive->title }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-[6px] font-mono text-[11px]">
            <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" class="px-[10px] py-[3px] bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-[3px] border border-amber-600 shadow-2xs transition flex items-center gap-[4px]">
                <i data-lucide="printer" class="w-[12px] h-[12px]"></i>
                <span>Cetak Label Box</span>
            </a>

            @if($archive->status === 'in_warehouse' && auth()->check() && auth()->user()->isPicDept())
            <a href="{{ route('borrowings.create', ['archive_id' => $archive->id]) }}" class="px-[10px] py-[3px] bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 font-bold rounded-[3px] transition flex items-center gap-[4px]">
                <i data-lucide="file-symlink" class="w-[12px] h-[12px]"></i>
                <span>Pinjam Berkas</span>
            </a>
            @endif

            @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                @if($archive->status === 'in_warehouse' && $archive->retention_expiry_date && \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90)
                <a href="{{ route('destructions.propose', $archive) }}" class="px-[10px] py-[3px] bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold rounded-[3px] transition flex items-center gap-[4px]">
                    <i data-lucide="trash-2" class="w-[12px] h-[12px]"></i>
                    <span>Proses BAP</span>
                </a>
                @endif
            @endif
        </div>
    </div>

    <!-- STATUS WORKFLOW STRIP (TGroupBox) -->
    <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] px-[12px] py-[6px] shadow-xs flex items-center justify-between font-mono text-[11px]">
        <div class="flex items-center gap-[6px]">
            <span class="text-[10px] font-bold uppercase text-slate-500">Status Workflow:</span>
            @if($archive->status === 'draft')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft / Perlu Revisi</span>
            @elseif($archive->status === 'pending_verification')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Menunggu Verifikasi PIC Gudang</span>
            @elseif($archive->status === 'approved_booked')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30">Approved / Booking Tempat Fix</span>
            @elseif($archive->status === 'in_warehouse')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30">Tersimpan di Gudang Arsip</span>
            @elseif($archive->status === 'borrowed')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/30">Sedang Dipinjam</span>
            @elseif($archive->status === 'destroyed')
                <span class="px-[6px] py-[2px] rounded-[3px] text-[10px] font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">Dimusnahkan (BAP Recorded)</span>
            @endif
        </div>

        <div class="text-[10px] text-slate-500">
            Dibuat: {{ $archive->created_at->format('d M Y H:i') }} oleh {{ $archive->creator->name ?? 'User' }}
        </div>
    </div>

    @if($archive->rejection_note)
    <div class="p-[10px] rounded-[4px] bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-[11px] font-mono space-y-[4px]">
        <span class="font-bold block uppercase text-[10px]">Catatan Penolakan PIC Gudang:</span>
        <p>{{ $archive->rejection_note }}</p>
    </div>
    @endif

    <!-- Verification Action Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'pending_verification')
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-[4px] p-[10px] shadow-xs space-y-[8px] font-mono text-[11px]">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-[3px]">
                <i data-lucide="shield-check" class="w-[16px] h-[16px]"></i>
            </span>
            <div>
                <h3 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase">Verifikasi & Generate Nomor Box</h3>
                <p class="text-[10px] text-amber-800 dark:text-amber-300 font-sans">Periksa berkas fisik. Setujui untuk meng-generate Nomor Box otomatis format PT Indraco.</p>
            </div>
        </div>

        <div class="flex items-center gap-[6px] pt-[4px]">
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" onclick="return confirm('Setujui pengajuan arsip dan generate nomor box otomatis?')" class="px-[10px] py-[3px] bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] rounded-[3px] shadow-2xs transition flex items-center gap-[4px]">
                    <i data-lucide="check-circle" class="w-[12px] h-[12px]"></i>
                    <span>Setujui & Generate Box Code</span>
                </button>
            </form>

            <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" type="button" class="px-[10px] py-[3px] bg-rose-500/20 hover:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold text-[11px] rounded-[3px] transition flex items-center gap-[4px]">
                <i data-lucide="x-circle" class="w-[12px] h-[12px]"></i>
                <span>Tolak & Minta Revisi</span>
            </button>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-[16px]">
        <div class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[4px] p-[16px] max-w-md w-full space-y-[10px] shadow-2xl font-mono text-[11px]">
            <h3 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase">Tolak Pengajuan Arsip</h3>
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="space-y-[8px]">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-400 mb-[4px]">Alasan Penolakan</label>
                    <textarea name="rejection_note" rows="3" required class="w-full p-[6px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-[6px] pt-[6px] border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-[10px] py-[3px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-[3px] font-bold">Batal</button>
                    <button type="submit" class="px-[10px] py-[3px] bg-rose-600 text-white rounded-[3px] font-bold">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Check-in Warehouse Placement Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'approved_booked')
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-[4px] p-[10px] shadow-xs space-y-[8px] font-mono text-[11px]">
        <div class="flex items-center gap-[8px]">
            <span class="p-[4px] bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-[3px]">
                <i data-lucide="warehouse" class="w-[16px] h-[16px]"></i>
            </span>
            <div>
                <h3 class="text-[12px] font-bold text-slate-900 dark:text-white uppercase">Check-in Fisik & Penempatan Rak Gudang</h3>
                <p class="text-[10px] text-blue-800 dark:text-blue-300 font-sans">Box Code: <strong class="text-amber-600 dark:text-amber-400 font-mono">{{ $archive->box_number }}</strong>. Tentukan slot rak penyimpanan.</p>
            </div>
        </div>

        <form action="{{ route('archives.checkin', $archive) }}" method="POST" class="space-y-[8px]">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-[8px]">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-300 mb-[2px]">Pilih Slot Rak Gudang <span class="text-rose-500">*</span></label>
                    <select name="warehouse_location_id" required class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] text-slate-900 dark:text-white focus:outline-none focus:border-blue-500">
                        <option value="">-- Pilih Slot Rak Available --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->full_location }} (Terisi {{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-700 dark:text-slate-300 mb-[2px]">Catatan Penerimaan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Fisik diterima segel utuh" class="w-full px-[8px] h-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-[3px] text-[11px] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <button type="submit" class="px-[10px] py-[3px] bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] rounded-[3px] shadow-2xs transition flex items-center gap-[4px]">
                <i data-lucide="check" class="w-[12px] h-[12px]"></i>
                <span>Konfirmasi Check-in Gudang</span>
            </button>
        </form>
    </div>
    @endif

    <!-- MAIN DETAIL GRID (Delphi Inspector) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-[10px]">
        <!-- Left: Information & Metadata (8 Columns) -->
        <div class="lg:col-span-8 space-y-[10px]">
            <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[8px] shadow-xs">
                <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px]">
                    Metadata & Rincian Berkas
                </legend>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-[6px] text-[11px] font-mono">
                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block">Perusahaan:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->company_name ?? 'PT Indraco' }}</span>
                    </div>

                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block">Departemen:</span>
                        <span class="font-bold text-slate-900 dark:text-white block">{{ $archive->department->name }} ({{ $archive->department->code }})</span>
                        @if($archive->subDepartment)
                            <span class="text-amber-600 dark:text-amber-400 font-bold block text-[10px] mt-0.5">Sub: {{ $archive->subDepartment->name }}</span>
                        @endif
                    </div>

                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block mb-[2px]">Jenis Dokumen Fisik:</span>
                        <div class="flex flex-wrap gap-[3px]">
                            @forelse($archive->document_types as $docType)
                                <span class="px-[5px] py-[1px] bg-amber-100 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-300 text-[10px] font-bold font-mono rounded-[2px]">
                                    {{ $docType }}
                                </span>
                            @empty
                                <span class="font-bold text-slate-900 dark:text-white">UMUM</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block">Creator / Pengaju:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->creator->name }}</span>
                    </div>

                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block">Periode Dokumen:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                            @if($archive->period_yy_mm)
                                <span class="text-[10px] bg-amber-500/20 px-[4px] py-[1px] rounded ml-[2px]">({{ $archive->period_yy_mm }})</span>
                            @endif
                        </span>
                    </div>

                    <div class="p-[6px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800">
                        <span class="text-[9px] font-bold text-slate-500 uppercase block">Kondisi Wadah:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->physical_condition }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-[10px] font-mono font-bold uppercase text-slate-500 block mb-[4px]">Deskripsi Berkas & Isi Box:</span>
                    <div class="p-[8px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800 text-[11px] font-mono text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">
                        {{ $archive->content_description }}
                    </div>
                </div>

                <!-- Digital Scans & Attachments -->
                <div class="space-y-[6px] pt-[4px]">
                    <span class="text-[10px] font-mono font-bold uppercase text-slate-500 block">Lampiran & Dokumentasi Scan:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-[6px] font-mono text-[11px]">
                        @if($archive->scan_input_form)
                        <div class="p-[6px] rounded-[3px] bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-[6px]">
                                <i data-lucide="file-check" class="w-[14px] h-[14px] text-amber-600"></i>
                                <span>Scan Formulir Input</span>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_input_form) }}" target="_blank" class="px-[6px] py-[2px] bg-amber-500 text-slate-950 text-[10px] font-black rounded-[2px]">
                                Buka
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_approval_input)
                        <div class="p-[6px] rounded-[3px] bg-blue-500/10 border border-blue-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-[6px]">
                                <i data-lucide="check-square" class="w-[14px] h-[14px] text-blue-600"></i>
                                <span>Scan Approval Input</span>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_approval_input) }}" target="_blank" class="px-[6px] py-[2px] bg-blue-500 text-white text-[10px] font-black rounded-[2px]">
                                Buka
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_extension_form)
                        <div class="p-[6px] rounded-[3px] bg-purple-500/10 border border-purple-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-[6px]">
                                <i data-lucide="clock" class="w-[14px] h-[14px] text-purple-600"></i>
                                <span>Scan Form Perpanjangan</span>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_extension_form) }}" target="_blank" class="px-[6px] py-[2px] bg-purple-500 text-white text-[10px] font-black rounded-[2px]">
                                Buka
                            </a>
                        </div>
                        @endif

                        @if($archive->file_path)
                        <div class="p-[6px] rounded-[3px] bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-[6px]">
                                <i data-lucide="paperclip" class="w-[14px] h-[14px] text-emerald-600"></i>
                                <span>Lampiran Softcopy</span>
                            </div>
                            <a href="{{ asset('storage/' . $archive->file_path) }}" target="_blank" class="px-[6px] py-[2px] bg-emerald-500 text-slate-950 text-[10px] font-black rounded-[2px]">
                                Unduh
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </fieldset>
        </div>

        <!-- Right: Storage & Retention Sidebar (4 Columns) -->
        <div class="lg:col-span-4 space-y-[10px]">
            <!-- Physical Location -->
            <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[6px] shadow-xs">
                <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px] flex items-center gap-[4px]">
                    <i data-lucide="map-pin" class="w-[11px] h-[11px]"></i>
                    <span>Lokasi Fisik Gudang</span>
                </legend>

                @if($archive->location)
                <div class="space-y-[4px] font-mono text-[11px]">
                    <div class="p-[6px] bg-emerald-500/10 border border-emerald-500/30 rounded-[3px]">
                        <span class="text-[9px] text-slate-500 block">Slot Rak:</span>
                        <span class="font-extrabold text-emerald-700 dark:text-emerald-300 text-[13px] block">{{ $archive->location->full_location }}</span>
                    </div>
                    <p class="text-[10px] text-slate-500">
                        {{ $archive->location->warehouse->name ?? '' }} ({{ $archive->location->warehouse->address ?? '' }})
                    </p>
                </div>
                @else
                <div class="p-[8px] bg-slate-50 dark:bg-slate-900 rounded-[3px] border border-slate-200 dark:border-slate-800 text-center text-[10px] font-mono text-slate-500">
                    Belum dilakukan penempatan slot rak gudang.
                </div>
                @endif
            </fieldset>

            <!-- Retention & Expiry Info -->
            <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[6px] shadow-xs">
                <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px] flex items-center gap-[4px]">
                    <i data-lucide="calendar" class="w-[11px] h-[11px]"></i>
                    <span>Masa Simpan (Retention)</span>
                </legend>

                <div class="space-y-[4px] font-mono text-[11px]">
                    <div class="flex justify-between py-[3px] border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Durasi Retensi:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->retention_years }} Tahun</span>
                    </div>

                    <div class="flex justify-between py-[3px] border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500">Tgl Expiry:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">
                            {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }}
                        </span>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>

    <!-- Bottom: Activity Log (Audit Trail) -->
    <fieldset class="border border-slate-300 dark:border-slate-800 rounded-[4px] bg-white dark:bg-slate-950 p-[10px] space-y-[6px] shadow-xs">
        <legend class="px-[6px] text-[10px] font-mono font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-[2px] flex items-center gap-[4px]">
            <i data-lucide="history" class="w-[11px] h-[11px]"></i>
            <span>Riwayat Aktivitas & Audit Trail Berkas</span>
        </legend>

        <div class="space-y-[4px] font-mono text-[11px]">
            @if($archive->entryLogs->isNotEmpty())
                @foreach($archive->entryLogs as $log)
                <div class="p-[6px] rounded-[3px] bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-start gap-[6px]">
                    <div class="p-[3px] bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-[2px] mt-[1px]">
                        <i data-lucide="log-in" class="w-[12px] h-[12px]"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Masuk Gudang - Check-in Slot Rak</span>
                        <p class="text-slate-700 dark:text-slate-300 text-[10px]">{{ $log->notes ?? '-' }}</p>
                        <span class="text-[9px] text-slate-500 block">Diproses oleh: {{ $log->picGudang->name ?? 'PIC Gudang' }} | {{ $log->entry_date->format('d M Y H:i') }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->borrowingLogs->isNotEmpty())
                @foreach($archive->borrowingLogs as $bLog)
                <div class="p-[6px] rounded-[3px] bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-start gap-[6px]">
                    <div class="p-[3px] bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-[2px] mt-[1px]">
                        <i data-lucide="file-symlink" class="w-[12px] h-[12px]"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Peminjaman Dokumen ({{ strtoupper($bLog->status) }})</span>
                        <p class="text-slate-700 dark:text-slate-300 text-[10px]">Tujuan: {{ $bLog->purpose }}</p>
                        <span class="text-[9px] text-slate-500 block">Peminjam: {{ $bLog->borrower->name ?? 'User' }} | Est. Kembali: {{ \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->destructionLog)
                <div class="p-[6px] rounded-[3px] bg-rose-500/10 border border-rose-500/30 flex items-start gap-[6px]">
                    <div class="p-[3px] bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-[2px] mt-[1px]">
                        <i data-lucide="file-x" class="w-[12px] h-[12px]"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Pemusnahan Dokumen (BAP: {{ $archive->destructionLog->bap_number }})</span>
                        <p class="text-slate-700 dark:text-slate-300 text-[10px]">Metode: {{ $archive->destructionLog->method }} | {{ $archive->destructionLog->notes }}</p>
                        <a href="{{ route('destructions.bap', $archive->destructionLog) }}" class="text-[10px] text-amber-600 dark:text-amber-400 font-bold hover:underline block mt-[2px]">
                            Lihat Cetak BAP &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </fieldset>
</div>
@endsection
