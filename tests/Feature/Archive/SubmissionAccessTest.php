<?php

namespace Tests\Feature\Archive;

use App\Actions\Archive\EnsureArchiveUnchanged;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Box submission security and integrity: role + department checks (findings #4/B3), double
 * submit, optimistic lock, draft deletion scope, similarity endpoint scope.
 */
class SubmissionAccessTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
    }

    public function test_pic_gudang_cannot_create(): void
    {
        $this->actingAs($this->gudang)->get('/archives/create')->assertForbidden();
        $this->actingAs($this->gudang)->post('/archives', $this->submission())->assertForbidden();
        $this->actingAs($this->gudang)->post('/archives/batch-delete-drafts', ['ids' => [1]])->assertForbidden();
    }

    public function test_pic_cannot_use_a_sub_department_of_another_department(): void
    {
        $fatSub = DB::table('sub_departments')->insertGetId(['department_id' => $this->fat, 'code' => 'TAX', 'name' => 'Tax', 'is_active' => 1]);
        $payload = $this->submission();
        $payload['boxes'][0]['sub_department_id'] = (string) $fatSub;

        $this->actingAs($this->picHcs)->post('/archives', $payload)->assertSessionHasErrors('boxes.0.sub_department_id');
    }

    public function test_pic_can_edit_only_drafts_of_their_own_department(): void
    {
        $otherDraft = $this->draftId($this->picFat);
        $ownDraft = $this->draftId($this->picHcs);
        $this->actingAs($this->picHcs)->post('/archives', $this->submission())->assertRedirect('/archives');
        $ownSubmitted = (int) DB::table('archives')->where('status', 'pending_verification')->value('id');

        foreach ([$otherDraft, $ownSubmitted] as $id) {
            $this->actingAs($this->picHcs)->get("/archives/{$id}/edit")->assertForbidden();
            $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['original_updated_at' => $this->updatedAt($id)]))->assertForbidden();
        }
        $this->actingAs($this->picHcs)->delete("/archives/{$otherDraft}")->assertForbidden();
        $this->actingAs($this->picHcs)->get("/archives/{$ownDraft}/edit")->assertOk();
        $this->actingAs($this->admin)->get("/archives/{$ownSubmitted}/edit")->assertOk()->assertSee('Simpan Perubahan Berkas');
        $this->actingAs($this->gudang)->get("/archives/{$ownDraft}/edit")->assertForbidden();
    }

    public function test_the_same_submission_sent_twice_creates_the_boxes_once(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission())->assertRedirect('/archives');
        $this->actingAs($this->picHcs)->from('/archives/create')->post('/archives', $this->submission())
            ->assertRedirect('/archives/create')
            ->assertSessionHas('error', 'Nama / Judul Box "Box Kontrak 2026" sudah digunakan oleh arsip lain  (Status: Menunggu Verifikasi). Harap gunakan nama box yang berbeda.');

        $this->assertSame(2, DB::table('archives')->count());
        $this->assertCount(1, Storage::disk('local')->allFiles('archive_scans'), 'the refused upload is removed again');
    }

    public function test_submitting_the_same_draft_twice_is_refused(): void
    {
        $id = $this->draftId($this->picHcs);
        $opened = $this->updatedAt($id);
        $this->travel(2)->seconds();
        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['original_updated_at' => $opened]))->assertRedirect('/archives');

        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['original_updated_at' => $opened]))->assertForbidden();
        $this->actingAs($this->admin)->from("/archives/{$id}/edit")->put("/archives/{$id}", $this->submission(['original_updated_at' => $opened, 'department_id' => $this->hcs]))
            ->assertRedirect("/archives/{$id}/edit")->assertSessionHas('error', EnsureArchiveUnchanged::CONFLICT);
        $this->assertSame(2, DB::table('archives')->count());
    }

    public function test_optimistic_lock_refuses_a_stale_form(): void
    {
        $id = $this->draftId($this->picHcs);
        $stale = $this->updatedAt($id);
        $this->travel(3)->seconds();
        DB::table('archives')->where('id', $id)->update(['title' => 'Diubah user lain', 'updated_at' => now()->format('Y-m-d H:i:s')]);

        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['submit_action' => 'draft', 'original_updated_at' => $stale]))
            ->assertSessionHas('error', EnsureArchiveUnchanged::CONFLICT);
        $this->assertSame('Diubah user lain', DB::table('archives')->where('id', $id)->value('title'));
        $this->actingAs($this->picHcs)->put("/archives/{$id}", $this->submission(['submit_action' => 'draft']))->assertSessionHasErrors('original_updated_at');
    }

    public function test_batch_delete_removes_only_own_drafts_with_their_files(): void
    {
        $own = $this->draftId($this->picHcs, ['scan_input_form' => $this->pdf()]);
        $scan = (string) DB::table('archives')->where('id', $own)->value('scan_input_form');
        $other = $this->draftId($this->picFat);
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['boxes' => [$this->submission()['boxes'][1]]]));
        $submitted = (int) DB::table('archives')->where('status', 'pending_verification')->value('id');

        $this->actingAs($this->picHcs)->post('/archives/batch-delete-drafts', ['ids' => "{$own},{$other},{$submitted}"])
            ->assertRedirect('/archives')->assertSessionHas('success', 'Berhasil menghapus 1 data draft arsip.');

        $this->assertDatabaseMissing('archives', ['id' => $own]);
        $this->assertDatabaseMissing('archive_items', ['archive_id' => $own]);
        $this->assertDatabaseHas('archives', ['id' => $other]);
        $this->assertDatabaseHas('archives', ['id' => $submitted]);
        Storage::disk('local')->assertMissing($scan);
        $log = ActivityLog::query()->latest('id')->first();
        $this->assertSame(['ARCHIVE_DRAFT_BATCH_DELETE', 'Batch 1 Draft'], [$log?->action, $log?->reference_id]);
        $this->assertSame('archive_deleted', DB::table('system_events')->latest('id')->value('event_type'));
    }

    public function test_single_delete_only_for_drafts(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['boxes' => [$this->submission()['boxes'][0]]]));
        $submitted = (int) DB::table('archives')->value('id');
        $draft = $this->draftId($this->picHcs);

        $this->actingAs($this->picHcs)->delete("/archives/{$submitted}")->assertSessionHas('error', 'Hanya data berstatus draft yang dapat dihapus.');
        $this->actingAs($this->picHcs)->delete("/archives/{$draft}")->assertRedirect('/archives')
            ->assertSessionHas('success', "Data draft arsip 'Box Kontrak 2026 (2 Box)' berhasil dihapus.");
        $this->assertSame('ARCHIVE_DRAFT_DELETE', ActivityLog::query()->latest('id')->value('action'));
        $this->assertDatabaseHas('archives', ['id' => $submitted]);
    }

    public function test_similarity_check_uses_the_pic_department_only(): void
    {
        $this->actingAs($this->picFat)->post('/archives', $this->submission(['boxes' => [$this->submission()['boxes'][0]]]));

        $this->actingAs($this->picHcs)->postJson('/api/archives/check-similarity', ['title' => 'Box Kontrak 2026', 'department_id' => $this->fat])
            ->assertOk()->assertJson(['has_similarity' => false]);
        $this->actingAs($this->picFat)->postJson('/api/archives/check-similarity', ['title' => 'box kontrak 2026'])
            ->assertOk()->assertJson(['has_similarity' => true, 'is_duplicate' => true, 'match_type' => 'identical']);
        $this->actingAs($this->picFat)->postJson('/api/archives/check-similarity', ['title' => 'Box Kontrak 2025'])
            ->assertOk()->assertJson(['has_similarity' => true, 'is_duplicate' => false, 'match_type' => 'similar']);
    }
}
