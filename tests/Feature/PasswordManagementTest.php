<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $picDept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $dept = Department::first() ?? Department::create([
            'name' => 'FAT Department',
            'code' => 'FAT',
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin_test_' . uniqid() . '@indraco.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->picDept = User::create([
            'name' => 'PIC Dept Test',
            'email' => 'pic_test_' . uniqid() . '@indraco.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'pic_dept',
            'department_id' => $dept->id,
        ]);
    }

    public function test_user_can_change_own_password_with_correct_current_password()
    {
        $response = $this->actingAs($this->picDept)
            ->from('/dashboard')
            ->post(route('profile.change_password'), [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHas('success');
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->picDept->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $response = $this->actingAs($this->picDept)
            ->post(route('profile.change_password'), [
                'current_password' => 'salah_password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('oldpassword', $this->picDept->password));
    }

    public function test_user_cannot_change_password_if_confirmation_mismatches()
    {
        $response = $this->actingAs($this->picDept)
            ->post(route('profile.change_password'), [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'beda_konfirmasi',
            ]);

        $response->assertSessionHasErrors(['password']);
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('oldpassword', $this->picDept->password));
    }

    public function test_superadmin_can_reset_any_user_password()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('master.users.reset_password', $this->picDept->id), [
                'password' => 'indraco123',
            ]);

        $response->assertSessionHas('success');
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('indraco123', $this->picDept->password));
    }

    public function test_non_admin_cannot_reset_other_user_password()
    {
        $otherUser = User::create([
            'name' => 'Target User Test',
            'email' => 'target_' . uniqid() . '@indraco.com',
            'password' => Hash::make('targetpass'),
            'role' => 'pic_dept',
        ]);

        $response = $this->actingAs($this->picDept)
            ->post(route('master.users.reset_password', $otherUser->id), [
                'password' => 'hacked123',
            ]);

        // Role middleware restricts to admin (throws 403 or redirect)
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));
        $otherUser->refresh();
        $this->assertTrue(Hash::check('targetpass', $otherUser->password));
    }

    public function test_superadmin_can_reset_pic_password_via_json_request()
    {
        $response = $this->actingAs($this->admin)
            ->json('POST', route('master.users.reset_password', $this->picDept->id), [
                'password' => 'secret789',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('secret789', $this->picDept->password));
    }

    public function test_admin_reset_password_validates_min_characters_json()
    {
        $response = $this->actingAs($this->admin)
            ->json('POST', route('master.users.reset_password', $this->picDept->id), [
                'password' => '123',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_change_password_via_json_request()
    {
        $response = $this->actingAs($this->picDept)
            ->json('POST', route('profile.change_password'), [
                'current_password' => 'oldpassword',
                'password' => 'newsecret456',
                'password_confirmation' => 'newsecret456',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->picDept->refresh();
        $this->assertTrue(Hash::check('newsecret456', $this->picDept->password));
    }
}
