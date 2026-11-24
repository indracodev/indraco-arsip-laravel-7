<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Kelola User & Hak Akses (Laravel 7 UserController): admin only, Laravel 7 validation, e-mail
 * unique after User::normalizeEmail(), no password hash on the page, delete guards.
 */
class UserManagementTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    public function test_only_admin_and_the_page_never_shows_hashes(): void
    {
        $this->actingAs($this->admin)->get('/master/users')->assertOk()->assertSee('PIC_HCS')->assertDontSee('$2y$', false);

        foreach ([$this->picHcs, $this->gudang] as $user) {
            $this->actingAs($user)->get('/master/users')->assertForbidden();
            $this->actingAs($user)->post('/master/users', $this->payload())->assertForbidden();
            $this->actingAs($user)->post("/master/users/{$this->picFat->id}/reset-password", ['password' => 'baru123'])->assertForbidden();
            $this->actingAs($user)->delete("/master/users/{$this->picFat->id}")->assertForbidden();
        }
    }

    public function test_store_validates_and_hashes(): void
    {
        $this->actingAs($this->admin)->post('/master/users', $this->payload(['email' => ' Sari@Indraco.com ']))
            ->assertRedirect('/master/users')->assertSessionHas('success', 'Pengguna Sari berhasil ditambahkan.');
        $user = User::query()->where('email', 'sari@indraco.com')->firstOrFail();
        $this->assertSame(['pic_dept', $this->hcs], [$user->role, $user->department_id]);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertSame('USER_CREATE', DB::table('activity_logs')->value('action'));

        $this->actingAs($this->admin)->post('/master/users', $this->payload(['email' => 'SARI@indraco.com']))->assertSessionHasErrors('email');
        $this->actingAs($this->admin)->post('/master/users', $this->payload(['department_id' => '', 'email' => 'a@b.c']))->assertSessionHasErrors('department_id');
        $this->actingAs($this->admin)->post('/master/users', $this->payload(['role' => 'root', 'email' => 'b@b.c']))->assertSessionHasErrors('role');
        $this->actingAs($this->admin)->post('/master/users', $this->payload(['password' => '12345', 'email' => 'c@b.c']))->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->post('/master/users', $this->payload(['role' => 'pic_gudang', 'department_id' => '', 'email' => 'd@b.c']))->assertSessionHasNoErrors();
    }

    public function test_update_keeps_an_empty_password_and_refuses_changing_ones_own_role(): void
    {
        $hash = $this->picHcs->password;
        $this->actingAs($this->admin)->put("/master/users/{$this->picHcs->id}", $this->payload(['name' => 'PIC HCS Baru', 'email' => $this->picHcs->email, 'password' => '']))
            ->assertSessionHas('success', 'Data pengguna PIC HCS Baru berhasil diperbarui.');
        $this->assertSame($hash, $this->picHcs->fresh()->password);
        $this->actingAs($this->admin)->put("/master/users/{$this->picHcs->id}", $this->payload(['email' => $this->picFat->email]))->assertSessionHasErrors('email');

        $this->actingAs($this->admin)->put("/master/users/{$this->admin->id}", $this->payload(['email' => $this->admin->email, 'role' => 'pic_gudang']))
            ->assertSessionHas('error', 'Anda tidak dapat mengubah peran (role) akun Anda sendiri.');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_reset_password_from_both_pages(): void
    {
        $this->actingAs($this->admin)->postJson("/master/users/{$this->picHcs->id}/reset-password", ['password' => 'indraco123'])
            ->assertOk()->assertJson(['status' => 'success', 'message' => "Password untuk pengguna PIC_HCS ({$this->picHcs->email}) berhasil di-reset."]);
        $this->assertTrue(Hash::check('indraco123', $this->picHcs->fresh()->password));

        $this->actingAs($this->admin)->post("/master/users/{$this->picHcs->id}/reset-password", ['password' => '123'])
            ->assertSessionHasErrors(['password' => 'Password baru minimal 6 karakter.']);
        $log = DB::table('activity_logs')->where('action', 'USER_PASSWORD_RESET')->first();
        $this->assertStringNotContainsString('indraco123', (string) $log->properties.$log->description);
    }

    public function test_delete_refuses_self_and_users_with_history(): void
    {
        $this->actingAs($this->admin)->delete("/master/users/{$this->admin->id}")->assertSessionHas('error', 'Anda tidak dapat menghapus akun Anda sendiri.');

        DB::table('archives')->insert(['department_id' => $this->hcs, 'created_by_user_id' => $this->picHcs->id, 'title' => 'Box', 'status' => 'draft',
            'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => '1. Kontrak', 'periode' => '6 Bulan']);
        $this->actingAs($this->admin)->delete("/master/users/{$this->picHcs->id}")->assertSessionHas('error');
        $this->assertSame(1, DB::table('archives')->count(), 'the cascade did not delete the box');

        $this->actingAs($this->admin)->delete("/master/users/{$this->picFat->id}")->assertSessionHas('success', 'Pengguna PIC_FINANCE berhasil dihapus.');
        $this->assertNull(User::query()->find($this->picFat->id));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace(['name' => 'Sari', 'email' => 'sari@indraco.com', 'password' => 'password', 'role' => 'pic_dept', 'department_id' => $this->hcs, 'phone' => '0812'], $overrides);
    }
}
