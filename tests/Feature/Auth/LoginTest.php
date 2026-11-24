<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_ERROR = 'Kredensial email atau password yang dimasukkan salah.';

    /** A user row exactly as Laravel 7 stored it (bcrypt cost 10, no Eloquent). */
    private function legacyUser(string $role = 'pic_dept'): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'PIC_FIN',
            'email' => 'fin@indraco.com',
            'password' => password_hash('password', PASSWORD_BCRYPT, ['cost' => 10]),
            'role' => $role,
        ]);
    }

    public function test_login_page_has_no_prefilled_credentials(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('NAMA PENGGUNA / EMAIL')
            ->assertDontSee('admin@indraco.com')
            ->assertDontSee('value="password"', false);
    }

    public function test_laravel7_user_can_log_in_and_the_session_is_regenerated(): void
    {
        $id = $this->legacyUser();
        $this->get('/login');
        $sessionBefore = session()->getId();

        $this->post('/login', ['email' => 'fin@indraco.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Selamat datang kembali, PIC_FIN');

        $this->assertAuthenticatedAs(User::findOrFail($id));
        $this->assertNotSame($sessionBefore, session()->getId());
        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGIN', 'module' => 'AUTH', 'user_id' => $id]);
        // Laravel rehashes the old cost-10 hash on login; the password keeps working.
        $this->assertTrue(Hash::check('password', (string) DB::table('users')->where('id', $id)->value('password')));
    }

    public function test_email_is_matched_with_the_unique_index_normalization(): void
    {
        $id = $this->legacyUser();

        $this->post('/login', ['email' => '  FIN@Indraco.com ', 'password' => 'password'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::findOrFail($id));
        $this->assertSame('new@indraco.com', User::factory()->create(['email' => ' New@Indraco.COM'])->email);
    }

    public function test_wrong_password_gives_a_generic_error_and_is_logged_without_the_password(): void
    {
        $this->legacyUser();

        $this->from('/login')
            ->withHeader('User-Agent', 'UjiBrowser/1.0')
            ->post('/login', ['email' => 'fin@indraco.com', 'password' => 'S4lah-Rahasia'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::GENERIC_ERROR])
            ->assertSessionHasInput('email', 'fin@indraco.com')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $log = ActivityLog::where('action', 'LOGIN_FAILED')->sole();
        $this->assertSame('fin@indraco.com', $log->user_name);
        $this->assertSame('guest', $log->user_role);
        $this->assertSame(['attempted_email' => 'fin@indraco.com'], $log->properties);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame('UjiBrowser/1.0', $log->user_agent);
        $this->assertNotNull($log->created_at);
        $this->assertStringNotContainsString('S4lah-Rahasia', json_encode($log->getAttributes()) ?: '');
    }

    public function test_unknown_email_gets_the_same_error(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'tidak-ada@indraco.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => self::GENERIC_ERROR]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGIN_FAILED', 'user_name' => 'tidak-ada@indraco.com']);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionHas('info', 'Anda telah berhasil keluar dari sistem.');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGOUT', 'user_id' => $user->id]);
    }

    public function test_guests_are_sent_to_login_and_users_away_from_it(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('dashboard'));
    }
}
