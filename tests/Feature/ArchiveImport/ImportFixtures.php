<?php

namespace Tests\Feature\ArchiveImport;

use Illuminate\Http\UploadedFile;
use Tests\Feature\Archive\ArchiveFixtures;
use ZipArchive;

/**
 * Tiny generated spreadsheets for the import tests (on top of the ARSIP fixtures: HCS with sub
 * PAY + Master Berkas "Kontrak Kerja", FAT, PIC per department, admin, gudang; time 2026-11-24).
 * Laravel runs setUpArchiveFixtures() itself (setUp<TraitName> convention): do not call it again.
 */
trait ImportFixtures
{
    use ArchiveFixtures;

    /** PIC template header (Laravel 7 MultiBoxTemplateService). */
    protected const HEADER = ['JUDUL_BOX', 'KODE_DEPARTEMEN', 'KODE_SUB_DEPARTEMEN', 'NAMA_DOKUMEN', 'PERIODE_DOK_MULAI', 'PERIODE_DOK_SELESAI', 'KETERANGAN_DOKUMEN', 'MASA_SIMPAN_BULAN', 'KONDISI_FISIK'];

    /**
     * Laravel 7 style sample: two boxes, box columns only on the first row, a typo matched to
     * Master Berkas, several period formats.
     *
     * @return list<list<string>>
     */
    protected function sampleRows(): array
    {
        return [
            self::HEADER,
            ['Box Kontrak 2026', 'HCS', '', 'Kontrak Kerja', '2026-01', '2026-03', 'No 1-50', '24', 'Baik / Box Karton Standar TB 30g'],
            ['', '', '', 'kontrak krja', 'Jan 26', 'Apr 26', 'No 51-60', '', ''],
            [],
            ['Box Payroll', 'HCS', 'PAY', 'Slip Gaji', '03/2026', '2026/05', 'Payroll Q1', '12', ''],
        ];
    }

    /** @param list<list<string>> $rows */
    protected function xlsxFile(array $rows, string $name = 'impor.xlsx'): UploadedFile
    {
        $path = $this->tempPath();
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $data = '';
        foreach ($rows as $r => $row) {
            $data .= '<row r="'.($r + 1).'">';
            foreach ($row as $c => $value) {
                $data .= '<c r="'.chr(65 + $c).($r + 1).'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            }
            $data .= '</row>';
        }
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"/>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$data.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /** @param list<list<string>> $rows */
    protected function csvFile(array $rows, string $name = 'impor.csv'): UploadedFile
    {
        $path = $this->tempPath();
        file_put_contents($path, "\xEF\xBB\xBF".implode("\n", array_map(fn (array $row): string => implode(';', $row), $rows))."\n");

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    protected function rawFile(string $content, string $name): UploadedFile
    {
        $path = $this->tempPath();
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'application/octet-stream', null, true);
    }

    private function tempPath(): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'dmsimp');
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
