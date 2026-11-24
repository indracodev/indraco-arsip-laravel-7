<?php

namespace App\Services;

use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Template download of the multi-box import (Laravel 7 MultiBoxTemplateService): sheet
 * DATA_ARSIP (headers + sample boxes from real master data) and REFERENSI_KODE (department,
 * sub-department, rack and Master Berkas codes + filling guide). PIC Departemen: own department
 * only, no rack columns. CSV = DATA_ARSIP only, formula-like cells escaped.
 */
final class ArchiveImportTemplate
{
    private const HEADERS = ['JUDUL_BOX', 'KODE_DEPARTEMEN', 'KODE_SUB_DEPARTEMEN', 'NAMA_DOKUMEN', 'PERIODE_DOK_MULAI', 'PERIODE_DOK_SELESAI', 'KETERANGAN_DOKUMEN', 'MASA_SIMPAN_BULAN', 'KONDISI_FISIK'];

    private const LOCATION_HEADERS = ['LOKASI_RUANG', 'LOKASI_RAK', 'NO_SLOT'];

    private const WIDTHS = [48, 22, 26, 46, 24, 24, 34, 22, 36, 18, 18, 14];

    public function download(string $format, User $user): Response
    {
        $isPic = $user->role === User::ROLE_PIC_DEPT;
        $departments = Department::query()->where('is_active', true)
            ->when($isPic, fn ($q) => $q->whereKey($user->department_id))
            ->with(['subDepartments' => fn ($q) => $q->where('is_active', true)->orderBy('code'), 'masterArchives' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('code')->get();
        $locations = $isPic ? collect() : WarehouseLocation::query()->where('location_type', 'rack')->where('is_active', true)
            ->with(['warehouse', 'slots'])->orderBy('room_sector')->orderBy('rack_code')->take(40)->get();
        $headers = $isPic ? self::HEADERS : [...self::HEADERS, ...self::LOCATION_HEADERS];
        $withMasters = $departments->filter(fn ($d) => $d->masterArchives->isNotEmpty());
        $withSubs = $withMasters->filter(fn ($d) => $d->subDepartments->isNotEmpty());
        $rows = $this->sampleRows($isPic ? $departments : ($withSubs->count() >= 2 ? $withSubs : $withMasters)->take(3), $locations, $isPic);
        $widths = array_map(fn (int $width, int $i): int => max($width, min(120, 4 + max(0, ...array_map(fn (array $row): int => mb_strlen($row[$i] ?? ''), [$headers, ...$rows])))),
            array_slice(self::WIDTHS, 0, count($headers)), array_keys($headers));

        if (strtolower($format) === 'csv') {
            $csv = fopen('php://temp', 'r+');
            foreach ([$headers, ...$rows] as $row) {
                fputcsv($csv, array_map(fn ($v): string => preg_match('/^[=+\-@]/', (string) $v) ? "'{$v}" : (string) $v, $row), ',', '"', '');
            }
            rewind($csv);

            return response("\xEF\xBB\xBF".stream_get_contents($csv), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_arsip_multi_box.csv"',
            ]);
        }

        return $this->xlsx([
            'DATA_ARSIP' => [[$headers, ...$rows], $widths],
            'REFERENSI_KODE' => [$this->reference($user, $departments, $locations, $isPic), [46, 22, 24, 28, 24]],
        ]);
    }

    /**
     * Sample boxes from real master data (Laravel 7): PIC = two boxes of their department (first
     * and second sub-department), admin = one box for up to three departments. Box columns only
     * on the first row of each box.
     *
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, WarehouseLocation>  $locations
     * @return list<list<string>>
     */
    private function sampleRows(Collection $departments, Collection $locations, bool $isPic): array
    {
        $rows = [];
        foreach ($departments->values() as $i => $dept) {
            $subs = $dept->subDepartments->values();
            $all = $dept->masterArchives->values();
            foreach ($isPic ? [0, 1] : [0] as $box) {
                $sub = $subs->get($box) ?? $subs->first();
                $masters = $sub && ($box === 0 || $sub->id !== $subs->first()->id) ? $all->where('sub_department_id', $sub->id)->values() : collect();
                if ($masters->isEmpty()) {
                    $masters = $box === 1 ? $all->slice(2)->values() : ($isPic ? $all->whereNull('sub_department_id')->values() : $masters);
                }
                $masters = $masters->isEmpty() ? $all : $masters;
                $subName = $sub->name ?? $dept->name;
                $end = $isPic && $box === 0 ? '2024-01' : '2024-12';
                $title = ! $isPic ? "Berkas {$dept->name} - {$subName} 2024" : ($box === 0 ? "Berkas Operasional {$subName} 2024" : "Laporan & Formulir {$subName} 2024");
                $years = $masters->get(0)->retention_years ?? 0;
                $months = $years ? $years * 12 : ($isPic ? ($box === 0 ? 60 : 36) : ($dept->retention_years ?: 5) * 12);
                $location = $isPic ? [] : $this->sampleLocation($locations->isNotEmpty() ? $locations->get($i % $locations->count()) : null);
                $rows[] = [$title, $dept->code, $sub->code ?? '', $masters->get(0)->name ?? "Berkas Utama {$subName}", '2024-01', $end,
                    ($masters->get(0)->description ?? '') ?: 'Asli bertandatangan & lengkap', (string) $months, ArchiveBoxBuilder::DEFAULT_CONDITION, ...$location];
                $rows[] = ['', '', '', $masters->get(1)->name ?? "Dokumen Pendukung {$subName}", '2024-01', $end,
                    ($masters->get(1)->description ?? '') ?: 'Dokumen pendukung & verifikasi', '', '', ...($isPic ? [] : ['', '', ''])];
            }
        }

        return $rows;
    }

    /** @return list<string> [room, rack, slot] */
    private function sampleLocation(?WarehouseLocation $location): array
    {
        $slot = $location?->slots->firstWhere('status', 'empty') ?? $location?->slots->first();

        return [$location->room_sector ?? 'R5', $location ? $this->rackIdentifier($location) : 'AS', (string) ($slot->slot_number ?? '1')];
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, WarehouseLocation>  $locations
     * @return list<list<string>>
     */
    private function reference(User $user, Collection $departments, Collection $locations, bool $isPic): array
    {
        $own = $isPic ? $departments->first() : null;
        $rows = $own
            ? [["BUKU PANDUAN REFERENSI KODE RESMI - DEPARTEMEN {$own->name} ({$own->code})"], ["Akun Anda memiliki hak akses PIC Departemen {$own->name}. Sesuai batasan otoritas akun, tabel di bawah HANYA menampilkan kode resmi departemen Anda agar terhindar dari salah pilih kode departemen lain."]]
            : [['BUKU PANDUAN REFERENSI KODE RESMI & MASTER DATA DMS INDRACO'], ['Gunakan kode-kode resmi di bawah ini saat mengisi sheet DATA_ARSIP agar proses impor berjalan lancar 100%.']];
        $rows = [...$rows, [], ['[1. KODE DEPARTEMEN RESMI ANDA]'], ['KODE_DEPARTEMEN', 'NAMA_DEPARTEMEN', 'DEFAULT_RETENSI_TAHUN']];
        foreach ($departments as $d) {
            $rows[] = [$d->code, $d->name, (string) ($d->retention_years ?: 5)];
        }
        $rows = [...$rows, [], ['[2. DAFTAR KODE SUB-DEPARTEMEN RESMI]'], ['KODE_SUB_DEPARTEMEN', 'NAMA_SUB_DEPARTEMEN', 'DEPARTEMEN_INDUK']];
        $subs = $departments->flatMap(fn ($d) => $d->subDepartments->map(fn ($s) => [$s->code, $s->name, $d->code]))->sortBy(0)->values()->all();
        $rows = [...$rows, ...($subs ?: [['-(TIDAK ADA)-', 'Departemen ini tidak memiliki sub-departemen. Kolom KODE_SUB_DEPARTEMEN boleh dikosongkan.', $own->code ?? '-']]), []];
        if (! $isPic) {
            $rows = [...$rows, ['[3. DAFTAR RUANG & RAK GUDANG AKTIF (CONTOH FORMAT LOKASI)]'], ['LOKASI_RUANG', 'LOKASI_RAK', 'CONTOH_NO_SLOT', 'KETERANGAN_LOKASI']];
            foreach ($locations as $loc) {
                $rack = $this->rackIdentifier($loc);
                $rows[] = [$loc->room_sector ?? 'GUDANG', $rack, '1 s/d '.$loc->getAttribute('box_capacity')." (contoh: 1, 33, 55 atau kode {$rack}55)",
                    ($loc->warehouse->name ?? "Gudang {$loc->room_sector}")." - Sektor {$loc->room_sector} - Rak {$loc->rack_code}"];
            }
            $rows[] = [];
        }
        $rows = [...$rows, ['['.($isPic ? 3 : 4).'. DAFTAR MASTER NAMA BERKAS / DOKUMEN RESMI]'], ['NAMA_DOKUMEN_RESMI', 'KODE_DEPARTEMEN', 'KODE_SUB_DEPARTEMEN', 'KATEGORI_DOKUMEN', 'STANDAR_RETENSI_BULAN']];
        $masters = MasterArchive::query()->where('is_active', true)->when($isPic, fn ($q) => $q->where('department_id', $user->department_id))
            ->with(['department', 'subDepartment'])->orderBy('name')->take($isPic ? 100 : 150)->get();
        foreach ($masters as $m) {
            $rows[] = [$m->name, $m->department->code ?? '', $m->subDepartment->code ?? '-', $m->document_type ?? 'Umum', (string) ($m->retention_years ? $m->retention_years * 12 : 60)];
        }

        return [...$rows, [], ...$this->guide($isPic)];
    }

    /** Laravel 7 WarehouseLocation::rack_identifier: 'AS' from 'R5-AS', 'B' from 'RAK-R1-B'. */
    private function rackIdentifier(WarehouseLocation $location): string
    {
        return (string) preg_replace('/^(?:RAK-)?(?:R\d+-)?/i', '', $location->rack_code) ?: $location->rack_code;
    }

    /** @return list<list<string>> */
    private function guide(bool $isPic): array
    {
        return [
            [$isPic ? '[4. PETUNJUK PENGISIAN PENGAJUAN ARSIP]' : '[5. PETUNJUK PENGISIAN AGAR MINIM ERROR]'],
            ['1. Identitas Box (JUDUL_BOX, DEPARTEMEN) cukup diisi 1 kali pada baris pertama box. Pengisian JUDUL_BOX baru pada baris berikutnya otomatis memisahkan berkas ke dalam box baru.'],
            ['2. Nomor urut dokumen otomatis berurutan dari atas ke bawah untuk setiap box (tanpa perlu kolom nomor butir).'],
            ['3. Kolom MASA_SIMPAN_BULAN murni hitungan bulan (contoh: 60 untuk 60 bulan/5 tahun, 12 untuk 12 bulan/1 tahun).'],
            ['4. Format Periode Mulai & Selesai disarankan YYYY-MM (contoh: 2024-01) atau format bulan natural (contoh: Jan 25 s/d Des 25).'],
            [$isPic
                ? '5. Penentuan lokasi rak dan slot gudang fisik tidak perlu diisi oleh departemen. Alokasi rak akan diverifikasi dan diplot langsung oleh Tim Gudang saat proses persetujuan (approval).'
                : '5. Kolom Lokasi (RUANG, RAK, SLOT) bersifat opsional. Contoh: LOKASI_RUANG diisi R5, LOKASI_RAK diisi AS, NO_SLOT diisi 33 atau 55 (menghasilkan lokasi Gudang R5 - AS33 / AS55) atau dapat langsung mengisi kode slot seperti AS55 pada kolom NO_SLOT.'],
        ];
    }

    /**
     * Minimal XLSX (inline strings only: no formula is ever evaluated).
     *
     * @param  array<string, array{0: list<list<string>>, 1: list<int>}>  $sheets  name => [rows, column widths]
     */
    private function xlsx(array $sheets): BinaryFileResponse
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'dms_xlsx_');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $ns = 'http://schemas.openxmlformats.org';
        $overrides = $workbookSheets = $rels = '';
        $n = 0;
        foreach ($sheets as $name => [$rows, $widths]) {
            $n++;
            $overrides .= "<Override PartName=\"/xl/worksheets/sheet{$n}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $workbookSheets .= "<sheet name=\"{$name}\" sheetId=\"{$n}\" r:id=\"rId{$n}\"/>";
            $rels .= "<Relationship Id=\"rId{$n}\" Type=\"{$ns}/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$n}.xml\"/>";
            $zip->addFromString("xl/worksheets/sheet{$n}.xml", $this->sheetXml($rows, $widths));
        }
        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $zip->addFromString('[Content_Types].xml', "{$head}<Types xmlns=\"{$ns}/package/2006/content-types\"><Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/><Default Extension=\"xml\" ContentType=\"application/xml\"/><Override PartName=\"/xl/workbook.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml\"/>{$overrides}</Types>");
        $zip->addFromString('_rels/.rels', "{$head}<Relationships xmlns=\"{$ns}/package/2006/relationships\"><Relationship Id=\"rId1\" Type=\"{$ns}/officeDocument/2006/relationships/officeDocument\" Target=\"xl/workbook.xml\"/></Relationships>");
        $zip->addFromString('xl/_rels/workbook.xml.rels', "{$head}<Relationships xmlns=\"{$ns}/package/2006/relationships\">{$rels}</Relationships>");
        $zip->addFromString('xl/workbook.xml', "{$head}<workbook xmlns=\"{$ns}/spreadsheetml/2006/main\" xmlns:r=\"{$ns}/officeDocument/2006/relationships\"><sheets>{$workbookSheets}</sheets></workbook>");
        $zip->close();

        return response()->download($path, 'template_arsip_multi_box.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @param  list<list<string>>  $rows
     * @param  list<int>  $widths
     */
    private function sheetXml(array $rows, array $widths): string
    {
        $cols = '';
        foreach ($widths as $i => $width) {
            $cols .= '<col min="'.($i + 1).'" max="'.($i + 1)."\" width=\"{$width}\" customWidth=\"1\"/>";
        }
        $data = '';
        foreach ($rows as $r => $row) {
            $data .= '<row r="'.($r + 1).'">';
            foreach ($row as $c => $value) {
                $ref = chr(65 + $c).($r + 1);
                $data .= "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>".htmlspecialchars((string) $value, ENT_XML1, 'UTF-8').'</t></is></c>';
            }
            $data .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            ."<cols>{$cols}</cols><sheetData>{$data}</sheetData></worksheet>";
    }
}
