<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Archive;
use App\Models\Department;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LabelPrintStatusRestrictionTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;
    protected $dept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->dept = Department::firstOrCreate(
            ['code' => 'TEST_DEPT'],
            ['name' => 'Department Testing', 'is_active' => true]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_label_test@indraco.com'],
            [
                'name' => 'Admin Label Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'department_id' => $this->dept->id,
            ]
        );
    }

    public function test_can_print_label_helper()
    {
        $arcApproved = new Archive(['status' => 'approved_booked']);
        $arcWarehouse = new Archive(['status' => 'in_warehouse']);
        $arcExtension = new Archive(['status' => 'pending_extension']);
        $arcDraft = new Archive(['status' => 'draft']);
        $arcQueue = new Archive(['status' => 'pending_verification']);
        $arcBorrowed = new Archive(['status' => 'borrowed']);
        $arcTaken = new Archive(['status' => 'taken']);
        $arcDestroyed = new Archive(['status' => 'destroyed']);

        $this->assertTrue($arcApproved->canPrintLabel());
        $this->assertTrue($arcWarehouse->canPrintLabel());
        $this->assertTrue($arcExtension->canPrintLabel());

        $this->assertFalse($arcDraft->canPrintLabel());
        $this->assertFalse($arcQueue->canPrintLabel());
        $this->assertFalse($arcBorrowed->canPrintLabel());
        $this->assertFalse($arcTaken->canPrintLabel());
        $this->assertFalse($arcDestroyed->canPrintLabel());
    }

    public function test_print_sticker_allowed_for_approved_warehouse_and_extension()
    {
        $this->actingAs($this->adminUser);

        $arcApproved = Archive::create([
            'box_number' => 'BOX-TEST-APP-' . time(),
            'title' => 'Arsip Approved Booked Test',
            'department_id' => $this->dept->id,
            'status' => 'approved_booked',
            'created_by_user_id' => $this->adminUser->id,
            'period_start_date' => '2020-01-01',
            'period_end_date' => '2024-12-31',
            'retention_years' => 5,
            'content_description' => 'Isi arsip deskripsi test',
        ]);

        $responseApp = $this->get(route('archives.print_sticker', $arcApproved));
        $responseApp->assertStatus(200);

        $arcWarehouse = Archive::create([
            'box_number' => 'BOX-TEST-WH-' . time(),
            'title' => 'Arsip In Warehouse Test',
            'department_id' => $this->dept->id,
            'status' => 'in_warehouse',
            'created_by_user_id' => $this->adminUser->id,
            'period_start_date' => '2020-01-01',
            'period_end_date' => '2024-12-31',
            'retention_years' => 5,
            'content_description' => 'Isi arsip deskripsi test',
        ]);

        $responseWh = $this->get(route('archives.print_sticker', $arcWarehouse));
        $responseWh->assertStatus(200);

        $arcExt = Archive::create([
            'box_number' => 'BOX-TEST-EXT-' . time(),
            'title' => 'Arsip Extension Test',
            'department_id' => $this->dept->id,
            'status' => 'pending_extension',
            'created_by_user_id' => $this->adminUser->id,
            'period_start_date' => '2020-01-01',
            'period_end_date' => '2024-12-31',
            'retention_years' => 5,
            'content_description' => 'Isi arsip deskripsi test',
        ]);

        $responseExt = $this->get(route('archives.print_sticker', $arcExt));
        $responseExt->assertStatus(200);
    }

    public function test_print_sticker_blocked_for_draft_queue_borrowed_and_destroyed()
    {
        $this->actingAs($this->adminUser);

        $disallowedStatuses = ['draft', 'pending_verification', 'borrowed', 'taken', 'destroyed'];

        foreach ($disallowedStatuses as $status) {
            $arc = Archive::create([
                'box_number' => 'BOX-TEST-' . strtoupper($status) . '-' . time() . '-' . rand(100, 999),
                'title' => 'Arsip Test ' . $status,
                'department_id' => $this->dept->id,
                'status' => $status,
                'created_by_user_id' => $this->adminUser->id,
                'period_start_date' => '2020-01-01',
                'period_end_date' => '2024-12-31',
                'retention_years' => 5,
                'content_description' => 'Isi arsip deskripsi test',
            ]);

            // Request with json
            $responseJson = $this->getJson(route('archives.print_sticker', $arc));
            $responseJson->assertStatus(403);

            // Request with embed
            $responseEmbed = $this->get(route('archives.print_sticker', ['archive' => $arc->id, 'embed' => 1]));
            $responseEmbed->assertStatus(403);
        }
    }
}
