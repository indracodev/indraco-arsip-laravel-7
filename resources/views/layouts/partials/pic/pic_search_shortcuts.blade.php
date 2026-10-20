        // PIC Department Quick Search & Live Suggestion Component
        function picQuickSearch() {
            return {
                searchQuery: '',
                showSuggestions: false,
                isLoading: false,
                searchResults: [],
                suggestedKeywords: [],
                selectedIndex: -1,

                init() {
                    // Tutup dropdown saat klik di luar area input & modal dropdown
                    document.addEventListener('pointerdown', (e) => {
                        if (this.showSuggestions && !this.$el.contains(e.target)) {
                            this.closeDropdown();
                        }
                    });

                    // Tutup dropdown saat iframe diklik (window parent kehilangan fokus)
                    window.addEventListener('blur', () => {
                        if (this.showSuggestions) {
                            this.closeDropdown();
                        }
                    });
                },

                async loadDefaultSuggestions() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('{{ route("archives.search_api") }}');
                        const data = await res.json();
                        if (data.type === 'suggestions') {
                            this.suggestedKeywords = data.keywords || [];
                            this.searchResults = data.recent || [];
                        }
                    } catch (err) {
                        console.error('Search API error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async doSearch() {
                    if (!this.searchQuery.trim()) {
                        this.loadDefaultSuggestions();
                        return;
                    }
                    this.isLoading = true;
                    this.showSuggestions = true;
                    this.selectedIndex = -1;
                    try {
                        const res = await fetch('{{ route("archives.search_api") }}?q=' + encodeURIComponent(this.searchQuery.trim()));
                        const data = await res.json();
                        if (data.type === 'results') {
                            this.searchResults = data.items || [];
                        }
                    } catch (err) {
                        console.error('Search API query error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                selectKeyword(keyword) {
                    this.searchQuery = keyword;
                    this.doSearch();
                },

                selectArchive(archive) {
                    // Keep suggestions open as requested so user can open multiple documents simultaneously
                    const detailId = 'archive_detail_' + archive.id;
                    const boxLabel = archive.box_number ? archive.box_number : ('ID #' + archive.id);
                    const title = `[Detail] ${boxLabel} - ${archive.title || 'Dokumen'}`;
                    let url = archive.url || ('{{ url("/archives") }}/' + archive.id);
                    url += (url.includes('?') ? '&embed=1' : '?embed=1');

                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: detailId,
                            title: title,
                            icon: 'file-text',
                            url: url
                        }
                    }));
                },

                openArchive(archive) {
                    this.selectArchive(archive);
                },

                navigateResults(direction) {
                    if (!this.searchResults.length) return;
                    this.selectedIndex = (this.selectedIndex + direction + this.searchResults.length) % this.searchResults.length;
                },

                selectActiveResult() {
                    if (this.selectedIndex >= 0 && this.selectedIndex < this.searchResults.length) {
                        this.selectArchive(this.searchResults[this.selectedIndex]);
                    }
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.loadDefaultSuggestions();
                },

                closeDropdown() {
                    this.showSuggestions = false;
                    this.selectedIndex = -1;
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (window.lucide) {
                lucide.createIcons();
            }

            // Desktop Keyboard Shortcuts Handler
            document.addEventListener('keydown', function(e) {
                // F2: Buka Form Draft Baru
                if (e.key === 'F2') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: 'archives_create',
                            title: 'Draft Pengajuan Box Baru',
                            icon: 'plus-circle',
                            url: '{{ route("archives.create") }}?embed=1'
                        }
                    }));
                }
                // F5 / Ctrl+R / Ctrl+Shift+R: Refresh Active Window or Hard Refresh
                else if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'))) {
                    const app = document.querySelector('[x-data="desktopPicAppLayout()"]');
                    if (e.shiftKey) {
                        // Ctrl + Shift + R: Persist state then perform full reload
                        if (app && window.Alpine) {
                            const alpineData = Alpine.$data(app);
                            if (alpineData && alpineData.saveMdiState) {
                                alpineData.saveMdiState();
                            }
                        }
                        return; // Let browser trigger hard reload
                    }

                    // Regular F5 or Ctrl + R: Refresh ONLY the active form iframe without shell flicker or losing open windows
                    e.preventDefault();
                    if (app && window.Alpine) {
                        const alpineData = Alpine.$data(app);
                        if (alpineData && alpineData.refreshActiveWindow) {
                            alpineData.refreshActiveWindow();
                            return;
                        }
                    }
                    window.location.reload();
                }
                // F8: Form Penarikan Berkas
                else if (e.key === 'F8') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-form-window', {
                        detail: {
                            id: 'borrowings',
                            title: '[Form 2] Penarikan Berkas',
                            icon: 'file-symlink',
                            url: '{{ route("borrowings.index") }}?embed=1'
                        }
                    }));
                }
                // Ctrl + F: Focus Search Input
                else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                    e.preventDefault();
                    const searchInput = document.getElementById('picHeaderSearchInput');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            });
        });
