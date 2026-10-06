@extends(auth()->check() && auth()->user()->isPicDept() ? 'layouts.desktop_pic' : 'layouts.app')

@section('title', 'Log History & Audit Trail - DMS PT Indraco')

@section('content')
<div class="space-y-6" x-data="logViewer()">
    <!-- Header with System Status & Badges -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="p-2 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-xl shadow-xs">
                    <i data-lucide="history" class="w-6 h-6"></i>
                </span>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        Log History & Global Audit Trail
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm font-medium">
                        Pencatatan riwayat komprehensif & mutlak (immutable) seluruh aktivitas pengguna dan transaksi sistem DMS PT Indraco.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <div class="px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-700 dark:text-emerald-400 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Audit Logger Aktif</span>
            </div>
            <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-4 h-4 text-amber-500"></i>
                <span>Akses: {{ auth()->user()->role_label ?? 'User' }} (View Only)</span>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
                <i data-lucide="activity" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ number_format($stats['total_activity_logs'] ?? 0) }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Log Aktivitas</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <i data-lucide="calendar-check" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($stats['today_activity_logs'] ?? 0) }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aktivitas Hari Ini</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ number_format($stats['total_users'] ?? 0) }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pengguna Terdaftar</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl">
                <i data-lucide="layers" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ number_format($stats['modules_count'] ?? 0) }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Modul Terpantau</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs & Comprehensive Filter Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        <!-- Tabs -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-1 sm:gap-2 overflow-x-auto no-scrollbar">
            <a href="{{ route('logs.index', ['tab' => 'activity']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 whitespace-nowrap {{ $tab === 'activity' ? 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-b-2 border-cyan-500 font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="activity" class="w-4 h-4 text-cyan-500"></i> Semua Aktivitas User & Sistem
            </a>

            <a href="{{ route('logs.index', ['tab' => 'entry']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 whitespace-nowrap {{ $tab === 'entry' ? 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-b-2 border-blue-500 font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="log-in" class="w-4 h-4 text-blue-500"></i> Log Masuk Gudang (Fisik)
            </a>

            <a href="{{ route('logs.index', ['tab' => 'borrowing']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 whitespace-nowrap {{ $tab === 'borrowing' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-b-2 border-purple-500 font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-symlink" class="w-4 h-4 text-purple-500"></i> Log Peminjaman Dokumen
            </a>

            <a href="{{ route('logs.index', ['tab' => 'destruction']) }}" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 whitespace-nowrap {{ $tab === 'destruction' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-b-2 border-rose-500 font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="file-x" class="w-4 h-4 text-rose-500"></i> Log Pemusnahan & BAP
            </a>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('logs.index') }}" method="GET" class="space-y-3 pt-1" x-data="{ submitting: false }" @submit="submitting = true">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pencarian Teks / Ref / IP:</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, deskripsi, nama user, no. referensi, IP address..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 font-medium">
                    </div>
                </div>

                @if($tab === 'activity')
                <!-- Module Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Modul:</label>
                    <select name="module" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                        @foreach($modulesList as $modKey => $modLabel)
                        <option value="{{ $modKey }}" {{ request('module') == $modKey ? 'selected' : '' }}>{{ $modLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Pengguna:</label>
                    <select name="user_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->role_label }})</option>
                        @endforeach
                    </select>
                </div>
                @else
                <!-- Department Filter for other tabs -->
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Departemen:</label>
                    <select name="department_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->code }} - {{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <!-- Date Range & Action Buttons Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 items-end pt-1">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sampai Tanggal:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                </div>

                @if($tab === 'activity')
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Departemen:</label>
                    <select name="department_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500 font-medium">
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->code }} - {{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="submitting" class="flex-1 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 disabled:opacity-50 shadow-xs cursor-pointer">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="submitting"></i>
                        <i data-lucide="filter" class="w-3.5 h-3.5" x-show="!submitting"></i>
                        <span x-text="submitting ? 'Memfilter...' : 'Terapkan Filter'"></span>
                    </button>

                    @if(request()->hasAny(['search', 'module', 'user_id', 'department_id', 'date_from', 'date_to']))
                    <a href="{{ route('logs.index', ['tab' => $tab]) }}" class="px-3 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition" title="Reset filter">
                        ✕ Reset
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Read-Only Notice Banner -->
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-3">
        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
        <div class="text-xs text-amber-900 dark:text-amber-200 leading-relaxed">
            <strong class="font-bold">Keamanan & Integritas Audit Trail:</strong> Seluruh catatan log aktivitas di halaman ini bersifat <em>append-only & immutable</em> (hanya dapat dilihat / view-only oleh SuperAdmin, Admin, dan PIC Gudang). Rekaman riwayat tidak dapat dimodifikasi atau dihapus untuk menjaga transparansi dan kepatuhan audit.
        </div>
    </div>

    <!-- Log Table Content -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6">
        @if($tab === 'activity')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Waktu & IP</th>
                            <th class="py-3 px-4">Pengguna / Role</th>
                            <th class="py-3 px-4">Modul & Aksi</th>
                            <th class="py-3 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3 px-4 text-center">Payload / Metadata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                        @forelse($activityLogs as $log)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850 transition">
                            <!-- Time & IP Address -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white font-mono flex items-center gap-1.5">
                                    <span>{{ $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</span>
                                    <span class="text-xs px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded font-semibold border border-slate-200 dark:border-slate-700">WIB</span>
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <span>{{ $log->created_at->timezone('Asia/Jakarta')->diffForHumans() }}</span>
                                    <span>•</span>
                                    <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                                </div>
                            </td>

                            <!-- User & Role -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    {{ $log->user_name ?? ($log->user->name ?? 'System/Guest') }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    @php
                                        $role = $log->user_role ?? ($log->user->role ?? 'system');
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $role === 'admin' ? 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/20' : ($role === 'pic_gudang' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20' : 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/20') }}">
                                        {{ $role === 'admin' ? 'Super Admin' : ($role === 'pic_gudang' ? 'PIC Gudang' : ($role === 'pic_dept' ? 'PIC Dept' : $role)) }}
                                    </span>
                                    @if($log->department)
                                    <span class="text-[10px] text-slate-400 font-mono">[{{ $log->department->code }}]</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Module & Action -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border shadow-2xs {{ $log->module_badge_class }}">
                                        <i data-lucide="{{ $log->module_icon }}" class="w-3.5 h-3.5"></i>
                                        <span>{{ $log->module_label }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border {{ $log->action_badge_class }}">
                                            {{ $log->action_label }}
                                        </span>
                                        <span class="text-[10px] font-mono text-slate-400 font-semibold">[{{ $log->action }}]</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Description & Ref -->
                            <td class="py-3.5 px-4">
                                <div class="text-slate-900 dark:text-slate-100 font-medium leading-relaxed">
                                    {{ $log->description }}
                                </div>
                                @if($log->reference_id)
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    Ref ID: <span class="font-bold text-amber-600 dark:text-amber-400">{{ $log->reference_id }}</span>
                                </div>
                                @endif
                            </td>

                            <!-- Metadata detail modal trigger -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <button 
                                    type="button" 
                                    @click="openDetails({{ json_encode([
                                        'id' => $log->id,
                                        'action' => $log->action,
                                        'action_label' => $log->action_label,
                                        'module' => $log->module_label,
                                        'module_raw' => $log->module,
                                        'description' => $log->description,
                                        'user_name' => $log->user_name ?? ($log->user->name ?? 'System'),
                                        'user_role' => $log->user_role ?? ($log->user->role ?? '-'),
                                        'department' => $log->department ? ($log->department->code . ' - ' . $log->department->name) : '-',
                                        'reference_id' => $log->reference_id ?? '-',
                                        'ip_address' => $log->ip_address ?? '127.0.0.1',
                                        'user_agent' => $log->user_agent ?? '-',
                                        'created_at' => $log->created_at->timezone('Asia/Jakarta')->format('d F Y, H:i:s') . ' WIB',
                                        'properties' => $log->properties,
                                    ]) }})"
                                    class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-cyan-500/10 hover:text-cyan-600 dark:hover:text-cyan-400 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl font-bold transition flex items-center gap-1.5 mx-auto cursor-pointer"
                                >
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Detail Log</span>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600"></i>
                                    <p class="font-bold text-sm text-slate-700 dark:text-slate-300">Belum ada catatan log aktivitas</p>
                                    <p class="text-xs text-slate-500">Log aktivitas akan tercatat secara otomatis saat ada transaksi dokumen, login pengguna, mutasi gudang, atau perubahan master data.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $activityLogs->links() }}</div>

        @elseif($tab === 'entry')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Tgl Masuk Gudang</th>
                            <th class="py-3 px-4">No. Box Arsip</th>
                            <th class="py-3 px-4">Judul Berkas & Dept</th>
                            <th class="py-3 px-4">Lokasi Fisik Slot Rak</th>
                            <th class="py-3 px-4">PIC Gudang Penerima</th>
                            <th class="py-3 px-4">Catatan Reception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                        @forelse($entryLogs as $eLog)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850 transition font-medium">
                            <td class="py-3.5 px-4 font-bold text-cyan-700 dark:text-cyan-300 font-mono">
                                {{ $eLog->entry_date ? $eLog->entry_date->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                {{ $eLog->archive->box_number ?? 'Pending' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($eLog->archive)
                                <a href="{{ route('archives.show', $eLog->archive) }}" class="font-bold text-slate-900 dark:text-white hover:text-cyan-600 dark:hover:text-cyan-400 transition block">
                                    {{ $eLog->archive->title }}
                                </a>
                                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $eLog->archive->department->name ?? '' }}</span>
                                @else
                                <span class="text-slate-400 italic">Arsip telah dihapus/dimusnahkan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700 dark:text-emerald-400 font-mono">
                                {{ $eLog->location->full_location ?? ($eLog->archive->location->full_location ?? 'Umum') }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $eLog->picGudang->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                {{ $eLog->notes ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data log masuk gudang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entryLogs->links('vendor.pagination.tailwind') }}</div>

        @elseif($tab === 'borrowing')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Tgl Pinjam / Pengembalian</th>
                            <th class="py-3 px-4">No. Box & Judul Berkas</th>
                            <th class="py-3 px-4">Peminjam</th>
                            <th class="py-3 px-4">Tujuan Peminjaman</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Verifikasi Gudang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs font-medium">
                        @forelse($borrowingLogs as $bLog)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850 transition">
                            <td class="py-3.5 px-4 space-y-0.5">
                                <span class="text-purple-700 dark:text-purple-300 font-bold block">Req: {{ \Carbon\Carbon::parse($bLog->request_date)->format('d M Y') }}</span>
                                <span class="text-slate-500 dark:text-slate-400 block">Est: {{ $bLog->expected_return_date ? \Carbon\Carbon::parse($bLog->expected_return_date)->format('d M Y') : 'Hanya Diambil (Permanen)' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-extrabold text-amber-600 dark:text-amber-400 text-[11px] block">{{ $bLog->archive->box_number ?? '-' }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $bLog->archive->title ?? 'Arsip' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-900 dark:text-slate-200 font-bold">
                                {{ $bLog->borrower->name ?? 'User' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                {{ $bLog->purpose }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-purple-700 dark:text-purple-300">
                                    {{ $bLog->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                {{ $bLog->picGudang->name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data log peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $borrowingLogs->links('vendor.pagination.tailwind') }}</div>

        @elseif($tab === 'destruction')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3 px-4">Nomor BAP</th>
                            <th class="py-3 px-4">Judul Berkas & Box</th>
                            <th class="py-3 px-4">Tgl Pemusnahan</th>
                            <th class="py-3 px-4">Metode Execution</th>
                            <th class="py-3 px-4">Pengaju / Approval</th>
                            <th class="py-3 px-4 text-right">Lampiran BAP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs font-medium">
                        @forelse($destructionLogs as $dLog)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850 transition">
                            <td class="py-3.5 px-4 font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                {{ $dLog->bap_number }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $dLog->archive->title ?? 'Dokumen' }}</span>
                                <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono">{{ $dLog->archive->box_number ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-rose-700 dark:text-rose-400 font-bold font-mono">
                                {{ \Carbon\Carbon::parse($dLog->destruction_date)->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $dLog->method }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                {{ $dLog->proposedBy->name ?? 'Gudang Specialist' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('destructions.bap', $dLog) }}" target="_blank" class="px-2.5 py-1 bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 rounded font-bold hover:bg-amber-500/30">
                                    Cetak BAP
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada log pemusnahan dokumen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $destructionLogs->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>

    <!-- DETAIL LOG MODAL POPUP -->
    <div 
        x-show="showModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        @keydown.escape.window="showModal = false"
    >
        <div 
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.outside="showModal = false"
        >
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 rounded-lg">
                        <i data-lucide="info" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white" x-text="'Audit Log Detail #' + selectedLog.id"></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium" x-text="(selectedLog.action_label || selectedLog.action) + ' [' + selectedLog.action + '] • ' + selectedLog.module"></p>
                    </div>
                </div>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto space-y-4 text-xs">
                <!-- Summary Card -->
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi Aktivitas</div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white" x-text="selectedLog.description"></div>
                </div>

                <!-- 2-Column Info Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pengguna</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedLog.user_name"></span>
                        <span class="block text-[11px] text-slate-400 font-mono" x-text="'Role: ' + selectedLog.user_role"></span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Departemen</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="selectedLog.department"></span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Pencatatan</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="selectedLog.created_at"></span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Alamat IP & Ref</span>
                        <span class="font-bold text-cyan-600 dark:text-cyan-400 font-mono" x-text="selectedLog.ip_address"></span>
                        <span class="block text-[11px] text-slate-400 font-mono" x-text="'Ref ID: ' + selectedLog.reference_id"></span>
                    </div>
                </div>

                <!-- User Agent -->
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">User Agent / Browser</span>
                    <span class="font-mono text-[11px] text-slate-600 dark:text-slate-400 break-all" x-text="selectedLog.user_agent"></span>
                </div>

                <!-- Properties JSON Payload -->
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Properties & Payload JSON</span>
                        <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400">Structured Data</span>
                    </div>
                    <pre class="p-3 bg-slate-900 text-emerald-400 rounded-lg text-[11px] font-mono overflow-x-auto max-h-48 border border-slate-800 leading-relaxed" x-text="formatJson(selectedLog.properties)"></pre>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-slate-100 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                <button type="button" @click="showModal = false" class="px-4 py-2 bg-slate-800 text-white hover:bg-slate-700 rounded-xl text-xs font-bold transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function logViewer() {
    return {
        showModal: false,
        selectedLog: {
            id: null,
            action: '',
            module: '',
            description: '',
            user_name: '',
            user_role: '',
            department: '',
            reference_id: '',
            ip_address: '',
            user_agent: '',
            created_at: '',
            properties: null
        },
        openDetails(logData) {
            this.selectedLog = logData;
            this.showModal = true;
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },
        formatJson(data) {
            if (!data) return '{\n  "status": "No additional payload recorded"\n}';
            try {
                if (typeof data === 'string') {
                    data = JSON.parse(data);
                }
                return JSON.stringify(data, null, 2);
            } catch (e) {
                return String(data);
            }
        }
    };
}
</script>
@endsection
