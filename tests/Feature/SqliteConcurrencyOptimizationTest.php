<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Department;
use App\Models\NumberingFormat;
use App\Models\User;
use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SqliteConcurrencyOptimizationTest extends TestCase
{
    protected $admin;
    protected $dept;
    protected $createdArchiveIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->dept = Department::first() ?? Department::create([
            'name' => 'IT Department',
            'code' => 'ITD',
        ]);

        $this->admin = User::create([
            'name' => 'Concurrency Admin',
            'email' => 'admin_concurrency_' . uniqid() . '@indraco.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'department_id' => $this->dept->id,
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdArchiveIds)) {
            Archive::whereIn('id', $this->createdArchiveIds)->forceDelete();
        }

        if ($this->admin) {
            $this->admin->forceDelete();
        }

        parent::tearDown();
    }

    protected function makeTestArchive(array $attributes = []): Archive
    {
        $archive = Archive::create(array_merge([
            'title' => 'Test Concurrency Archive',
            'department_id' => $this->dept->id,
            'created_by_user_id' => $this->admin->id,
            'status' => 'draft',
            'content_description' => 'Uraian pengujian konkurensi',
            'tgl_penyerahan' => date('Y-m-d'),
            'physical_condition' => 'Baik',
            'period_start_date' => date('Y-01-01'),
            'period_end_date' => date('Y-12-31'),
        ], $attributes));

        $this->createdArchiveIds[] = $archive->id;
        return $archive;
    }

    public function test_numbering_service_collision_guard_auto_increments()
    {
        $format = NumberingFormat::first();
        if (!$format) {
            $format = NumberingFormat::create([
                'name' => 'Format Box DMS',
                'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}',
                'padding' => 4,
                'current_counter' => 10,
                'is_active' => true,
            ]);
        } else {
            $format->update([
                'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}',
                'padding' => 4,
                'current_counter' => 10,
                'is_active' => true,
            ]);
        }

        $expectedCode1 = "IND/" . strtoupper($this->dept->code) . "/" . date('Y') . "/0011";

        $this->makeTestArchive([
            'box_number' => $expectedCode1,
            'title' => 'Arsip Pra-Ada',
            'status' => 'in_warehouse',
        ]);

        $newArchive = new Archive([
            'department_id' => $this->dept->id,
            'period_end_date' => now(),
        ]);
        $newArchive->setRelation('department', $this->dept);

        $numberingService = new NumberingService();
        $generatedCode = $numberingService->generateBoxCode($newArchive, false);

        $this->assertNotEquals($expectedCode1, $generatedCode);
        $expectedCode2 = "IND/" . strtoupper($this->dept->code) . "/" . date('Y') . "/0012";
        $this->assertEquals($expectedCode2, $generatedCode);
    }

    public function test_optimistic_locking_rejects_stale_update()
    {
        $archive = $this->makeTestArchive([
            'title' => 'Original Archive Title',
        ]);

        $staleTimestamp = Carbon::now()->subMinutes(10)->toDateTimeString();

        $response = $this->actingAs($this->admin)
            ->from(route('archives.edit', $archive->id))
            ->put(route('archives.update', $archive->id), [
                'title' => 'Concurrent Modified Title',
                'original_updated_at' => $staleTimestamp,
                'submit_action' => 'draft',
            ]);

        $response->assertRedirect(route('archives.edit', $archive->id));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Konflik Perubahan', session('error'));

        $this->assertEquals('Original Archive Title', $archive->fresh()->title);
    }

    public function test_optimistic_locking_allows_fresh_update()
    {
        $archive = $this->makeTestArchive([
            'title' => 'Original Archive Title',
        ]);

        $freshTimestamp = $archive->updated_at->toDateTimeString();

        $response = $this->actingAs($this->admin)
            ->put(route('archives.update', $archive->id), [
                'title' => 'Successfully Updated Title',
                'original_updated_at' => $freshTimestamp,
                'submit_action' => 'draft',
                'department_id' => $this->dept->id,
            ]);

        $response->assertSessionMissing('error');
        $this->assertEquals('Successfully Updated Title', $archive->fresh()->title);
    }

    public function test_db_immediate_transaction_executes_successfully()
    {
        $result = db_immediate_transaction(function () {
            return 'immediate_transaction_ok';
        });

        $this->assertEquals('immediate_transaction_ok', $result);
    }

    public function test_wal_checkpoint_endpoint_returns_valid_json()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('api.health.wal_checkpoint'));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue(in_array($json['status'], ['ok', 'ignored']));
    }

    public function test_backup_database_sqlite_snapshot_download()
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            $this->markTestSkipped('Snapshot VACUUM INTO test is specific to SQLite driver.');
        }

        $response = $this->actingAs($this->admin)
            ->get(route('master.settings.backup_database', ['format' => 'sqlite']));

        $response->assertStatus(200);
        $this->assertStringContainsString('sqlite', $response->headers->get('content-type'));
    }
}
