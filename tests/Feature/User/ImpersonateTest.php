<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * "Login As" (Laravel 7 UserController@impersonate / @leaveImpersonate, decisions ASK 6):
 * admin only, logged, never into an admin account, "leave" returns to the admin.
 */
class ImpersonateTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    public function test_admin_logs_in_as_a_pic_and_leaves_again(): void
    {
        $this->actingAs($this->admin)->post("/master/users/{$this->picHcs->id}/impersonate")
            ->assertRedirect('/')->assertSessionHas('impersonator_id', $this->admin->id)
            ->assertSessionHas('success', 'Mode Impersonasi Aktif: Anda sekarang berinteraksi sebagai PIC_HCS (PIC Departemen).');
        $this->assertAuthenticatedAs($this->picHcs);

        $this->get('/archives')->assertOk()->assertSee('Mode Impersonasi Aktif')->assertSee('Kembali ke SuperAdmin');
        $this->get('/master/users')->assertForbidden();
        $this->post("/master/users/{$this->picFat->id}/impersonate")->assertForbidden();

        $this->post('/impersonate/leave')->assertRedirect('/master/users')
            ->assertSessionMissing('impersonator_id')->assertSessionHas('success', "Selesai Impersonasi: Anda telah kembali ke akun SuperAdmin {$this->admin->name}.");
        $this->assertAuthenticatedAs($this->admin);
        $this->assertSame(['IMPERSONATE_START', 'IMPERSONATE_LEAVE'], DB::table('activity_logs')->orderBy('id')->pluck('action')->all());
        $this->assertSame($this->admin->id, (int) DB::table('activity_logs')->where('action', 'IMPERSONATE_START')->value('user_id'));
    }

    public function test_no_impersonation_of_self_or_another_admin_and_not_by_others(): void
    {
        $otherAdmin = $this->admin->replicate()->fill(['email' => 'admin2@indraco.com']);
        $otherAdmin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($this->admin)->post("/master/users/{$this->admin->id}/impersonate")->assertSessionHas('error', 'Anda tidak dapat meng-impersonasi akun Anda sendiri.');
        $this->actingAs($this->admin)->post("/master/users/{$otherAdmin->id}/impersonate")->assertSessionHas('error', 'Akun Super Admin lain tidak dapat di-impersonasi.');
        $this->assertAuthenticatedAs($this->admin);
        $this->actingAs($this->gudang)->post("/master/users/{$this->picHcs->id}/impersonate")->assertForbidden();
        $this->assertSame(0, DB::table('activity_logs')->where('action', 'IMPERSONATE_START')->count());
    }

    public function test_leave_without_impersonation_or_with_a_demoted_admin(): void
    {
        $this->actingAs($this->picHcs)->post('/impersonate/leave')->assertRedirect('/');
        $this->assertAuthenticatedAs($this->picHcs);

        // A forged / stale session pointing at a non-admin never logs into that account.
        $this->actingAs($this->picHcs)->withSession(['impersonator_id' => $this->gudang->id])->post('/impersonate/leave')->assertRedirect('/login');
        $this->assertGuest();
    }
}
