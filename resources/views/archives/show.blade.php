@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Detail Arsip - ' . $archive->title)

@section('content')
<div class="w-full space-y-6">
    <!-- Navigation & Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('archives.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline inline-flex items-center gap-1 mb-2">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Katalog
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="file-text" class="w-7 h-7 text-amber-600 dark:text-amber-400"></i>
                Detail Berkas Arsip
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm font-medium">Nomor Box: <span class="font-mono text-amber-600 dark:text-amber-400 font-bold">{{ $archive->box_number ?? 'Belum ter-generate (Pending Verification)' }}</span></p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('archives.print_sticker', $archive) }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-md transition flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Stiker Box Label
            </a>

            @if($archive->status === 'in_warehouse')
            <a href="{{ route('borrowings.create', ['archive_id' => $archive->id]) }}" class="px-4 py-2.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30 text-xs font-bold transition flex items-center gap-2">
                <i data-lucide="file-symlink" class="w-4 h-4"></i> Ajukan Pinjam Berkas
            </a>
            @endif

            @if(auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin())
                @if($archive->status === 'in_warehouse' && $archive->retention_expiry_date && \Carbon\Carbon::parse($archive->retention_expiry_date)->diffInDays(now()) <= 90)
                <a href="{{ route('destructions.propose', $archive) }}" class="px-4 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30 text-xs font-bold transition flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Proses BAP Pemusnahan
                </a>
                @endif
            @endif
        </div>
    </div>

    <!-- Status Tracker & Rejection Alert -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status Workflow Berkas</span>
            <div>
                @if($archive->status === 'draft')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft / Perlu Revisi</span>
                @elseif($archive->status === 'pending_verification')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">Menunggu Verifikasi PIC Gudang</span>
                @elseif($archive->status === 'approved_booked')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30">Approved / Booking Tempat Fix</span>
                @elseif($archive->status === 'in_warehouse')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30">Tersimpan di Gudang Arsip</span>
                @elseif($archive->status === 'borrowed')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/30">Sedang Dipinjam</span>
                @elseif($archive->status === 'destroyed')
                    <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">Telah Dimusnahkan (BAP Recorded)</span>
                @endif
            </div>
        </div>

        @if($archive->rejection_note)
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            <span class="font-bold block">Catatan Penolakan PIC Gudang:</span>
            <p>{{ $archive->rejection_note }}</p>
        </div>
        @endif
    </div>

    <!-- Verification Action Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'pending_verification')
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hub Verifikasi & Penomoran Box (PIC Gudang)</h3>
                <p class="text-xs text-amber-800 dark:text-amber-200/80 font-medium">Periksa kesesuaian berkas fisik. Klik Setujui untuk melakukan generate Nomor Box otomatis sesuai format custom PT Indraco.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" onclick="return confirm('Setujui pengajuan arsip dan generate nomor box otomatis?')" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Setujui & Generate Box Code
                </button>
            </form>

            <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" type="button" class="px-5 py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-bold text-xs rounded-xl transition flex items-center gap-2">
                <i data-lucide="x-circle" class="w-4 h-4"></i> Tolak & Minta Revisi
            </button>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tolak Pengajuan Arsip</h3>
            <form action="{{ route('archives.verify', $archive) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Alasan / Catatan Penolakan</label>
                    <textarea name="rejection_note" rows="3" required class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 font-medium"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-400 text-xs rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white text-xs rounded-xl font-black">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Check-in Warehouse Placement Panel (PIC Gudang Only) -->
    @if((auth()->user()->isPicGudang() || auth()->user()->isSuperAdmin()) && $archive->status === 'approved_booked')
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-2xl">
                <i data-lucide="warehouse" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Check-in Fisik & Penempatan Rak Gudang</h3>
                <p class="text-xs text-blue-800 dark:text-blue-200/80 font-medium">Pengajuan telah disetujui dengan Box Code <span class="font-mono text-amber-600 dark:text-amber-400 font-bold">{{ $archive->box_number }}</span>. Tentukan lokasi fisik penyimpanan rak gudang.</p>
            </div>
        </div>

        <form action="{{ route('archives.checkin', $archive) }}" method="POST" class="space-y-4 pt-2">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Pilih Lokasi Rak Gudang <span class="text-rose-500">*</span></label>
                    <select name="warehouse_location_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                        <option value="">-- Pilih Slot Rak Gudang Available --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->full_location }} (Terisi {{ $loc->current_box_count }}/{{ $loc->box_capacity }} Box)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Catatan Penerimaan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Fisik diterima segel utuh" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 font-medium">
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i> Konfirmasi Check-in Ke Gudang
            </button>
        </form>
    </div>
    @endif

    <!-- Main Detail Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Information Cards -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Metadata Card -->
            <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block mb-1">Judul Berkas Dokumen</span>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $archive->title }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Perusahaan / Entitas:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->company_name ?? 'PT Indraco' }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Departemen Pemilik:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->department->name }} ({{ $archive->department->code }})</span>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Jenis Dokumen:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->document_type ?? 'UMUM' }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Pengaju / Creator:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->creator->name }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Periode Dokumen:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400 text-sm">
                            {{ $archive->period_text ?? $archive->period_start_date->format('M Y') }}
                            @if($archive->period_yy_mm)
                                <span class="font-mono text-xs bg-amber-500/20 px-1.5 py-0.5 rounded ml-1">({{ $archive->period_yy_mm }})</span>
                            @endif
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 block mb-1">Kondisi Wadah Fisik:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $archive->physical_condition }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-2">Rincian Isi Berkas & Metadata:</span>
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/90 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed font-medium">
                        {{ $archive->content_description }}
                    </div>
                </div>

                <!-- Digital Attachments & Scans -->
                <div class="space-y-3 pt-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Dokumentasi Scan & Lampiran Digital:</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($archive->scan_input_form)
                        <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-check" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Formulir Input</span>
                                    <span class="text-[10px] text-slate-500">Form pendaftaran fisik</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_input_form) }}" target="_blank" class="px-2.5 py-1 bg-amber-500 text-slate-950 text-[11px] font-black rounded-lg hover:bg-amber-400 transition">
                                Lihat Scan
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_approval_input)
                        <div class="p-3 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="check-square" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Approval Input</span>
                                    <span class="text-[10px] text-slate-500">Bukti persetujuan PIC</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_approval_input) }}" target="_blank" class="px-2.5 py-1 bg-blue-500 text-white text-[11px] font-black rounded-lg hover:bg-blue-400 transition">
                                Lihat Scan
                            </a>
                        </div>
                        @endif

                        @if($archive->scan_extension_form)
                        <div class="p-3 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Scan Form Perpanjangan</span>
                                    <span class="text-[10px] text-slate-500">Perpanjangan masa simpan</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->scan_extension_form) }}" target="_blank" class="px-2.5 py-1 bg-purple-500 text-white text-[11px] font-black rounded-lg hover:bg-purple-400 transition">
                                Lihat Form
                            </a>
                        </div>
                        @endif

                        @if($archive->file_path)
                        <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Lampiran Digital</span>
                                    <span class="text-[10px] text-slate-500">File softcopy</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $archive->file_path) }}" target="_blank" class="px-2.5 py-1 bg-emerald-500 text-slate-950 text-[11px] font-black rounded-lg hover:bg-emerald-400 transition">
                                Unduh Softcopy
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Storage & Retention Sidebar -->
        <div class="space-y-6">
            <!-- Physical Location Card -->
            <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    Lokasi Fisik Gudang
                </h3>

                @if($archive->location)
                <div class="space-y-2">
                    <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs">
                        <span class="text-slate-500 dark:text-slate-400 block font-medium">Gudang & Slot:</span>
                        <span class="font-extrabold text-emerald-700 dark:text-emerald-300 text-base block mt-0.5">{{ $archive->location->full_location }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                        {{ $archive->location->warehouse->name ?? '' }} ({{ $archive->location->warehouse->address ?? '' }})
                    </p>
                </div>
                @else
                <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500 font-semibold">
                    Belum dilakukan penempatan slot rak gudang.
                </div>
                @endif
            </div>

            <!-- Retention & Expiry Info -->
            <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                    Masa Simpan & Expiry
                </h3>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Durasi Retention:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $archive->retention_years }} Tahun</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Tanggal Pemusnahan:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">
                            {{ $archive->retention_expiry_date ? \Carbon\Carbon::parse($archive->retention_expiry_date)->format('d M Y') : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Timeline Logs -->
    <div class="bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5 text-cyan-600 dark:text-cyan-400"></i>
            Riwayat Activity Log (Audit Trail)
        </h3>

        <div class="space-y-3">
            @if($archive->entryLogs->isNotEmpty())
                @foreach($archive->entryLogs as $log)
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-xs flex items-start gap-3">
                    <div class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Masuk Gudang - Check-in</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">{{ $log->notes }}</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Diproses oleh: {{ $log->picGudang->name ?? 'PIC Gudang' }} | {{ $log->entry_date->format('d M Y H:i') }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->borrowingLogs->isNotEmpty())
                @foreach($archive->borrowingLogs as $bLog)
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-xs flex items-start gap-3">
                    <div class="p-2 bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl">
                        <i data-lucide="file-symlink" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Peminjaman Dokumen (Status: {{ strtoupper($bLog->status) }})</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">Tujuan: {{ $bLog->purpose }}</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Peminjam: {{ $bLog->borrower->name ?? 'User' }} | Est. Kembali: {{ \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') }}</span>
                    </div>
                </div>
                @endforeach
            @endif

            @if($archive->destructionLog)
                <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-xs flex items-start gap-3">
                    <div class="p-2 bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl">
                        <i data-lucide="file-x" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Log Pemusnahan Dokumen (No. BAP: {{ $archive->destructionLog->bap_number }})</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 font-medium">Metode: {{ $archive->destructionLog->method }} | {{ $archive->destructionLog->notes }}</p>
                        <a href="{{ route('destructions.bap', $archive->destructionLog) }}" class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline block mt-1">
                            Lihat Cetak Berita Acara Pemusnahan (BAP) &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
