{{-- Laravel 7 frmUserAdd / frmUserEdit (one modal, add or edit) and frmResetUserPassword. --}}
@php($deptOptions = $departments->mapWithKeys(fn ($d) => [$d->id => $d->code.' - '.$d->name])->all())
<x-modal id="user-form" title="User Pengguna">
    <form method="POST" :action="form.id ? base + '/' + form.id : base" class="stack">
        @csrf
        <input type="hidden" name="_method" :value="form.id ? 'PUT' : 'POST'">
        <input type="hidden" name="_form" value="user">
        <input type="hidden" name="_id" :value="form.id || ''">
        <p class="field__label" x-text="form.id ? 'Form Perubahan User' : 'Form Data Pengguna'"></p>
        <x-field label="NAMA LENGKAP" for="user-name" required error="name">
            <x-input name="name" id="user-name" x-model="form.name" required maxlength="255" />
        </x-field>
        <x-field label="ALAMAT EMAIL" for="user-email" required error="email">
            <x-input name="email" id="user-email" type="email" x-model="form.email" required maxlength="255" autocomplete="off" />
        </x-field>
        <div class="field">
            <label class="field__label" for="user-password" x-text="form.id ? 'PASSWORD BARU (KOSONGKAN JIKA TIDAK UBAH)' : 'PASSWORD DEFAULT'"></label>
            <x-input name="password" id="user-password" type="password" x-model="form.password" minlength="6" ::required="!form.id" ::placeholder="form.id ? '******' : ''" autocomplete="new-password" />
            @error('password')<span class="field__error" role="alert">{{ $message }}</span>@enderror
        </div>
        <x-field label="PERAN / ROLE SYSTEM" for="user-role" required error="role">
            <x-select name="role" id="user-role" :options="\App\Http\Requests\UserRequest::ROLES" x-model="form.role" required />
        </x-field>
        <x-field label="DEPARTEMEN LINKED" for="user-department" error="department_id">
            <x-select name="department_id" id="user-department" :options="$deptOptions" placeholder="-- Tanpa Departemen (Global) --" x-model="form.department_id" />
        </x-field>
        <x-field label="NO. TELEPON / WA" for="user-phone" error="phone">
            <x-input name="phone" id="user-phone" x-model="form.phone" maxlength="20" placeholder="081234567890" />
        </x-field>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary"><span x-text="form.id ? 'Update User (Enter)' : 'Simpan User (Enter)'"></span></x-button>
        </div>
    </form>
</x-modal>

<x-modal id="user-reset" title="Reset Password User">
    <form method="POST" :action="resetUser ? base + '/' + resetUser.id + '/reset-password' : '#'" class="stack">
        @csrf
        <div x-show="resetUser">
            <strong x-text="resetUser ? resetUser.name : ''"></strong>
            <p class="text-sm text-muted" x-text="resetUser ? resetUser.email + ' (' + resetUser.role + ')' : ''"></p>
        </div>
        <p class="field__label">Atur Password Baru</p>
        <x-field label="PASSWORD BARU USER" for="reset-password" required hint="Min. 6 Karakter">
            <div class="input-group">
                <input :type="showReset ? 'text' : 'password'" name="password" id="reset-password" class="control" x-model="resetValue" required minlength="6" autocomplete="new-password">
                <x-button class="input-group__addon btn--icon" @click="showReset = !showReset" tabindex="-1" ::aria-label="showReset ? 'Sembunyikan password' : 'Tampilkan password'"><x-icon name="eye" /></x-button>
            </div>
        </x-field>
        <div class="cluster text-sm">
            <span class="text-muted">Preset:</span>
            <x-button size="sm" @click="resetValue = 'indraco123'">indraco123</x-button>
            <x-button size="sm" @click="randomPassword()">Acak 8 Karakter</x-button>
        </div>
        <div class="modal__footer">
            <x-button data-modal-close>Batal (Esc)</x-button>
            <x-button type="submit" variant="primary">Simpan Password (Enter)</x-button>
        </div>
    </form>
</x-modal>
