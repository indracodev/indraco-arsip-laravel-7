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

    public function testHealthPingEndpointReturnsFastJsonResponse()
    {
        $response = $this->get('/api/health/ping');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'server_time',
            'server_ip',
            'server_name',
            'client_ip',
            'app',
            'version',
        ]);
        $response->assertJson(['status' => 'ok']);
    }

    public function testHealthMetricsEndpointReturnsSystemTelemetry()
    {
        $response = $this->get('/api/health/metrics');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'server' => ['name', 'ip', 'php_version', 'os', 'architecture'],
            'client' => ['ip'],
            'memory' => ['current_mb', 'peak_mb', 'limit'],
            'opcache' => ['enabled'],
            'database' => ['driver', 'db_size_kb'],
        ]);
    }

    public function testHealthLogsEndpointReturnsServerLogs()
    {
        $response = $this->get('/api/health/logs?lines=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'file_exists',
            'file_size_kb',
            'lines',
        ]);
    }

    public function testHealthProbeIpEndpoint()
    {
        $response = $this->get('/api/health/probe-ip?target=127.0.0.1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'target_ip',
            'is_reachable',
            'server_ip',
        ]);
        $response->assertJson(['target_ip' => '127.0.0.1']);
    }

    public function testDiagnosticsDashboardViewIsAccessible()
    {
        $response = $this->get('/diagnostics');
        $response->assertStatus(200);
        $response->assertSee('DMS INDRACO Server Telemetry');
        $response->assertSee('storage/logs/laravel.log');
    }
}

