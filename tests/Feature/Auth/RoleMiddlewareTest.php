<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin', fn () => 'ok');
        Route::middleware(['web', 'auth', 'role:pic_gudang'])->get('/_test/gudang', fn () => 'ok');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_admin_passes_every_role_check(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/_test/admin')->assertOk();
        $this->actingAs($admin)->get('/_test/gudang')->assertOk();
    }

    public function test_listed_role_passes(): void
    {
        $this->actingAs($this->user('pic_gudang'))->get('/_test/gudang')->assertOk();
    }

    public function test_other_roles_get_403(): void
    {
        $this->actingAs($this->user('pic_dept'))->get('/_test/admin')
            ->assertForbidden()
            ->assertSee('Akses tidak diizinkan untuk peran pengguna Anda.');
        $this->actingAs($this->user('pic_gudang'))->get('/_test/admin')->assertForbidden();
        $this->actingAs($this->user('pic_dept'))->get('/_test/gudang')->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/_test/admin')->assertRedirect(route('login'));
    }
}
