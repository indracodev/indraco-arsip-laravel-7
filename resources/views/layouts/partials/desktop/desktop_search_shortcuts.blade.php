            function superAdminQuickSearch() {
                return {
                    searchQuery: '',
                    selectedDept: '',
                    selectedSubDept: '',
                    selectedPeriod: '',
                    filterDepartments: [],
                    availablePeriods: [],
                    showSuggestions: false,
                    isLoading: false,
                    searchResults: [],
                    suggestedKeywords: [],
                    selectedIndex: -1,

                    subDepartmentsList() {
                        if (!this.selectedDept) return [];
                        const dept = this.filterDepartments.find(d => String(d.id) === String(this.selectedDept));
                        return dept ? (dept.sub_departments || []) : [];
                    },

                    hasActiveFilters() {
                        return Boolean(this.selectedDept || this.selectedSubDept || this.selectedPeriod);
                    },

                    onDeptChange() {
                        this.selectedSubDept = '';
                        this.doSearch();
                    },

                    onSubDeptChange() {
                        this.doSearch();
                    },

                    onPeriodChange() {
                        this.doSearch();
                    },

                    resetFilters() {
                        this.selectedDept = '';
                        this.selectedSubDept = '';
                        this.selectedPeriod = '';
                        this.doSearch();
                    },

                    async loadDefaultSuggestions() {
                        this.isLoading = true;
                        try {
                            const params = new URLSearchParams();
                            if (this.selectedDept) params.append('department_id', this.selectedDept);
                            if (this.selectedSubDept) params.append('sub_department_id', this.selectedSubDept);
                            if (this.selectedPeriod) params.append('period', this.selectedPeriod);

                            const res = await fetch('{{ route('archives.search_api') }}?' + params.toString());
                            const data = await res.json();
                            if (data.departments) this.filterDepartments = data.departments;
                            if (data.periods) this.availablePeriods = data.periods;

                            if (data.type === 'suggestions') {
                                this.suggestedKeywords = data.keywords || [];
                                this.searchResults = data.recent || [];
                            } else if (data.type === 'results') {
                                this.searchResults = data.items || [];
                            }
                        } catch (err) {
                            console.error('Super Admin Search API error:', err);
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    async doSearch() {
                        if (!this.searchQuery.trim() && !this.hasActiveFilters()) {
                            this.loadDefaultSuggestions();
                            return;
                        }
                        this.isLoading = true;
                        this.showSuggestions = true;
                        this.selectedIndex = -1;
                        try {
                            const params = new URLSearchParams();
                            if (this.searchQuery.trim()) params.append('q', this.searchQuery.trim());
                            if (this.selectedDept) params.append('department_id', this.selectedDept);
                            if (this.selectedSubDept) params.append('sub_department_id', this.selectedSubDept);
                            if (this.selectedPeriod) params.append('period', this.selectedPeriod);

                            const res = await fetch('{{ route('archives.search_api') }}?' + params.toString());
                            const data = await res.json();
                            if (data.departments) this.filterDepartments = data.departments;
                            if (data.periods) this.availablePeriods = data.periods;

                            if (data.type === 'results') {
                                this.searchResults = data.items || [];
                            } else if (data.type === 'suggestions') {
                                this.suggestedKeywords = data.keywords || [];
                                this.searchResults = data.recent || [];
                            }
                        } catch (err) {
                            console.error('Super Admin Search query error:', err);
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    selectKeyword(keyword) {
                        this.searchQuery = keyword;
                        this.doSearch();
                    },

                    selectArchive(archive) {
                        if (archive.result_type === 'rack') {
                            let url = archive.url || ('{{ route('master.warehouses.layout') }}?rack_id=' + (archive.rack_id ||
                                archive.id));
                            url += (url.includes('?') ? '&embed=1' : '?embed=1');
                            window.dispatchEvent(new CustomEvent('open-form-window', {
                                detail: {
                                    id: 'warehouse_layout',
                                    title: 'Layout Gudang 2D',
                                    icon: 'map',
                                    url: url
                                }
                            }));
                            // Post message directly to the layout iframe in case window is already open
                            setTimeout(() => {
                                const ifr = document.getElementById('iframe-warehouse_layout');
                                if (ifr && ifr.contentWindow) {
                                    ifr.contentWindow.postMessage({
                                        action: 'highlight_rack',
                                        rack_id: archive.rack_id || archive.id,
                                        rack_code: archive.rack_code
                                    }, '*');
                                }
                            }, 250);
                            this.closeDropdown();
                            return;
                        }

                        const parentArchiveId = archive.archive_id || archive.id;
                        const detailId = 'archive_detail_' + parentArchiveId;
                        const boxLabel = archive.box_number ? archive.box_number : ('ID #' + parentArchiveId);
                        const docTitle = archive.document_name || archive.title || 'Dokumen Arsip';
                        const title = `[Detail] ${boxLabel} - ${docTitle}`;
                        let url = archive.detail_url || archive.url || ('{{ url('/archives') }}/' + parentArchiveId);
                        url += (url.includes('?') ? '&embed=1' : '?embed=1');

                        window.dispatchEvent(new CustomEvent('open-form-window', {
                            detail: {
                                id: detailId,
                                title: title,
                                icon: 'file-text',
                                url: url
                            }
                        }));
                        this.closeDropdown();
                    },

                    openArchive(archive) {
                        this.selectArchive(archive);
                    },

                    navigateResults(direction) {
                        if (!this.searchResults.length) return;
                        this.selectedIndex = (this.selectedIndex + direction + this.searchResults.length) % this.searchResults
                            .length;
                    },

                    selectActiveResult() {
                        if (this.selectedIndex >= 0 && this.selectedIndex < this.searchResults.length) {
                            this.selectArchive(this.searchResults[this.selectedIndex]);
                        }
                    },

                    clearSearch() {
                        this.searchQuery = '';
                        if (this.hasActiveFilters()) {
                            this.doSearch();
                        } else {
                            this.loadDefaultSuggestions();
                        }
                    },

                    closeDropdown() {
                        this.showSuggestions = false;
                        this.selectedIndex = -1;
                    }
                }
            }

            document.addEventListener("DOMContentLoaded", function() {
                lucide.createIcons();

                // Desktop Keyboard Shortcuts Handler
                document.addEventListener('keydown', function(e) {
                    // F5 / Ctrl+R / Ctrl+Shift+R: Refresh Active Window or Hard Refresh
                    if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'))) {
                        const app = document.querySelector('[x-data="desktopAppLayout()"]');
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
                    // Ctrl + F: Focus Super Admin Search Input
                    else if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                        e.preventDefault();
                        const searchInput = document.getElementById('adminHeaderSearchInput');
                        if (searchInput) {
                            searchInput.focus();
                            searchInput.select();
                        }
                    }
                });
            });
