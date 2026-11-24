<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function submit(User $user, array $data): TestResponse
    {
        return $this->actingAs($user)->from('/')->post('/profile/change-password', $data);
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create();

        $this->submit($user, ['current_password' => 'password', 'password' => 'baru123', 'password_confirmation' => 'baru123'])
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Password Anda berhasil diperbarui.');

        $this->assertTrue(Hash::check('baru123', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'PASSWORD_CHANGE', 'user_id' => $user->id]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->submit($user, ['current_password' => 'keliru', 'password' => 'baru123', 'password_confirmation' => 'baru123'])
            ->assertSessionHasErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_validation_rules_and_messages_match_laravel7(): void
    {
        $user = User::factory()->create();

        $this->submit($user, [])->assertSessionHasErrors([
            'current_password' => 'Password saat ini wajib diisi.',
            'password' => 'Password baru wajib diisi.',
        ]);
        $this->submit($user, ['current_password' => 'password', 'password' => '12345', 'password_confirmation' => '12345'])
            ->assertSessionHasErrors(['password' => 'Password baru minimal 6 karakter.']);
        $this->submit($user, ['current_password' => 'password', 'password' => 'baru123', 'password_confirmation' => 'lain123'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi password baru tidak cocok.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_json_request_gets_json(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/profile/change-password', ['current_password' => 'password', 'password' => 'baru123', 'password_confirmation' => 'baru123'])
            ->assertOk()
            ->assertExactJson(['status' => 'success', 'message' => 'Password Anda berhasil diperbarui.']);
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->post('/profile/change-password', [])->assertRedirect(route('login'));
    }
}
