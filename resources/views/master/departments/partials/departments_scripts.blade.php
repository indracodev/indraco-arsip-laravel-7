<script>
function masterDepartmentsManager() {
    return {
        openAddDept: false, 
        editDeptItem: null,
        openAddSubDept: false,
        selectedDeptForSub: '',
        editSubDeptItem: null,
        searchQuery: '',
        sortColumn: 'code',
        sortDirection: 'asc',
        submitting: false,
        isLoading: false,
        expandedDepts: {},
        items: @json($departments),

        // Archive list & detail modals
        openArchiveListModal: false,
        archiveListTitle: '',
        archiveListSubtitle: '',
        archiveListItems: [],
        archiveListLoading: false,
        archiveListSearch: '',

        openArchiveDetailModal: false,
        selectedArchiveDetail: null,

        // Manage Department Modal (PIC & Master Archives)
        openManageDeptModal: false,
        selectedManageDept: null,
        selectedManageSubDept: null,
        selectedSubDeptFilter: '',
        manageTab: 'pic', // 'pic' or 'master_archives'
        manageLoading: false,
        managePicUsers: [],
        availableUsers: [],
        manageMasterArchives: [],
        manageSubDepts: [],
        selectedAssignUserId: '',
        showCreatePicForm: false,
        newPicName: '',
        newPicEmail: '',
        newPicPassword: '',
        newPicPhone: '',
        
        showAddMasterArchiveForm: false,
        showBatchAddForm: false,
        batchArchiveText: '',
        batchArchiveSubDeptId: '',
        batchArchiveDocType: 'UMUM',
        batchArchiveRetentionYears: 5,
        newArchiveName: '',
        newArchiveCode: '',
        newArchiveDocType: 'FAKTUR_PAJAK',
        newArchiveSubDeptId: '',
        newArchiveRetentionYears: 5,
        newArchiveDescription: '',
        editMasterArchiveItem: null,
        manageSearchArchive: '',

        openManageDept(dept) {
            this.selectedManageDept = Object.assign({}, dept);
            this.selectedManageSubDept = null;
            this.selectedSubDeptFilter = '';
            this.newArchiveSubDeptId = '';
            this.batchArchiveSubDeptId = '';
            this.batchArchiveText = '';
            this.manageTab = 'pic';
            this.managePicUsers = [];
            this.manageMasterArchives = [];
            this.manageSubDepts = [];
            this.availableUsers = [];
            this.selectedAssignUserId = '';
            this.showCreatePicForm = false;
            this.showAddMasterArchiveForm = false;
            this.showBatchAddForm = false;
            this.newArchiveRetentionYears = dept.retention_years || 5;
            this.batchArchiveRetentionYears = dept.retention_years || 5;
            this.openManageDeptModal = true;
            this.loadManageData(dept.id);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        openManageSubDept(dept, sub) {
            this.selectedManageDept = Object.assign({}, dept);
            this.selectedManageSubDept = Object.assign({}, sub);
            this.manageTab = 'master_archives';
            this.selectedSubDeptFilter = (sub.id) ? sub.id.toString() : (sub.code === 'GEN' ? 'induk_only' : '');
            this.newArchiveSubDeptId = (sub.id) ? sub.id.toString() : '';
            this.batchArchiveSubDeptId = (sub.id) ? sub.id.toString() : '';
            this.batchArchiveText = '';
            this.managePicUsers = [];
            this.manageMasterArchives = [];
            this.manageSubDepts = [];
            this.availableUsers = [];
            this.selectedAssignUserId = '';
            this.showCreatePicForm = false;
            this.showAddMasterArchiveForm = false;
            this.showBatchAddForm = false;
            this.newArchiveRetentionYears = sub.retention_years || dept.retention_years || 5;
            this.batchArchiveRetentionYears = sub.retention_years || dept.retention_years || 5;
            this.openManageDeptModal = true;
            this.loadManageData(dept.id);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        loadManageData(deptId) {
            this.manageLoading = true;
            fetch('{{ url('/api/departments') }}/' + deptId + '/manage-data')
                .then(res => res.json())
                .then(data => {
                    this.managePicUsers = data.pic_users || [];
                    this.availableUsers = data.available_users || [];
                    this.manageMasterArchives = data.master_archives || [];
                    this.manageSubDepts = data.sub_departments || [];
                    this.manageLoading = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Error fetching manage data:', err);
                    this.manageLoading = false;
                });
        },

        assignExistingPic() {
            if (!this.selectedAssignUserId) {
                alert('Pilih user terlebih dahulu.');
                return;
            }
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/pic/assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: this.selectedAssignUserId })
            })
            .then(res => res.json())
            .then(data => {
                this.selectedAssignUserId = '';
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'PIC berhasil ditugaskan.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal menugaskan PIC.');
            });
        },

        createAndAssignPic() {
            if (!this.newPicName || !this.newPicEmail || !this.newPicPassword) {
                alert('Nama, Email, dan Password wajib diisi.');
                return;
            }
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/pic/assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.newPicName,
                    email: this.newPicEmail,
                    password: this.newPicPassword,
                    phone: this.newPicPhone
                })
            })
            .then(res => res.json())
            .then(data => {
                this.newPicName = '';
                this.newPicEmail = '';
                this.newPicPassword = '';
                this.newPicPhone = '';
                this.showCreatePicForm = false;
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'User PIC baru berhasil dibuat.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal membuat user PIC baru.');
            });
        },

        async resetPicPassword(user) {
            const newPassword = prompt(`Masukkan password baru untuk PIC "${user.name}" (${user.email})\nMinimal 6 karakter:`);
            if (newPassword === null) return;
            const trimmed = newPassword.trim();
            if (!trimmed) {
                alert('Password baru tidak boleh kosong.');
                return;
            }
            if (trimmed.length < 6) {
                alert('Password baru minimal 6 karakter.');
                return;
            }

            this.manageLoading = true;
            try {
                const res = await fetch('{{ url('/master/users') }}/' + user.id + '/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ password: trimmed })
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    alert(data.message || `Password untuk ${user.name} berhasil diperbarui.`);
                } else {
                    let errMsg = data.message || 'Gagal mereset password.';
                    if (data.errors) {
                        errMsg = Object.values(data.errors).flat().join('\n');
                    }
                    alert(errMsg);
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan jaringan saat mereset password.');
            } finally {
                this.manageLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        async removePicUser(user) {
            const ok = await window.showConfirmModal({
                title: 'Lepas Penugasan PIC',
                message: 'Lepaskan penugasan PIC ' + user.name + ' dari departemen ini?',
                type: 'warning',
                confirmText: 'Ya, Lepaskan'
            });
            if (!ok) return;
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/pic/' + user.id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'Penugasan PIC berhasil dilepaskan.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal melepaskan PIC.');
            });
        },

        saveMasterArchive() {
            if (!this.newArchiveName) {
                alert('Nama Dokumen / Berkas Arsip wajib diisi.');
                return;
            }
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/master-archives', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.newArchiveName,
                    code: this.newArchiveCode,
                    document_type: this.newArchiveDocType,
                    sub_department_id: this.newArchiveSubDeptId || null,
                    retention_years: this.newArchiveRetentionYears,
                    description: this.newArchiveDescription
                })
            })
            .then(res => res.json())
            .then(data => {
                this.newArchiveName = '';
                this.newArchiveCode = '';
                this.newArchiveDescription = '';
                this.showAddMasterArchiveForm = false;
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'Master Arsip berhasil ditambahkan.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal menambahkan Master Arsip.');
            });
        },

        getBatchLinesCount() {
            if (!this.batchArchiveText) return 0;
            return this.batchArchiveText
                .split('\n')
                .map(l => l.trim())
                .filter(l => l.length > 0).length;
        },

        saveBatchMasterArchives() {
            const lines = this.batchArchiveText
                .split('\n')
                .map(l => l.trim())
                .filter(l => l.length > 0);

            if (lines.length === 0) {
                alert('Silakan masukkan minimal 1 baris nama berkas arsip.');
                return;
            }

            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/master-archives/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: lines,
                    sub_department_id: this.batchArchiveSubDeptId || null,
                    document_type: this.batchArchiveDocType,
                    retention_years: this.batchArchiveRetentionYears
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.batchArchiveText = '';
                    this.showBatchAddForm = false;
                    this.loadManageData(this.selectedManageDept.id);
                    alert(data.message || 'Master Arsip Batch berhasil disimpan.');
                } else {
                    alert(data.message || 'Gagal menyimpan batch master arsip.');
                    this.manageLoading = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Terjadi kesalahan saat menyimpan batch.');
            });
        },

        updateMasterArchiveSubmit() {
            if (!this.editMasterArchiveItem || !this.editMasterArchiveItem.name) {
                alert('Nama Dokumen / Berkas wajib diisi.');
                return;
            }
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/master-archives/' + this.editMasterArchiveItem.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.editMasterArchiveItem.name,
                    code: this.editMasterArchiveItem.code,
                    document_type: this.editMasterArchiveItem.document_type,
                    sub_department_id: this.editMasterArchiveItem.sub_department_id || null,
                    retention_years: this.editMasterArchiveItem.retention_years,
                    description: this.editMasterArchiveItem.description,
                    is_active: this.editMasterArchiveItem.is_active
                })
            })
            .then(res => res.json())
            .then(data => {
                this.editMasterArchiveItem = null;
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'Master Arsip berhasil diperbarui.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal memperbarui Master Arsip.');
            });
        },

        async deleteMasterArchive(arc) {
            const ok = await window.showConfirmModal({
                title: 'Hapus Master Arsip',
                message: 'Hapus master arsip "' + arc.name + '"?',
                type: 'danger',
                confirmText: 'Ya, Hapus'
            });
            if (!ok) return;
            this.manageLoading = true;
            fetch('{{ url('/master/departments') }}/' + this.selectedManageDept.id + '/master-archives/' + arc.id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.loadManageData(this.selectedManageDept.id);
                alert(data.message || 'Master Arsip berhasil dihapus.');
            })
            .catch(err => {
                console.error(err);
                this.manageLoading = false;
                alert('Gagal menghapus Master Arsip.');
            });
        },

        get filteredManageMasterArchives() {
            let list = this.manageMasterArchives || [];
            if (this.selectedSubDeptFilter) {
                if (this.selectedSubDeptFilter === 'induk_only') {
                    list = list.filter(arc => !arc.sub_department_id);
                } else {
                    list = list.filter(arc => arc.sub_department_id == this.selectedSubDeptFilter);
                }
            }
            if (this.manageSearchArchive && this.manageSearchArchive.trim() !== '') {
                const q = this.manageSearchArchive.toLowerCase();
                list = list.filter(arc => {
                    return (arc.name && arc.name.toLowerCase().includes(q)) ||
                           (arc.code && arc.code.toLowerCase().includes(q)) ||
                           (arc.document_type && arc.document_type.toLowerCase().includes(q)) ||
                           (arc.description && arc.description.toLowerCase().includes(q));
                });
            }
            return list;
        },

        toggleExpand(deptId) {
            this.expandedDepts[deptId] = !this.expandedDepts[deptId];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        expandAll() {
            this.items.forEach(d => { this.expandedDepts[d.id] = true; });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        collapseAll() {
            this.expandedDepts = {};
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async toggleDeptActive(dept) {
            const actionText = dept.is_active !== false ? 'menonaktifkan' : 'mengaktifkan';
            const ok = await window.showConfirmModal({
                title: 'Ubah Status Departemen',
                message: 'Yakin ingin ' + actionText + ' departemen "' + dept.name + '"?',
                type: 'warning',
                confirmText: 'Ya, Lanjutkan'
            });
            if (!ok) return;
            
            this.isLoading = true;
            fetch('{{ url('/master/departments') }}/' + dept.id + '/toggle-active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    dept.is_active = data.is_active;
                }
                this.isLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                console.error(err);
                this.isLoading = false;
                alert('Gagal mengubah status aktif departemen.');
            });
        },

        async toggleSubDeptActive(sub, dept) {
            const actionText = sub.is_active !== false ? 'menonaktifkan' : 'mengaktifkan';
            const ok = await window.showConfirmModal({
                title: 'Ubah Status Sub-Departemen',
                message: 'Yakin ingin ' + actionText + ' sub-departemen "' + sub.name + '"?',
                type: 'warning',
                confirmText: 'Ya, Lanjutkan'
            });
            if (!ok) return;
            
            this.isLoading = true;
            fetch('{{ url('/master/sub-departments') }}/' + sub.id + '/toggle-active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sub.is_active = data.is_active;
                }
                this.isLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                console.error(err);
                this.isLoading = false;
                alert('Gagal mengubah status aktif sub-departemen.');
            });
        },

        openCreateSubDept(deptId) {
            this.selectedDeptForSub = deptId || (this.items.length > 0 ? this.items[0].id : '');
            this.openAddSubDept = true;
        },

        openEditSubDept(sub, deptId) {
            this.editSubDeptItem = Object.assign({}, sub, { department_id: deptId || sub.department_id });
        },

        // Fetch and open archives list modal for Department
        openDeptArchives(dept) {
            this.archiveListTitle = 'Daftar Berkas Arsip: ' + dept.name + ' (' + dept.code + ')';
            this.archiveListSubtitle = 'Seluruh Box & Dokumen Fisik Terdaftar di Unit ' + dept.name;
            this.archiveListItems = [];
            this.archiveListSearch = '';
            this.archiveListLoading = true;
            this.openArchiveListModal = true;

            fetch('{{ url('/api/departments') }}/' + dept.id + '/archives')
                .then(res => res.json())
                .then(data => {
                    this.archiveListItems = data.archives || [];
                    this.archiveListLoading = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Error fetching archives:', err);
                    this.archiveListLoading = false;
                });
        },

        // Fetch and open archives list modal for Sub-Department
        openSubDeptArchives(sub, dept) {
            this.archiveListTitle = 'Daftar Berkas Arsip: ' + dept.code + '-' + sub.code + ' (' + sub.name + ')';
            this.archiveListSubtitle = 'Sub-Departemen ' + sub.name + ' di bawah ' + dept.name;
            this.archiveListItems = [];
            this.archiveListSearch = '';
            this.archiveListLoading = true;
            this.openArchiveListModal = true;

            fetch('{{ url('/api/sub-departments') }}/' + sub.id + '/archives')
                .then(res => res.json())
                .then(data => {
                    this.archiveListItems = data.archives || [];
                    this.archiveListLoading = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Error fetching archives:', err);
                    this.archiveListLoading = false;
                });
        },

        // Fetch and open archives list modal for Unassigned / General archives in Department
        openUnassignedDeptArchives(dept) {
            this.archiveListTitle = 'Daftar Berkas Arsip Induk: ' + dept.name + ' (' + dept.code + ')';
            this.archiveListSubtitle = 'Berkas arsip tingkat departemen induk (belum dialokasikan ke sub-unit tertentu)';
            this.archiveListItems = [];
            this.archiveListSearch = '';
            this.archiveListLoading = true;
            this.openArchiveListModal = true;

            fetch('{{ url('/api/departments') }}/' + dept.id + '/archives?filter=unassigned')
                .then(res => res.json())
                .then(data => {
                    this.archiveListItems = data.archives || [];
                    this.archiveListLoading = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Error fetching unassigned archives:', err);
                    this.archiveListLoading = false;
                });
        },

        // Open detail modal for specific archive
        showArchiveDetail(archive) {
            this.selectedArchiveDetail = Object.assign({}, archive);
            this.openArchiveDetailModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get filteredArchiveList() {
            if (!this.archiveListSearch || this.archiveListSearch.trim() === '') {
                return this.archiveListItems;
            }
            const q = this.archiveListSearch.toLowerCase();
            return this.archiveListItems.filter(arc => {
                const matchBox = arc.box_number && arc.box_number.toLowerCase().includes(q);
                const matchTitle = arc.title && arc.title.toLowerCase().includes(q);
                const matchPeriod = arc.periode_doc && arc.periode_doc.toLowerCase().includes(q);
                const matchLoc = arc.location && arc.location.toLowerCase().includes(q);
                const matchItems = arc.items && arc.items.some(it => it.document_name && it.document_name.toLowerCase().includes(q));
                return matchBox || matchTitle || matchPeriod || matchLoc || matchItems;
            });
        },

        get filteredItems() {
            let res = [...this.items];
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(dept => {
                    const matchDept = (dept.code && dept.code.toLowerCase().includes(q)) ||
                                      (dept.name && dept.name.toLowerCase().includes(q)) ||
                                      (dept.description && dept.description.toLowerCase().includes(q));
                    
                    const matchSub = dept.sub_departments && dept.sub_departments.some(sub => 
                        (sub.code && sub.code.toLowerCase().includes(q)) ||
                        (sub.name && sub.name.toLowerCase().includes(q)) ||
                        (sub.description && sub.description.toLowerCase().includes(q))
                    );

                    return matchDept || matchSub;
                });
            }
            res.sort((a, b) => {
                let valA = a[this.sortColumn] ?? '';
                let valB = b[this.sortColumn] ?? '';
                if (typeof valA === 'number' && typeof valB === 'number') {
                    return this.sortDirection === 'asc' ? valA - valB : valB - valA;
                }
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
                if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            return res;
        },

        sortBy(col) {
            this.isLoading = true;
            if (this.sortColumn === col) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = col;
                this.sortDirection = 'asc';
            }
            setTimeout(() => { this.isLoading = false; if (window.lucide) lucide.createIcons(); }, 80);
        }
    };
}
</script>