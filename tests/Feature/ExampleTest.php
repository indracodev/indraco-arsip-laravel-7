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

    public function testHealthPingIsPubliclyAccessible()
    {
        $response = $this->get('/api/health/ping');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function testDiagnosticsEndpointsBlockedForGuestsAndRegularUsers()
    {
        // 1. Unauthenticated guest accessing UI gets redirected to login
        $guestUi = $this->get('/diagnostics');
        $guestUi->assertRedirect(route('login'));

        // 2. Unauthenticated guest accessing API gets 403 Forbidden
        $guestApi = $this->getJson('/api/health/metrics');
        $guestApi->assertStatus(403);
        $guestApi->assertJson(['status' => 'forbidden']);

        // 3. Regular non-admin user (e.g. pic_dept or pic_gudang) gets 403 Forbidden
        $regularUser = \App\Models\User::where('role', '!=', 'admin')->first();
        if ($regularUser) {
            $userUi = $this->actingAs($regularUser)->get('/diagnostics');
            $userUi->assertStatus(403);

            $userApi = $this->actingAs($regularUser)->getJson('/api/health/metrics');
            $userApi->assertStatus(403);
        }
    }

    public function testSuperAdminCanAccessDiagnosticsEndpoints()
    {
        $admin = \App\Models\User::where('email', 'admin@indraco.com')->first();
        $this->actingAs($admin);

        // UI Dashboard
        $uiResponse = $this->get('/diagnostics');
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee('DMS INDRACO Server Telemetry');
        $uiResponse->assertSee('Super Admin Diagnostic Verdict');

        // Metrics API
        $metricsResponse = $this->getJson('/api/health/metrics');
        $metricsResponse->assertStatus(200);
        $metricsResponse->assertJsonStructure([
            'status',
            'timestamp',
            'diagnosis' => ['health_score', 'overall_status', 'summary_for_ai'],
            'server' => ['name', 'ip', 'php_version', 'os', 'architecture'],
            'memory' => ['current_mb', 'peak_mb', 'limit'],
            'opcache' => ['enabled'],
            'database' => ['driver', 'db_size_kb'],
        ]);

        // Logs API
        $logsResponse = $this->getJson('/api/health/logs?lines=10');
        $logsResponse->assertStatus(200);
        $logsResponse->assertJsonStructure([
            'status',
            'file_exists',
            'file_size_kb',
            'lines',
        ]);

        // Probe IP API
        $probeResponse = $this->getJson('/api/health/probe-ip?target=127.0.0.1');
        $probeResponse->assertStatus(200);
        $probeResponse->assertJson(['target_ip' => '127.0.0.1']);
    }

    public function testDiagnosticKeyAllowsRemoteTelemetryAccess()
    {
        $token = substr(hash('sha256', config('app.key', 'indraco-secret')), 0, 16);

        // Via query parameter ?token=...
        $responseQuery = $this->getJson('/api/health/metrics?token=' . $token);
        $responseQuery->assertStatus(200);
        $responseQuery->assertJson(['status' => 'ok']);

        // Via Bearer token header
        $responseHeader = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/health/metrics');
        $responseHeader->assertStatus(200);
        $responseHeader->assertJson(['status' => 'ok']);
    }
}

