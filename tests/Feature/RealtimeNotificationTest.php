<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    public function test_guest_cannot_access_realtime_check()
    {
        $response = $this->getJson(route('api.realtime.check'));
        $response->assertStatus(401);
    }

    public function test_superadmin_can_check_realtime_initial_baseline()
    {
        $admin = User::where('role', 'admin')->first() ?? factory(User::class)->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson(route('api.realtime.check') . '?initial=1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'has_new',
            'latest_id',
            'new_archives',
            'count',
            'stats' => ['total', 'pending', 'in_warehouse', 'borrowed']
        ]);
        $response->assertJson([
            'has_new' => false,
            'new_archives' => []
        ]);
    }

    public function test_superadmin_detects_newly_added_document()
    {
        $admin = User::where('role', 'admin')->first() ?? factory(User::class)->create(['role' => 'admin']);
        $dept = Department::first();
        $pic = User::where('role', 'pic_dept')->first() ?? factory(User::class)->create(['role' => 'pic_dept', 'department_id' => $dept->id]);

        $initialLatestId = Archive::max('id') ?? 0;

        // Client adds a new archive
        $archive = Archive::create([
            'title' => 'Dokumen Realtime Test UAT ' . uniqid(),
            'department_id' => $dept->id,
            'created_by_user_id' => $pic->id,
            'status' => 'pending_verification',
            'box_number' => 'BOX-TEST-' . uniqid(),
            'periode_doc' => '2026/09',
            'period_start_date' => '2026-09-01',
            'period_end_date' => '2026-09-30',
            'content_description' => 'Isi dokumen berkas keuangan realtime',
            'retention_years' => 5,
        ]);

        // Super Admin poller checks since initialLatestId
        $response = $this->actingAs($admin)->getJson(route('api.realtime.check') . '?last_id=' . $initialLatestId);
        $response->assertStatus(200);
        $response->assertJson([
            'has_new' => true,
        ]);
        $this->assertGreaterThanOrEqual(1, count($response->json('new_archives')));
        $this->assertEquals($archive->id, $response->json('new_archives.0.id'));
        $this->assertEquals($archive->title, $response->json('new_archives.0.title'));
    }
}
