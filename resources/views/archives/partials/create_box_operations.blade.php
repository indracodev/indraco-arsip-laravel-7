        openDuplicateModal(index) {
            this.duplicateSourceIndex = index;
            this.duplicateMode = 'full';
            const src = this.boxes[index];
            this.duplicateSelectedItems = src && src.items ? src.items.map(it => it.id) : [];
            this.duplicateModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        confirmDuplicateBox() {
            const source = this.boxes[this.duplicateSourceIndex];
            if (!source) return;

            let clonedItems = [];
            if (this.duplicateMode === 'full') {
                clonedItems = source.items.map((it, idx) => ({
                    id: Date.now() + idx + Math.random(),
                    document_name: it.document_name,
                    period_start: it.period_start,
                    period_end: it.period_end,
                    notes: it.notes || ''
                }));
            } else if (this.duplicateMode === 'selected_items') {
                const selectedSet = new Set(this.duplicateSelectedItems);
                clonedItems = source.items
                    .filter(it => selectedSet.has(it.id))
                    .map((it, idx) => ({
                        id: Date.now() + idx + Math.random(),
                        document_name: it.document_name,
                        period_start: it.period_start,
                        period_end: it.period_end,
                        notes: it.notes || ''
                    }));
            }

            if (clonedItems.length === 0) {
                const available = this.getBoxMasterArchives(source);
                const defaultDoc = available.length > 0 ? available[0].name : '';
                clonedItems = [
                    { id: Date.now(), document_name: defaultDoc, period_start: this.currentMonth, period_end: this.currentMonth, notes: '' }
                ];
            }

            const newTitle = source.title ? (source.title + ' (Salinan)') : '';
            this.boxes.push({
                id: Date.now() + Math.random(),
                title: newTitle,
                sub_department_id: source.sub_department_id,
                document_type: source.document_type,
                periode_bulan: source.periode_bulan || '1',
                follow_previous: true,
                physical_condition: source.physical_condition,
                items: clonedItems,
                similarityInfo: null
            });

            this.activeBoxIndex = this.boxes.length - 1;
            this.duplicateModalOpen = false;
            this.checkSimilarity(this.activeBoxIndex);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async removeBox(index) {
            if (this.boxes.length <= 1) {
                if (window.showConfirmModal) {
                    await window.showConfirmModal({
                        title: 'Perhatian',
                        message: 'Pengajuan minimal harus memiliki 1 box arsip.',
                        type: 'warning',
                        confirmText: 'Mengerti'
                    });
                }
                return;
            }
            if (window.showConfirmModal) {
                const ok = await window.showConfirmModal({
                    title: 'Hapus Box Arsip',
                    message: `Apakah Anda yakin ingin menghapus Box #${index + 1}? Seluruh butir berkas di dalam box ini akan dihapus.`,
                    type: 'danger',
                    confirmText: 'Ya, Hapus Box'
                });
                if (!ok) return;
            }
            this.boxes.splice(index, 1);
            if (this.activeBoxIndex >= this.boxes.length) {
                this.activeBoxIndex = this.boxes.length - 1;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleTablePaste(e, boxIndex) {
            const clipboardData = (e.clipboardData || window.clipboardData);
            if (!clipboardData) return;
            const text = clipboardData.getData('text');
            if (!text || (!text.includes('\t') && !text.includes('\n'))) {
                return; // Normal paste inside a single input
            }

            // Prevent tab/newline raw text dumping into one input
            e.preventDefault();
            const box = this.boxes[boxIndex];
            if (!box) return;

            const lines = text.trim().split(/\r?\n/);
            const newItems = [];

            for (let line of lines) {
                let trimmed = line.trim();
                if (!trimmed) continue;

                let cols = trimmed.split('\t');
                if (cols.length === 1 && trimmed.includes(';')) cols = trimmed.split(';');
                else if (cols.length === 1 && trimmed.includes(',')) cols = trimmed.split(',');

                cols = cols.map(c => c.trim().replace(/^["']|["']$/g, ''));

                const firstColLower = (cols[0] || '').toLowerCase();
                if (firstColLower === 'nama dokumen' || firstColLower === 'judul dokumen' || firstColLower === 'nama' || firstColLower === 'no') {
                    continue;
                }

                let docName = cols[0] || '';
                let pStart = cols[1] || this.currentMonth;
                let pEnd = cols[2] || pStart;
                let notes = cols[3] || '';

                pStart = pStart.replace('/', '-').substring(0, 7);
                pEnd = pEnd.replace('/', '-').substring(0, 7);
                if (!/^\d{4}-\d{2}$/.test(pStart)) pStart = this.currentMonth;
                if (!/^\d{4}-\d{2}$/.test(pEnd)) pEnd = pStart;

                if (docName) {
                    newItems.push({
                        id: Date.now() + Math.random(),
                        document_name: docName,
                        period_start: pStart,
                        period_end: pEnd,
                        notes: notes
                    });
                }
            }

            if (newItems.length > 0) {
                if (box.items.length === 1 && !box.items[0].document_name) {
                    box.items = newItems;
                } else {
                    box.items.push(...newItems);
                }

                if (window.showToast) {
                    window.showToast(`📋 Berhasil menempel ${newItems.length} butir dokumen dari spreadsheet ke Box #${boxIndex + 1}.`, 'success');
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        addItem(boxIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            
            // Auto-fill from previous item if present
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const defaultDoc = prevItem ? (prevItem.document_name || '') : '';
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;
            const pNotes = prevItem ? prevItem.notes : '';

            box.items.push({
                id: Date.now() + Math.random(),
                document_name: defaultDoc,
                period_start: pStart,
                period_end: pEnd,
                notes: pNotes
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleEnterOnItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (itemIndex === box.items.length - 1) {
                this.addItem(boxIndex);
            }
        },

        applyPreviousBoxPreset(boxIndex) {
            if (boxIndex <= 0) return;
            const prevBox = this.boxes[boxIndex - 1];
            const currentBox = this.boxes[boxIndex];
            if (!prevBox || !currentBox) return;

            currentBox.sub_department_id = prevBox.sub_department_id;
            currentBox.document_type = prevBox.document_type;
            currentBox.physical_condition = prevBox.physical_condition;
            currentBox.periode_bulan = prevBox.periode_bulan;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        addBulkItems(boxIndex, count = 5) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            
            const prevItem = box.items.length > 0 ? box.items[box.items.length - 1] : null;
            const defaultDoc = prevItem ? (prevItem.document_name || '') : '';
            const pStart = prevItem ? prevItem.period_start : this.currentMonth;
            const pEnd = prevItem ? prevItem.period_end : this.currentMonth;
            const pNotes = prevItem ? prevItem.notes : '';

            for (let i = 0; i < count; i++) {
                box.items.push({
                    id: Date.now() + i + Math.random(),
                    document_name: defaultDoc,
                    period_start: pStart,
                    period_end: pEnd,
                    notes: pNotes
                });
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        duplicateItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            const source = box.items[itemIndex];
            box.items.splice(itemIndex + 1, 0, {
                id: Date.now() + Math.random(),
                document_name: source.document_name,
                period_start: source.period_start,
                period_end: source.period_end,
                notes: source.notes || ''
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removeItem(boxIndex, itemIndex) {
            const box = this.boxes[boxIndex];
            if (!box) return;
            if (box.items.length <= 1) {
                alert('Box minimal harus memiliki 1 butir dokumen.');
                return;
            }
            box.items.splice(itemIndex, 1);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        checkSimilarity(boxIndex) {
            clearTimeout(this.similarityTimer);
            this.similarityTimer = setTimeout(() => {
                const box = this.boxes[boxIndex];
                if (!box || !box.title || !box.title.trim()) {
                    if (box) box.similarityInfo = null;
                    return;
                }

                const currentTitle = box.title.trim().toLowerCase();

                // 1. Local In-Batch Check (against other boxes in the form)
                let localMatch = null;
                for (let i = 0; i < this.boxes.length; i++) {
                    if (i === boxIndex) continue;
                    const otherTitle = (this.boxes[i].title || '').trim().toLowerCase();
                    if (!otherTitle) continue;

                    if (currentTitle === otherTitle) {
                        localMatch = {
                            match_type: 'identical',
                            source: 'batch',
                            box_num: i + 1,
                            title: this.boxes[i].title,
                            similarity_percentage: 100
                        };
                        break;
                    }
                }

                if (localMatch) {
                    box.similarityInfo = {
                        has_similarity: true,
                        is_duplicate: true,
                        is_restored_box: false,
                        match_type: 'identical',
                        source: 'batch',
                        similarity_percentage: 100,
                        message: `Nama box sama dengan Box #${localMatch.box_num} di form pengajuan ini. Setiap box harus memiliki nama berbeda.`,
                        matched_title: localMatch.title
                    };
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    return;
                }

                // 2. Remote Database Scoped Check
                const deptId = this.selectedDeptId;
                if (!deptId) return;

                fetch('{{ route('api.archives.check_similarity') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        department_id: deptId,
                        title: box.title,
                        origin_archive_id: box.origin_archive_id || null
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.has_similarity) {
                        const isIdentical = data.match_type === 'identical' || data.is_duplicate;
                        const isRestored = !!box.origin_archive_id;
                        box.similarityInfo = {
                            has_similarity: true,
                            is_duplicate: isIdentical && !isRestored,
                            is_restored_box: isIdentical && isRestored,
                            match_type: data.match_type,
                            source: 'database',
                            similarity_percentage: data.similarity_percentage,
                            message: isIdentical
                                ? (isRestored 
                                    ? `Box dari penarikan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`
                                    : `Nama box sudah digunakan oleh arsip lain [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`)
                                : `Kemiripan ${data.similarity_percentage}% dengan arsip tersimpan [${data.matched_archive.box_number || 'Box'}]: ${data.matched_archive.title}`,
                            matched_archive: data.matched_archive,
                            diff: data.diff
                        };
                    } else {
                        box.similarityInfo = null;
                    }
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    console.error('Similarity check error:', err);
                });
            }, 400);
        },
