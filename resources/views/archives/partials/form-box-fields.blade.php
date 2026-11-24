{{-- Identity of one box (inside x-for "box, bIdx"): title + similarity check, sub-department, category, condition, period. --}}
<div class="form-grid form-grid--2">
    <div class="field">
        <label class="field__label" :for="'box_title_' + bIdx">NAMA / JUDUL BOX <span class="field__required" aria-hidden="true">*</span></label>
        <input type="text" class="control" :id="'box_title_' + bIdx" :name="'boxes[' + bIdx + '][title]'" x-model="box.title" @input="checkSimilarity(bIdx)"
            maxlength="255" required placeholder="Contoh: Voucher Kas Kecil & Pengeluaran Q1">
        <span class="field__hint">Cek kemiripan aktif otomatis</span>
        <template x-if="box.similarity">
            <div class="notice" :class="box.similarity.is_duplicate ? 'notice--danger' : 'notice--warning'" role="status">
                <div>
                    <strong x-text="box.similarity.message"></strong> <span class="badge" x-text="box.similarity.badge"></span>
                    <p class="text-sm" x-show="box.similarity.diff">Kata berbeda: <span x-text="box.similarity.diff"></span></p>
                    <p class="text-sm" x-text="box.similarity.is_duplicate ? 'Nama box harus unik dan tidak boleh sama dengan box aktif lainnya. Silakan ubah nama box ini.' : 'Peringatan ini informatif agar Anda dapat memeriksa potensi duplikasi.'"></p>
                </div>
                <x-button size="sm" variant="ghost" class="btn--icon" @click="box.similarity = null" aria-label="Tutup"><x-icon name="x" /></x-button>
            </div>
        </template>
    </div>

    <div class="field">
        <label class="field__label" :for="'box_sub_' + bIdx">SUB-DEPARTEMEN <span class="field__optional">(Opsional)</span></label>
        <select class="control" :id="'box_sub_' + bIdx" :name="'boxes[' + bIdx + '][sub_department_id]'" x-model="box.sub_department_id" @change="onBoxSubDeptChange(box, false)">
            <option value="">-- Ikuti Sub-Dept Induk / Semua --</option>
            <option value="inti" :selected="box.sub_department_id === 'inti'">Inti Departemen</option>
            <template x-for="s in subDepartments()" :key="s.id">
                <option :value="String(s.id)" :selected="String(s.id) === box.sub_department_id" x-text="s.code + ' - ' + s.name"></option>
            </template>
        </select>
        <span class="field__hint" x-text="box.sub_department_id ? (box.sub_department_id === 'inti' ? 'Inti Dept' : 'Khusus Box Ini') : 'Mewarisi Dept Induk'"></span>
    </div>

    <div class="field">
        <label class="field__label" :for="'box_type_' + bIdx">KATEGORI DOKUMEN (Terkunci)</label>
        <input type="text" class="control" :id="'box_type_' + bIdx" :name="'boxes[' + bIdx + '][document_type]'" x-model="box.document_type" readonly
            maxlength="100" placeholder="Otomatis dari Master Berkas" title="Tipe dokumen otomatis terkunci dari Master Berkas">
    </div>

    <div class="field">
        <label class="field__label" :for="'box_condition_' + bIdx">KONDISI FISIK WADAH BOX <span class="field__required" aria-hidden="true">*</span></label>
        <input type="text" class="control" :id="'box_condition_' + bIdx" :name="'boxes[' + bIdx + '][physical_condition]'" x-model="box.physical_condition" maxlength="100" required>
    </div>

    <div class="field">
        <label class="field__label" :for="'periode_bulan_' + bIdx">PERIODE DOKUMEN (BULAN) <span class="field__required" aria-hidden="true">*</span>
            <span class="badge badge--warning" x-text="'Maks. ' + maxMonths() + ' Bulan (' + (maxMonths() / 12) + ' Thn)'"></span></label>
        <div class="input-group">
            <input type="number" class="control" :id="'periode_bulan_' + bIdx" x-model="box.periode_bulan" @input="clampMonths(box)" :disabled="bIdx > 0 && box.follow_previous"
                min="1" :max="maxMonths()" :placeholder="'Maks. ' + maxMonths() + ' Bln'">
            <span class="input-group__addon">Bulan</span>
        </div>
        <div class="cluster" x-show="!(bIdx > 0 && box.follow_previous)">
            <span class="text-sm text-muted">Preset:</span>
            <template x-for="preset in presets()" :key="preset">
                <button type="button" class="btn btn--sm" :class="{ 'btn--primary': String(box.periode_bulan) === String(preset) }" @click="box.periode_bulan = String(preset)" x-text="presetLabel(preset)"></button>
            </template>
        </div>
        @if ($isDraft)
            <label class="checkbox" x-show="bIdx > 0">
                <input type="checkbox" x-model="box.follow_previous" @change="followPrevious(bIdx)">
                <span>Ikuti Periode Box Sebelumnya <span class="text-muted" x-show="box.follow_previous" x-text="'(' + (bIdx > 0 ? finalPeriod(bIdx - 1) : '') + ')'"></span></span>
            </label>
        @endif
        <input type="hidden" :name="'boxes[' + bIdx + '][periode]'" :value="finalPeriod(bIdx)">
    </div>
</div>
