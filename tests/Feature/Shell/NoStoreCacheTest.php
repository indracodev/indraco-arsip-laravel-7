<?php

namespace Tests\Feature\Shell;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R50: logged-in pages are never stored by the browser (Back after logout on a shared PC).
 */
class NoStoreCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_page_and_login_page_are_sent_with_no_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $page = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertSame('no-store, private', $page->headers->get('Cache-Control'));

        auth()->logout();
        $login = $this->get('/login')->assertOk();
        $this->assertStringContainsString('no-store', (string) $login->headers->get('Cache-Control'));
    }
}
