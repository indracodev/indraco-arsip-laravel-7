<?php

namespace App\Services;

use ZipArchive;

/**
 * Pembaca XLSX/CSV ringan (ZipArchive + SimpleXML, tanpa PhpSpreadsheet).
 * Hasil: array baris padat (dense) dengan key = nomor baris Excel (1-based).
 */
class SpreadsheetReader
{
    /**
     * Baca satu sheet XLSX. $sheetName null = sheet pertama (sheet1.xml / worksheet pertama di zip).
     * Return [] jika file tidak terbaca, null jika $sheetName tidak ditemukan.
     */
    public static function xlsx(string $filePath, ?string $sheetName = null): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) return [];

        $sharedStrings = [];
        if ($ssContent = $zip->getFromName('xl/sharedStrings.xml')) {
            $ssXml = @simplexml_load_string($ssContent);
            if ($ssXml && isset($ssXml->si)) {
                foreach ($ssXml->si as $si) {
                    $val = (string)($si->t ?? '');
                    if (!$val && isset($si->r)) {
                        foreach ($si->r as $r) $val .= (string)$r->t;
                    }
                    $sharedStrings[] = $val;
                }
            }
        }

        if ($sheetName !== null) {
            $sheetPath = self::sheetPathByName($zip, $sheetName);
            if ($sheetPath === null) {
                $zip->close();
                return null;
            }
            $sheetContent = $zip->getFromName($sheetPath);
        } else {
            $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!$sheetContent) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (strpos($entry, 'xl/worksheets/') === 0 && substr($entry, -4) === '.xml') {
                        $sheetContent = $zip->getFromName($entry);
                        break;
                    }
                }
            }
        }

        $rows = [];
        $rowNum = 0;
        if ($sheetContent && ($sheetXml = @simplexml_load_string($sheetContent)) && isset($sheetXml->sheetData->row)) {
            foreach ($sheetXml->sheetData->row as $row) {
                $rowAttr = $row->attributes();
                $rowNum = isset($rowAttr['r']) ? (int)$rowAttr['r'] : $rowNum + 1;
                $rowCells = [];
                foreach ($row->c as $c) {
                    $attr = $c->attributes();
                    $colIdx = self::colLetterToIndex(isset($attr['r']) ? (string)$attr['r'] : '');
                    $t = isset($attr['t']) ? (string)$attr['t'] : '';
                    $val = ($t === 's') ? ($sharedStrings[(int)$c->v] ?? '') : (($t === 'inlineStr') ? (string)$c->is->t : (string)$c->v);
                    $rowCells[$colIdx] = trim($val);
                }
                if (!empty($rowCells)) {
                    $maxCol = max(array_keys($rowCells));
                    $denseRow = [];
                    for ($c = 0; $c <= $maxCol; $c++) $denseRow[] = $rowCells[$c] ?? '';
                    $rows[$rowNum] = $denseRow;
                }
            }
        }
        $zip->close();
        return $rows;
    }

    /**
     * Baca CSV dengan auto-deteksi delimiter (, ; tab) dan penghapusan BOM UTF-8.
     */
    public static function csv(string $filePath): array
    {
        if (!($handle = fopen($filePath, 'r'))) return [];
        $firstLine = fgets($handle); rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ((substr_count($firstLine, "\t") > substr_count($firstLine, ',')) ? "\t" : ',');
        $rows = []; $isFirst = true; $rowNum = 0;
        while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
            if ($isFirst && !empty($data)) { $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]); $isFirst = false; }
            $rows[++$rowNum] = array_map('trim', $data);
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Cari path XML worksheet berdasarkan nama sheet (workbook.xml + workbook.xml.rels).
     */
    protected static function sheetPathByName(ZipArchive $zip, string $sheetName): ?string
    {
        $wb = @simplexml_load_string((string)$zip->getFromName('xl/workbook.xml'));
        $rels = @simplexml_load_string((string)$zip->getFromName('xl/_rels/workbook.xml.rels'));
        if (!$wb || !$rels || !isset($wb->sheets->sheet)) return null;

        $relId = null;
        foreach ($wb->sheets->sheet as $sheet) {
            if (strcasecmp(trim((string)$sheet['name']), trim($sheetName)) === 0) {
                $relId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                break;
            }
        }
        if (!$relId) return null;

        foreach ($rels->Relationship as $rel) {
            if ((string)$rel['Id'] === $relId) {
                $target = (string)$rel['Target'];
                return $target[0] === '/' ? ltrim($target, '/') : 'xl/' . $target;
            }
        }
        return null;
    }

    protected static function colLetterToIndex(string $cellRef): int
    {
        if (!preg_match('/^([A-Z]+)/i', $cellRef, $m)) return 0;
        $num = 0;
        foreach (str_split(strtoupper($m[1])) as $ch) $num = $num * 26 + (ord($ch) - 64);
        return $num - 1;
    }
}
