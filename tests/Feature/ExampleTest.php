<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function testSuperAdminLoginAndDashboardAccess()
    {
        $response = $this->post('/login', [
            'email' => 'admin@indraco.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs(\App\Models\User::where('email', 'admin@indraco.com')->first())
            ->get(route('dashboard'));

        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Layout Gudang');
    }
}
