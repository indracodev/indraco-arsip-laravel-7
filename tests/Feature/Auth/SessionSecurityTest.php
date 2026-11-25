<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Security audit 2026-11-25: S2 (password change ends the account's other sessions),
 * S3 (Host header allow-list), S5 (request body too large -> Indonesian 413).
 */
class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    /** Opens a page as $user and returns the password fingerprint AuthenticateSession stored. */
    private function sessionHashOf(User $user): string
    {
        $this->actingAs($user)->get('/profile/password')->assertOk();

        return (string) session('password_hash_web');
    }

    /** A second device of $user whose session still holds $hash. */
    private function otherDevice(User $user, string $hash): TestResponse
    {
        $this->flushSession();
        auth()->forgetGuards();

        return $this->withSession(['password_hash_web' => $hash])->actingAs($user->fresh())->get('/profile/password');
    }

    public function test_own_password_change_keeps_this_session_and_ends_the_others(): void
    {
        $user = User::factory()->create();
        $oldHash = $this->sessionHashOf($user);

        $this->from('/profile/password')->post('/profile/change-password', ['current_password' => 'password', 'password' => 'baru123', 'password_confirmation' => 'baru123'])
            ->assertRedirect('/profile/password');
        $this->get('/profile/password')->assertOk();
        $this->assertAuthenticatedAs($user);

        $this->otherDevice($user, $oldHash)->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_reset_ends_the_users_sessions(): void
    {
        $user = User::factory()->create();
        $oldHash = $this->sessionHashOf($user);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->flushSession();
        $this->actingAs($admin)->postJson("/master/users/{$user->id}/reset-password", ['password' => 'indraco123'])->assertOk();

        $this->otherDevice($user, $oldHash)->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unchanged_password_keeps_the_other_session(): void
    {
        $user = User::factory()->create();

        $this->otherDevice($user, $this->sessionHashOf($user))->assertOk();
    }

    public function test_only_the_servers_own_host_names_are_answered(): void
    {
        $this->app['env'] = 'production'; // TrustHosts is skipped while testing
        config(['app.allowed_hosts' => ['localhost', '127.0.0.1', '192.168.2.180']]);

        $this->get('http://evil.example/login')->assertStatus(400);
        $this->get('http://192.168.2.180/login')->assertOk();
        $this->get('http://127.0.0.1:8000/login')->assertOk();
    }

    public function test_body_above_post_max_size_gets_an_indonesian_413(): void
    {
        $server = ['CONTENT_LENGTH' => (string) (200 * 1024 * 1024)];

        $this->call('POST', '/login', [], [], [], $server)->assertStatus(413)->assertSee('Data Terlalu Besar');
        $this->call('POST', '/login', [], [], [], $server + ['HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(413)->assertJson(['message' => 'Ukuran data yang dikirim terlalu besar (maksimal 20 MB per pengiriman). Kurangi jumlah atau ukuran file lalu coba lagi.']);
    }
}
