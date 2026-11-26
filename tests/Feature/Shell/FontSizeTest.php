<?php

namespace Tests\Feature\Shell;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Font size per account (users.font_size): "Simpan" in Pengaturan Tampilan posts it, the layout
 * renders it on <html> for public/js/prefs.js (before first paint, seeds localStorage). Pages
 * without login keep using the browser value only.
 */
class FontSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_font_size_is_rendered_for_prefs_js_on_the_next_page(): void
    {
        $user = User::factory()->create(['role' => 'pic_dept']);
        $this->actingAs($user)->get('/profile/password')->assertOk()->assertDontSee('data-user-font-size', false);

        $this->actingAs($user)->postJson('/profile/font-size', ['font_size' => 23])
            ->assertOk()->assertJson(['status' => 'success', 'font_size' => 23]);

        $this->assertSame(23, $user->fresh()?->font_size);
        $this->actingAs($user)->get('/profile/password')->assertSee('data-user-font-size="23"', false);
        $prefs = (string) file_get_contents(public_path('js/prefs.js'));
        $this->assertStringContainsString("getAttribute('data-user-font-size')", $prefs);
        $this->assertStringContainsString("'/profile/font-size'", (string) file_get_contents(public_path('js/app.js')));
    }

    public function test_font_size_must_be_between_10_and_26(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        foreach ([9, 27, 'besar', null] as $invalid) {
            $this->actingAs($user)->postJson('/profile/font-size', ['font_size' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('font_size');
        }
        $this->actingAs($user)->postJson('/profile/font-size', ['font_size' => 10])->assertOk();
        $this->actingAs($user)->postJson('/profile/font-size', ['font_size' => 26])->assertOk();
        $this->assertSame(26, $user->fresh()?->font_size);
    }

    public function test_guest_cannot_save_and_the_login_page_uses_the_browser_value_only(): void
    {
        $this->post('/profile/font-size', ['font_size' => 20])->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertDontSee('data-user-font-size', false);
    }

    public function test_default_font_size_is_always_14px_regardless_of_legacy_app_setting(): void
    {
        AppSetting::query()->updateOrCreate(['key' => 'app_font_size'], ['value' => '17px']);

        $this->get('/login')->assertOk()->assertSee('data-font-size="14px"', false);
        $this->actingAs(User::factory()->create(['role' => 'pic_dept']))
            ->get('/profile/password')->assertOk()
            ->assertSee('data-font-size="14px"', false)
            ->assertDontSee('data-user-font-size', false);
    }
}
