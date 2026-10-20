<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAN Quick Clipboard & Scratchpad - DMS PT Indraco</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon_indraco.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amber: { 500: '#f59e0b', 600: '#d97706' }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="min-h-full bg-slate-950 font-sans flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50 px-4 py-3 shadow-md">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-amber-500/10 border border-amber-500/30 rounded-lg flex items-center justify-center text-amber-500 font-bold shadow-xs">
                    <i data-lucide="clipboard-copy" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-bold tracking-wide text-white font-mono uppercase">LAN Quick Scratchpad & Transfer</h1>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            1 Jaringan LAN
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Pindahkan catatan teks / file Notepad antar komputer tanpa aplikasi chat.</p>
                </div>
            </div>

            <!-- Server Access Link Badge -->
            <div class="flex items-center gap-2 bg-slate-800/80 border border-slate-700 rounded-lg px-3 py-1.5 font-mono text-xs text-slate-300">
                <i data-lucide="wifi" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span class="text-slate-400">Akses via:</span>
                <span id="displayLanUrl" class="font-bold text-amber-400 select-all cursor-pointer" onclick="copyLanUrl()" title="Klik untuk salin alamat URL">
                    {{ url('/quick-notes') }}
                </span>
                <button type="button" onclick="copyLanUrl()" class="ml-1 p-1 hover:bg-slate-700 text-slate-400 hover:text-white rounded transition" title="Salin URL ke Clipboard">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl w-full mx-auto p-4 sm:p-6 space-y-6 flex-1">

        <!-- Toast Notification Area -->
        <div id="toastMessage" class="hidden transition-all duration-300 p-3 rounded-lg border text-xs font-mono flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i id="toastIcon" data-lucide="check-circle" class="w-4 h-4"></i>
                <span id="toastText"></span>
            </div>
            <button onclick="hideToast()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT COLUMN: SENDER FORM (KIRIM DARI LAPTOP APAPUN) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 sm:p-5 shadow-lg space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </span>
                            <h2 class="text-xs font-bold font-mono uppercase tracking-wider text-white">Kirim Catatan ke Komputer Lain</h2>
                        </div>
                    </div>

                    <!-- TAB SELECTOR: TEXT / FILE -->
                    <div class="flex gap-2 p-1 bg-slate-950 border border-slate-800 rounded-lg font-mono text-xs">
                        <button type="button" id="tabTextBtn" onclick="switchTab('text')" class="flex-1 py-1.5 px-3 rounded text-center font-bold transition bg-amber-500 text-slate-950 shadow-xs flex items-center justify-center gap-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Paste Teks</span>
                        </button>
                        <button type="button" id="tabFileBtn" onclick="switchTab('file')" class="flex-1 py-1.5 px-3 rounded text-center font-bold transition text-slate-400 hover:text-white flex items-center justify-center gap-1.5">
                            <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                            <span>Upload File .txt</span>
                        </button>
                    </div>

                    <!-- FORM 1: PASTE TEKS -->
                    <form id="textForm" onsubmit="handleTextSubmit(event)" class="space-y-3">
                        @csrf
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5 font-mono">
                                <label for="textContentInput" class="font-bold text-slate-300">Tempelkan Catatan Notepad:</label>
                                <button type="button" onclick="pasteFromClipboard()" class="text-amber-400 hover:underline flex items-center gap-1 text-[11px]">
                                    <i data-lucide="clipboard" class="w-3 h-3"></i>
                                    <span>Paste dari Clipboard</span>
                                </button>
                            </div>
                            <textarea 
                                name="text_content" 
                                id="textContentInput" 
                                rows="12" 
                                placeholder="Tempelkan isi catatan testing, log, atau nomor-nomor box arsip di sini... (Ctrl+Enter untuk langsung kirim)"
                                class="w-full p-3 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono text-slate-100 placeholder-slate-600 focus:outline-none focus:border-amber-500 leading-relaxed transition"
                                required
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between text-xs font-mono">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-400 select-none">
                                <input type="checkbox" id="appendCheckbox" class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-0">
                                <span class="text-[11px]">Tambahkan di akhir (Append)</span>
                            </label>

                            <button 
                                type="submit" 
                                id="btnSubmitText"
                                class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-lg shadow-md transition flex items-center gap-2 cursor-pointer"
                            >
                                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                <span>Kirim Catatan</span>
                            </button>
                        </div>
                    </form>

                    <!-- FORM 2: UPLOAD FILE .TXT -->
                    <form id="fileForm" onsubmit="handleFileSubmit(event)" enctype="multipart/form-data" class="space-y-3 hidden">
                        @csrf
                        <div class="border-2 border-dashed border-slate-700 hover:border-amber-500/80 rounded-xl p-6 text-center transition bg-slate-950/60 cursor-pointer" onclick="document.getElementById('noteFileInput').click()">
                            <input 
                                type="file" 
                                name="note_file" 
                                id="noteFileInput" 
                                accept=".txt,.log,.md,.csv,.json" 
                                class="hidden" 
                                onchange="displaySelectedFilename(this)"
                                required
                            >
                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-800 flex items-center justify-center text-amber-400 mb-2">
                                <i data-lucide="file-text" class="w-6 h-6"></i>
                            </div>
                            <p class="text-xs font-bold text-white mb-0.5" id="fileNameDisplay">Pilih file Notepad (.txt) atau seret ke sini</p>
                            <p class="text-[11px] text-slate-500">Mendukung file teks: .txt, .log, .csv, .md</p>
                        </div>

                        <button 
                            type="submit" 
                            id="btnSubmitFile"
                            class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-lg shadow-md transition flex items-center justify-center gap-2 cursor-pointer text-xs font-mono"
                        >
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Upload & Kirim File Catatan</span>
                        </button>
                    </form>

                    <!-- Petunjuk Cepat -->
                    <div class="p-3 bg-slate-950 border border-slate-800/80 rounded-lg text-[11px] text-slate-400 font-mono space-y-1">
                        <strong class="text-slate-300 block">💡 Cara Pakai:</strong>
                        <p>1. Di laptop server/lain, buka browser dan ketik alamat URL di atas.</p>
                        <p>2. Paste catatan Notepad Anda di sini lalu klik "Kirim Catatan".</p>
                        <p>3. Di laptop utama ini, catatan otomatis muncul dan siap di-copy 1 klik!</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: RECEIVER & VIEWER (CATATAN AKTIF SAAT INI) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 sm:p-5 shadow-lg space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="p-1.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <i data-lucide="file-check-2" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <h2 class="text-xs font-bold font-mono uppercase tracking-wider text-white">Catatan Aktif di Server LAN</h2>
                                <p class="text-[11px] text-slate-400 font-mono" id="noteMetaText">
                                    @if(!empty($note['updated_at']))
                                        Tersimpan: <strong class="text-slate-200">{{ $note['updated_at'] }}</strong> 
                                        @if($note['sender_ip']) • IP: <span class="text-amber-400">{{ $note['sender_ip'] }}</span> @endif
                                        • {{ number_format($note['char_count']) }} Karakter ({{ $note['line_count'] }} Baris)
                                    @else
                                        Belum ada catatan yang dikirim.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                onclick="copyActiveNote()" 
                                id="btnCopyNote"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                                title="Salin seluruh teks catatan ke clipboard Anda"
                            >
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span>Salin Teks</span>
                            </button>

                            <a 
                                href="{{ route('quick_notes.download') }}" 
                                id="btnDownloadNote"
                                class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-mono font-bold transition flex items-center gap-1.5 shadow-sm"
                                title="Unduh catatan sebagai file .txt"
                            >
                                <i data-lucide="download" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Unduh .txt</span>
                            </a>

                            <button 
                                type="button" 
                                onclick="clearActiveNote()" 
                                class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-lg text-xs font-mono font-bold transition flex items-center gap-1 cursor-pointer"
                                title="Kosongkan catatan"
                            >
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Note Content Viewer (Clean Monospace Box) -->
                    <div class="relative">
                        <pre 
                            id="noteContentViewer" 
                            class="w-full min-h-[380px] max-h-[580px] overflow-auto p-4 bg-slate-950 border border-slate-800 rounded-xl font-mono text-xs text-slate-200 leading-relaxed whitespace-pre-wrap break-words select-text"
                        >{{ $note['content'] ?: 'Belum ada catatan. Silakan tempel teks catatan di sebelah kiri atau unggah file .txt untuk dibagikan antar komputer di jaringan ini.' }}</pre>

                        <!-- Live Polling Indicator -->
                        <div class="absolute bottom-3 right-3 flex items-center gap-1.5 px-2 py-1 rounded bg-slate-900/90 border border-slate-800 text-[10px] font-mono text-slate-400 backdrop-blur-xs select-none">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>Live Sync LAN Aktif</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950 px-4 py-3 text-center text-xs font-mono text-slate-500">
        <span>PT Indraco Workstation Utility • LAN Quick Clipboard • Bebas dependensi internet</span>
    </footer>

    <!-- JavaScript Engine -->
    <script>
        let currentNoteContent = @json($note['content'] ?? '');
        let currentUpdatedAt = @json($note['updated_at'] ?? '');

        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) lucide.createIcons();

            // Shortcut Ctrl + Enter to submit text form
            const textInput = document.getElementById('textContentInput');
            if (textInput) {
                textInput.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('textForm').requestSubmit();
                    }
                });
            }

            // Start polling every 3 seconds to auto-update note content from other computers
            setInterval(pollNoteUpdate, 3000);
        });

        function switchTab(type) {
            const textTab = document.getElementById('tabTextBtn');
            const fileTab = document.getElementById('tabFileBtn');
            const textForm = document.getElementById('textForm');
            const fileForm = document.getElementById('fileForm');

            if (type === 'text') {
                textTab.className = 'flex-1 py-1.5 px-3 rounded text-center font-bold transition bg-amber-500 text-slate-950 shadow-xs flex items-center justify-center gap-1.5';
                fileTab.className = 'flex-1 py-1.5 px-3 rounded text-center font-bold transition text-slate-400 hover:text-white flex items-center justify-center gap-1.5';
                textForm.classList.remove('hidden');
                fileForm.classList.add('hidden');
            } else {
                fileTab.className = 'flex-1 py-1.5 px-3 rounded text-center font-bold transition bg-amber-500 text-slate-950 shadow-xs flex items-center justify-center gap-1.5';
                textTab.className = 'flex-1 py-1.5 px-3 rounded text-center font-bold transition text-slate-400 hover:text-white flex items-center justify-center gap-1.5';
                fileForm.classList.remove('hidden');
                textForm.classList.add('hidden');
            }
        }

        function displaySelectedFilename(input) {
            const label = document.getElementById('fileNameDisplay');
            if (input.files && input.files[0]) {
                label.textContent = '📄 ' + input.files[0].name + ' (' + Math.round(input.files[0].size / 1024) + ' KB)';
            } else {
                label.textContent = 'Pilih file Notepad (.txt) atau seret ke sini';
            }
        }

        async function pasteFromClipboard() {
            try {
                if (navigator.clipboard && navigator.clipboard.readText) {
                    const text = await navigator.clipboard.readText();
                    if (text) {
                        const input = document.getElementById('textContentInput');
                        input.value = text;
                        showToast('Berhasil menempelkan teks dari clipboard!', 'success');
                    }
                } else {
                    document.getElementById('textContentInput').focus();
                    showToast('Gunakan pintasan keyboard Ctrl + V untuk menempelkan teks.', 'info');
                }
            } catch (err) {
                document.getElementById('textContentInput').focus();
                showToast('Izin clipboard ditolak. Silakan tempel manual dengan Ctrl + V.', 'warning');
            }
        }

        function copyLanUrl() {
            const url = document.getElementById('displayLanUrl').textContent.trim();
            copyToClipboard(url, 'Alamat link LAN berhasil disalin ke clipboard!');
        }

        function copyActiveNote() {
            const viewer = document.getElementById('noteContentViewer');
            const text = viewer.textContent;
            if (!text || text.startsWith('Belum ada catatan')) {
                showToast('Belum ada teks catatan untuk disalin.', 'warning');
                return;
            }
            copyToClipboard(text, '📋 Catatan berhasil disalin ke clipboard Anda!');
        }

        function copyToClipboard(text, successMsg) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(successMsg, 'success');
                }).catch(() => {
                    fallbackCopy(text, successMsg);
                });
            } else {
                fallbackCopy(text, successMsg);
            }
        }

        function fallbackCopy(text, successMsg) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showToast(successMsg, 'success');
            } catch (e) {
                showToast('Gagal menyalin otomatis. Silakan pilih dan salin manual.', 'warning');
            }
            document.body.removeChild(ta);
        }

        async function handleTextSubmit(e) {
            e.preventDefault();
            const textInput = document.getElementById('textContentInput');
            const appendCheck = document.getElementById('appendCheckbox');
            const btn = document.getElementById('btnSubmitText');

            const val = textInput.value;
            if (!val.trim()) return;

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Mengirim...</span>';
            if (window.lucide) lucide.createIcons();

            try {
                const res = await fetch('{{ route("quick_notes.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        text_content: val,
                        append: appendCheck.checked ? '1' : '0'
                    })
                });

                const data = await res.json();
                if (data.success && data.note) {
                    updateViewer(data.note);
                    textInput.value = '';
                    showToast('✅ Catatan berhasil dikirim ke server LAN!', 'success');
                }
            } catch (err) {
                showToast('Gagal mengirim catatan. Periksa jaringan.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="arrow-right-circle" class="w-4 h-4"></i><span>Kirim Catatan</span>';
                if (window.lucide) lucide.createIcons();
            }
        }

        async function handleFileSubmit(e) {
            e.preventDefault();
            const fileInput = document.getElementById('noteFileInput');
            const btn = document.getElementById('btnSubmitFile');
            if (!fileInput.files || !fileInput.files[0]) return;

            const formData = new FormData();
            formData.append('note_file', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Mengupload file...</span>';
            if (window.lucide) lucide.createIcons();

            try {
                const res = await fetch('{{ route("quick_notes.store") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (data.success && data.note) {
                    updateViewer(data.note);
                    fileInput.value = '';
                    displaySelectedFilename(fileInput);
                    showToast('✅ File Notepad berhasil diupload & dimuat!', 'success');
                }
            } catch (err) {
                showToast('Gagal upload file.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="upload" class="w-4 h-4"></i><span>Upload & Kirim File Catatan</span>';
                if (window.lucide) lucide.createIcons();
            }
        }

        async function clearActiveNote() {
            if (!confirm('Apakah Anda yakin ingin mengosongkan seluruh isi catatan di server LAN?')) return;

            try {
                const res = await fetch('{{ route("quick_notes.clear") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    updateViewer({
                        content: '',
                        updated_at: null,
                        sender_ip: null,
                        char_count: 0,
                        line_count: 0
                    });
                    showToast('Catatan berhasil dikosongkan.', 'info');
                }
            } catch (err) {
                showToast('Gagal mengosongkan catatan.', 'danger');
            }
        }

        async function pollNoteUpdate() {
            try {
                const res = await fetch('{{ route("quick_notes.api_get") }}?_t=' + Date.now(), { cache: 'no-store' });
                if (res.ok) {
                    const data = await res.json();
                    if (data.updated_at && data.updated_at !== currentUpdatedAt) {
                        updateViewer(data);
                        showToast('🔔 Catatan diperbarui dari perangkat lain (' + (data.sender_ip || 'LAN') + ')!', 'success');
                    }
                }
            } catch(e) {}
        }

        function updateViewer(note) {
            currentNoteContent = note.content || '';
            currentUpdatedAt = note.updated_at || '';

            const viewer = document.getElementById('noteContentViewer');
            const meta = document.getElementById('noteMetaText');

            if (note.content) {
                viewer.textContent = note.content;
                meta.innerHTML = `Tersimpan: <strong class="text-slate-200">${note.updated_at}</strong> ` +
                                (note.sender_ip ? `• IP: <span class="text-amber-400">${note.sender_ip}</span> ` : '') +
                                `• ${note.char_count || note.content.length} Karakter (${note.line_count || 1} Baris)`;
            } else {
                viewer.textContent = 'Belum ada catatan. Silakan tempel teks catatan di sebelah kiri atau unggah file .txt untuk dibagikan antar komputer di jaringan ini.';
                meta.textContent = 'Belum ada catatan yang dikirim.';
            }
        }

        function showToast(text, type = 'info') {
            const el = document.getElementById('toastMessage');
            const textEl = document.getElementById('toastText');
            const iconEl = document.getElementById('toastIcon');

            textEl.textContent = text;
            el.className = 'transition-all duration-300 p-3 rounded-lg border text-xs font-mono flex items-center justify-between ';

            if (type === 'success') {
                el.className += 'bg-emerald-950/60 border-emerald-500/30 text-emerald-300';
                iconEl.setAttribute('data-lucide', 'check-circle');
            } else if (type === 'warning') {
                el.className += 'bg-amber-950/60 border-amber-500/30 text-amber-300';
                iconEl.setAttribute('data-lucide', 'alert-triangle');
            } else if (type === 'danger') {
                el.className += 'bg-rose-950/60 border-rose-500/30 text-rose-300';
                iconEl.setAttribute('data-lucide', 'alert-circle');
            } else {
                el.className += 'bg-blue-950/60 border-blue-500/30 text-blue-300';
                iconEl.setAttribute('data-lucide', 'info');
            }

            el.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();

            clearTimeout(window.toastTimer);
            window.toastTimer = setTimeout(hideToast, 4000);
        }

        function hideToast() {
            document.getElementById('toastMessage').classList.add('hidden');
        }
    </script>
</body>
</html>
