{{-- Kelola Departemen, tab 1: assign an existing user / create a PIC account, list of PIC with password reset and release. --}}
<div class="stack">
    <div class="cluster">
        <strong>Penugasan PIC untuk Departemen Ini</strong>
        <x-button size="sm" @click="showCreatePic = !showCreatePic"><span x-text="showCreatePic ? 'Tutup Form Buat User' : '+ Buat Akun PIC Baru'"></span></x-button>
    </div>
    <div class="cluster" x-show="!showCreatePic">
        <x-field label="Pilih Pengguna Yang Sudah Ada:" for="pic-assign-user">
            <select id="pic-assign-user" class="control" x-model="assignUserId">
                <option value="">-- Pilih User untuk ditugaskan sebagai PIC --</option>
                <template x-for="u in available" :key="u.id">
                    <option :value="String(u.id)" x-text="u.name + ' (' + u.email + ') - ' + (u.role || 'user')"></option>
                </template>
            </select>
        </x-field>
        <x-button variant="primary" @click="assignPic()" ::disabled="!assignUserId || busy">Tugaskan Sebagai PIC</x-button>
    </div>
    <form class="stack" x-show="showCreatePic" @submit.prevent="createPic()" data-no-lock>
        <strong class="text-sm">Formulir Registrasi Akun PIC Baru:</strong>
        <div class="form-grid form-grid--2">
            <x-field label="NAMA LENGKAP" for="pic-new-name" required><x-input name="pic_name" id="pic-new-name" x-model="newPic.name" autocomplete="off" placeholder="Nama Lengkap User" required maxlength="255" /></x-field>
            <x-field label="EMAIL LOGIN" for="pic-new-email" required><x-input name="pic_email" id="pic-new-email" type="email" x-model="newPic.email" autocomplete="off" placeholder="nama@indraco.com" required /></x-field>
            <x-field label="PASSWORD" for="pic-new-pass" required><x-input name="pic_password" id="pic-new-pass" type="password" x-model="newPic.password" autocomplete="new-password" placeholder="Min. 6 karakter" required minlength="6" /></x-field>
            <x-field label="NO. TELEPON / WHATSAPP" for="pic-new-phone"><x-input name="pic_phone" id="pic-new-phone" x-model="newPic.phone" autocomplete="off" placeholder="0812xxxx" maxlength="20" /></x-field>
        </div>
        <div class="cluster">
            <x-button @click="showCreatePic = false">Batal</x-button>
            <x-button type="submit" variant="primary" ::disabled="busy">Simpan &amp; Buat Akun PIC</x-button>
        </div>
    </form>

    <strong class="text-sm">Daftar User PIC Terdaftar (<span x-text="pics.length"></span> Pengguna)</strong>
    <x-table>
        <thead><tr><th>NAMA PENGGUNA</th><th>EMAIL</th><th>NO. TELEPON</th><th>ROLE</th><th class="table__actions">AKSI</th></tr></thead>
        <tbody>
            <template x-for="u in pics" :key="u.id">
                <tr>
                    <td data-label="NAMA" class="table__title" x-text="u.name"></td>
                    <td data-label="EMAIL" x-text="u.email"></td>
                    <td data-label="NO. TELEPON" x-text="u.phone || '-'"></td>
                    <td data-label="ROLE"><span class="badge badge--info">PIC DEPT</span></td>
                    <td data-label="" class="table__actions">
                        <x-button size="sm" @click="openPassword(u)" title="Ganti / Reset password user PIC ini"><x-icon name="key" />Ganti Password</x-button>
                        <x-button size="sm" variant="danger" @click="removePic(u)" title="Lepaskan user ini dari PIC departemen">Hapus PIC</x-button>
                    </td>
                </tr>
            </template>
            <tr x-show="!pics.length"><td colspan="5" data-label="" class="text-muted">Belum ada user PIC yang ditugaskan untuk departemen ini.</td></tr>
        </tbody>
    </x-table>
</div>
