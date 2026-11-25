<?php

namespace Tests\Feature\Auth;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Services\SystemEventStream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression guards from the security audit (.agents/context/security-checklist.md).
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    /** Routes that are public on purpose: login form + the launcher / client health checks. */
    private const PUBLIC_ROUTES = ['login', 'login.post', 'health.ping', 'health.ready'];

    public function test_every_route_except_login_and_health_requires_login(): void
    {
        $open = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (RoutingRoute $route): bool => in_array('auth', $route->gatherMiddleware(), true))
            ->map(fn (RoutingRoute $route): string => (string) ($route->getName() ?? $route->uri()))
            ->values()->all();

        $this->assertEqualsCanonicalizing(self::PUBLIC_ROUTES, $open);
    }

    public function test_pic_without_department_sees_no_archive_and_no_department_less_event(): void
    {
        $department = Department::query()->create(['code' => 'AUD', 'name' => 'AUDIT Dept', 'retention_years' => 5, 'is_active' => true]);
        $creator = User::factory()->create(['role' => User::ROLE_PIC_DEPT, 'department_id' => $department->id]);
        DB::table('archives')->insert(['department_id' => $department->id, 'created_by_user_id' => $creator->id, 'title' => 'AUDIT box',
            'status' => 'in_warehouse', 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => '-', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('system_events')->insert(['event_type' => 'archive_deleted', 'department_id' => null, 'created_at' => now()]);

        // PIC released from its department ("Lepas PIC") keeps role pic_dept with department_id null.
        $released = User::factory()->create(['role' => User::ROLE_PIC_DEPT, 'department_id' => null]);

        $this->assertSame(0, Archive::query()->visibleTo($released)->count());
        $this->assertCount(0, SystemEventStream::visibleTo($released, 0));
        $this->assertCount(1, SystemEventStream::visibleTo(User::factory()->create(['role' => User::ROLE_ADMIN]), 0));
    }
}
