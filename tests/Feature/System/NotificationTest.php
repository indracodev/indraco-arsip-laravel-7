<?php

namespace Tests\Feature\System;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Services\SystemEventStream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Borrowing\BorrowingFixtures;
use Tests\TestCase;

/**
 * Realtime poll (/api/realtime/check), menu badges, expiry popup list and system_events pruning (B10).
 */
class NotificationTest extends TestCase
{
    use BorrowingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBorrowingFixtures();
    }

    private function submitted(int $departmentId, string $title, int $creatorId, string $event = 'archive_submitted'): Archive
    {
        $id = DB::table('archives')->insertGetId(['department_id' => $departmentId, 'created_by_user_id' => $creatorId, 'title' => $title, 'status' => 'pending_verification', 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => '-', 'created_at' => now(), 'updated_at' => now()]);
        $archive = Archive::query()->with('department')->findOrFail($id);
        SystemEventStream::archives($event, [$archive]);

        return $archive;
    }

    /** @return array<string, mixed> */
    private function poll(mixed $user, int $since): array
    {
        return $this->actingAs($user)->getJson('/api/realtime/check?since_seq='.$since)->assertOk()->json();
    }

    public function test_initial_poll_returns_the_cursor_and_badges_without_notifications(): void
    {
        $this->submitted($this->hcs, 'Box HCS', $this->picHcs->id);

        $data = $this->actingAs($this->gudang)->getJson('/api/realtime/check?initial=1')->assertOk()->json();

        $this->assertSame(SystemEventStream::latestId(), $data['latest_seq']);
        $this->assertSame([], $data['notifications']);
        $this->assertSame(1, $data['badges']['archives.index']);
    }

    public function test_idle_poll_runs_no_badge_queries(): void
    {
        $this->submitted($this->hcs, 'Box HCS', $this->picHcs->id);
        $latest = SystemEventStream::latestId();

        $data = $this->poll($this->gudang, $latest);

        $this->assertFalse($data['changed']);
        $this->assertArrayNotHasKey('badges', $data);
    }

    public function test_pic_departemen_never_sees_events_of_another_department(): void
    {
        $since = SystemEventStream::latestId();
        $this->submitted($this->fat, 'Box Rahasia FAT', $this->picFat->id);
        $this->submitted($this->hcs, 'Box HCS Baru', $this->admin->id);

        $hcs = $this->poll($this->picHcs, $since);
        $this->assertSame(['Box HCS Baru'], array_column($hcs['notifications'], 'title'));
        $this->assertStringNotContainsString('Rahasia', json_encode($hcs));
        $this->assertSame(1, $hcs['badges']['archives.index'], 'badge counts only the own department');

        $events = SystemEventStream::visibleTo($this->picHcs, $since);
        $this->assertSame([$this->hcs], $events->pluck('department_id')->unique()->values()->all());

        $warehouse = $this->poll($this->gudang, $since);
        $this->assertCount(2, $warehouse['notifications']);
        $this->assertSame('Pengajuan Box Arsip Masuk', $warehouse['notifications'][0]['title']);
        $this->assertSame(2, $warehouse['badges']['archives.index']);
    }

    public function test_own_actions_and_drafts_do_not_notify(): void
    {
        $since = SystemEventStream::latestId();
        $this->submitted($this->hcs, 'Box Saya', $this->picHcs->id);
        $this->submitted($this->hcs, 'Draft Rekan', $this->admin->id, 'archive_draft_created');

        $data = $this->poll($this->picHcs, $since);

        $this->assertTrue($data['changed']);
        $this->assertSame([], $data['notifications']);
    }

    public function test_new_penarikan_request_notifies_the_warehouse_but_not_pic_departemen(): void
    {
        $box = $this->storedBox($this->hcs, 'BOX-A');
        $since = SystemEventStream::latestId();
        $colleague = User::factory()->create(['role' => 'pic_dept', 'department_id' => $this->hcs]);
        $this->requested([$box]);

        $this->assertSame('Permohonan Penarikan Berkas Masuk', $this->poll($this->gudang, $since)['notifications'][0]['title']);
        $this->assertSame(1, $this->poll($this->gudang, $since)['badges']['borrowings.index']);
        $this->assertSame([], $this->poll($colleague, $since)['notifications']);
        $this->assertSame([], $this->poll($this->picHcs, $since)['notifications'], 'never for the requester');

        // A status change is shown to the department (not to the gudang user who made it).
        $log = BorrowingLog::query()->with(['archive.department', 'borrower'])->latest('id')->firstOrFail();
        $since = SystemEventStream::latestId();
        SystemEventStream::borrowings('borrowing_updated', [$log], $this->gudang->id);
        $this->assertSame('Status Penarikan Berkas Berubah', $this->poll($this->picHcs, $since)['notifications'][0]['title']);
        $this->assertSame([], $this->poll($this->gudang, $since)['notifications']);
    }

    public function test_expiring_list_uses_the_h30_end_of_month_rule_and_the_department_scope(): void
    {
        $make = fn (int $dept, string $title, string $expiry, string $status = 'in_warehouse') => DB::table('archives')->insertGetId([
            'department_id' => $dept, 'created_by_user_id' => $this->admin->id, 'title' => $title, 'status' => $status, 'retention_expiry_date' => $expiry, 'period_start_date' => '2026-01-01', 'period_end_date' => '2026-06-30', 'content_description' => '-', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $make($this->hcs, 'Lewat', '2026-09-15');          // ended 30 Sep
        $make($this->hcs, 'H-6', '2026-11-02');            // ends 30 Nov
        $make($this->hcs, 'Desember', '2026-12-10');       // ends 31 Dec = today + 37 days: not yet
        $make($this->hcs, 'Draft', '2026-01-01', 'draft');
        $make($this->fat, 'FAT Lewat', '2026-01-01');

        $hcs = $this->actingAs($this->picHcs)->getJson('/api/archives/expiring-list')->assertOk()->json();
        $this->assertSame(2, $hcs['count']);
        $this->assertSame(['Lewat', 'H-6'], array_column($hcs['items'], 'title'));
        $this->assertSame(['LEWAT 54 HARI', 'H-6 HARI'], array_column($hcs['items'], 'badge_text'));

        $this->assertSame(3, $this->actingAs($this->gudang)->getJson('/api/archives/expiring-list')->json('count'));
        $this->assertSame(2, $this->actingAs($this->picHcs)->getJson('/api/realtime/check?initial=1')->json('badges')['destructions.index']);
    }

    public function test_system_events_are_pruned_to_the_latest_2000(): void
    {
        $rows = array_fill(0, 2199, ['event_type' => 'archive_updated', 'entity_type' => 'Archive', 'created_at' => now()]);
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('system_events')->insert($chunk);
        }

        $this->submitted($this->hcs, 'Box 2200', $this->picHcs->id); // id 2200 crosses a 200 boundary

        $this->assertSame(2200, SystemEventStream::latestId());
        $this->assertSame(200, (int) DB::table('system_events')->min('id'));
        $this->assertSame(2001, DB::table('system_events')->count());
    }

    public function test_poll_requires_login(): void
    {
        $this->getJson('/api/realtime/check')->assertUnauthorized();
        $this->getJson('/api/archives/expiring-list')->assertUnauthorized();
    }
}
