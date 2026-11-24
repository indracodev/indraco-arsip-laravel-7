<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataImportTest extends TestCase
{
    use DatabaseTransactions;

    protected function admin(): User
    {
        return User::create([
            'name' => 'Admin Import',
            'email' => 'admin-import-' . uniqid() . '@indraco.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    protected function upload(string $path, string $name): UploadedFile
    {
        return new UploadedFile($path, $name, null, null, true);
    }

    protected function csv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'mdi_') . '.csv';
        file_put_contents($path, "No,Kode Departemen *,Departemen *,Kode Sub Departemen,Sub Departemen,Nama Dokumen *,Retensi (Tahun)\n" . $content);
        return $this->upload($path, 'data.csv');
    }

    protected function counts(): array
    {
        return [Department::count(), SubDepartment::count(), MasterArchive::count()];
    }

    public function test_template_import_is_idempotent()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $admin = $this->admin();
        $template = base_path('template-import-master/template_import_data_master.xlsx');

        [$d0, $s0, $m0] = $this->counts();
        $res = $this->actingAs($admin)->postJson(route('master.departments.import.commit'), [
            'file' => $this->upload($template, 'template_import_data_master.xlsx'),
        ])->assertOk()->json();

        $this->assertTrue($res['success']);
        $this->assertSame(15, $res['summary']['total_baris']);
        [$d1, $s1, $m1] = $this->counts();
        $this->assertSame($res['summary']['departemen_baru'], $d1 - $d0);
        $this->assertSame($res['summary']['sub_departemen_baru'], $s1 - $s0);
        $this->assertSame($res['summary']['dokumen_baru'], $m1 - $m0);

        $res2 = $this->actingAs($admin)->postJson(route('master.departments.import.commit'), [
            'file' => $this->upload($template, 'template_import_data_master.xlsx'),
        ])->assertOk()->json();

        $this->assertSame(0, $res2['summary']['departemen_baru'] + $res2['summary']['sub_departemen_baru'] + $res2['summary']['dokumen_baru']);
        $this->assertSame(13, $res2['summary']['dokumen_sudah_ada']);
        $this->assertSame([$d1, $s1, $m1], $this->counts());
    }

    public function test_new_data_and_in_file_duplicates()
    {
        $res = app(\App\Services\MasterDataImportService::class)->process($this->csv(
            "1,zq9,Zeta Test,s1,Sub Satu,Dok A,7\n" .
            "2,ZQ9,Zeta  Test,S1,Sub Satu,dok  a,\n" .   // duplikat di dalam file
            "3,ZQ9,Zeta Test,,,Dok A,\n" .               // langsung ke departemen: beda dengan sub S1
            "4,ZQ9,Zeta Test,S2,Sub Dua,,\n"             // sub tanpa dokumen
        ), true, $this->admin());

        $this->assertTrue($res['success'], implode("\n", $res['errors']));
        $this->assertSame(['departemen_baru' => 1, 'sub_departemen_baru' => 2, 'dokumen_baru' => 2, 'dokumen_sudah_ada' => 1, 'total_baris' => 4], $res['summary']);

        $dept = Department::where('code', 'ZQ9')->firstOrFail();
        $this->assertSame(5, $dept->retention_years);
        $this->assertSame(7, MasterArchive::where('department_id', $dept->id)->whereNotNull('sub_department_id')->value('retention_years'));
        $this->assertSame(5, MasterArchive::where('department_id', $dept->id)->whereNull('sub_department_id')->value('retention_years'));
    }

    public function test_error_file_is_refused()
    {
        $before = $this->counts();
        $res = app(\App\Services\MasterDataImportService::class)->process($this->csv(
            "1,ZQ8,,,,Dok A,5\n" .
            "2,ZQ8,Zeta,,,Dok B,abc\n" .
            "3,ZQ8,Zeta Lain,,,Dok C,5\n" .
            "4,ZQ8,Zeta,S1,,Dok D,\n" .
            "5,ZQ8,Zeta,,,Dok E,\n"
        ), true, $this->admin());

        $this->assertFalse($res['success']);
        $this->assertSame([
            'Baris 2: Departemen wajib diisi.',
            'Baris 3: Retensi (Tahun) harus angka bulat 1-100 (isi: abc).',
            "Baris 4: Kode Departemen ZQ8 memakai nama berbeda dengan baris 3 ('Zeta').",
            'Baris 5: Sub Departemen wajib diisi jika Kode Sub Departemen diisi.',
        ], $res['errors']);
        $this->assertSame($before, $this->counts());
    }
}
