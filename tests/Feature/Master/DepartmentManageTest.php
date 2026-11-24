<?php

namespace Tests\Feature\Master;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Kelola Departemen (Laravel 7 DepartmentController@apiGetManageData / @assignPic / @removePic,
 * MasterArchiveController): PIC assignment, Master Berkas CRUD + batch, scoping to the department.
 */
class DepartmentManageTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    public function test_manage_data_sends_only_the_shown_user_fields(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/departments/{$this->hcs}/manage-data")->assertOk()
            ->assertJsonPath('pic_users.0.name', 'PIC_HCS')
            ->assertJsonPath('master_archives.0.name', 'Kontrak Kerja')
            ->assertJsonPath('sub_departments.0.code', 'PAY');

        $this->assertSame(['id', 'name', 'email', 'phone', 'role'], array_keys($response->json('pic_users.0')));
        $this->assertStringNotContainsString('password', $response->getContent());
        $this->assertNotContains($this->admin->id, array_column($response->json('available_users'), 'id'), 'admins are not offered');
    }

    public function test_assign_existing_user_and_refuse_an_admin(): void
    {
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->hcs}/pic/assign", ['user_id' => $this->gudang->id])
            ->assertOk()->assertJson(['status' => 'success', 'message' => "User {$this->gudang->name} berhasil ditugaskan sebagai PIC Departemen Human Capital."]);
        $this->assertSame(['pic_dept', $this->hcs], [$this->gudang->fresh()->role, $this->gudang->fresh()->department_id]);

        $this->actingAs($this->admin)->postJson("/master/departments/{$this->hcs}/pic/assign", ['user_id' => $this->admin->id])
            ->assertStatus(422)->assertJson(['message' => 'Akun Super Admin tidak dapat ditugaskan sebagai PIC Departemen.']);
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_create_pic_normalizes_the_email_and_keeps_it_unique(): void
    {
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->fat}/pic/assign", ['name' => 'Budi', 'email' => '  Budi@Indraco.COM ', 'password' => 'rahasia1', 'phone' => '0812'])
            ->assertOk()->assertJson(['message' => 'User PIC baru Budi berhasil dibuat dan ditugaskan ke Finance.']);
        $user = User::query()->where('email', 'budi@indraco.com')->firstOrFail();
        $this->assertSame(['pic_dept', $this->fat], [$user->role, $user->department_id]);
        $this->assertTrue(Hash::check('rahasia1', $user->password));

        $this->actingAs($this->admin)->postJson("/master/departments/{$this->fat}/pic/assign", ['name' => 'Budi 2', 'email' => 'BUDI@indraco.com', 'password' => 'rahasia1'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->fat}/pic/assign", ['name' => 'X', 'email' => 'x@indraco.com', 'password' => '123'])
            ->assertJsonValidationErrors('password');
    }

    public function test_remove_pic_only_from_its_own_department(): void
    {
        $this->actingAs($this->admin)->deleteJson("/master/departments/{$this->fat}/pic/{$this->picHcs->id}")
            ->assertStatus(422)->assertJson(['message' => 'User tidak terdaftar pada departemen ini.']);
        $this->actingAs($this->admin)->deleteJson("/master/departments/{$this->hcs}/pic/{$this->picHcs->id}")
            ->assertOk()->assertJson(['message' => 'Penugasan PIC PIC_HCS dari Human Capital berhasil dilepaskan.']);
        $this->assertNull($this->picHcs->fresh()->department_id);
        $this->assertSame(['MASTER_DEPT_PIC_REMOVE', 'MASTER_DEPT'], array_values((array) DB::table('activity_logs')->first(['action', 'module'])));
    }

    public function test_master_berkas_crud_stays_inside_the_department(): void
    {
        $fatSub = DB::table('sub_departments')->insertGetId(['department_id' => $this->fat, 'code' => 'TAX', 'name' => 'Tax']);
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->hcs}/master-archives", ['name' => 'Slip Gaji', 'sub_department_id' => $fatSub])
            ->assertJsonValidationErrors('sub_department_id');

        $this->actingAs($this->admin)->postJson("/master/departments/{$this->hcs}/master-archives", ['name' => 'Slip Gaji', 'code' => 'sg-01', 'sub_department_id' => $this->hcsSub, 'document_type' => 'KONTRAK_KERJA'])
            ->assertOk()->assertJson(['message' => "Master Arsip 'Slip Gaji' berhasil ditambahkan."]);
        $row = DB::table('master_archives')->where('name', 'Slip Gaji')->first();
        $this->assertSame(['SG-01', 3, 1, $this->hcsSub], [$row->code, (int) $row->retention_years, (int) $row->is_active, (int) $row->sub_department_id]);

        $this->actingAs($this->admin)->putJson("/master/departments/{$this->fat}/master-archives/{$row->id}", ['name' => 'Bajak'])->assertNotFound();
        $this->actingAs($this->admin)->deleteJson("/master/departments/{$this->fat}/master-archives/{$row->id}")->assertNotFound();
        $this->actingAs($this->admin)->putJson("/master/departments/{$this->hcs}/master-archives/{$row->id}", ['name' => 'Slip Gaji Bulanan', 'retention_years' => 7])
            ->assertJson(['message' => "Master Arsip 'Slip Gaji Bulanan' berhasil diperbarui."]);
        $this->actingAs($this->admin)->deleteJson("/master/departments/{$this->hcs}/master-archives/{$row->id}")
            ->assertJson(['message' => "Master Arsip 'Slip Gaji Bulanan' berhasil dihapus."]);
        $this->assertNull(DB::table('master_archives')->find($row->id));
    }

    public function test_batch_adds_one_master_berkas_per_line(): void
    {
        $this->actingAs($this->admin)->postJson("/master/departments/{$this->fat}/master-archives/batch", ['raw_text' => "PL, DO\r\n\r\n  Picklist  \nBPBJ"])
            ->assertOk()->assertJson(['total' => 3, 'message' => 'Berhasil menambahkan 3 Master Berkas Arsip baru secara batch.']);
        $this->assertSame(['BPBJ', 'PL, DO', 'Picklist'], DB::table('master_archives')->where('department_id', $this->fat)->orderBy('name')->pluck('name')->all());
        $this->assertSame([5], DB::table('master_archives')->where('department_id', $this->fat)->distinct()->pluck('retention_years')->map(fn ($v) => (int) $v)->all());

        $this->actingAs($this->admin)->postJson("/master/departments/{$this->fat}/master-archives/batch", ['raw_text' => " \n "])
            ->assertStatus(422)->assertJson(['message' => 'Tidak ada nama berkas arsip yang valid untuk disimpan.']);
    }
}
