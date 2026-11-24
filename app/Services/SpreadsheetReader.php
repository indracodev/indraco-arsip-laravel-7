<?php

namespace App\Services;

use SimpleXMLElement;
use ZipArchive;

/**
 * Light XLSX / CSV reader (ZipArchive + SimpleXML, no PhpSpreadsheet), ported from Laravel 7.
 * Result: dense rows keyed by the Excel row number (1-based).
 */
class SpreadsheetReader
{
    /** Uncompressed size limit of one XML part (an upload is ≤ 5 MB; guards against zip bombs). */
    private const MAX_PART_BYTES = 30 * 1024 * 1024;

    /**
     * One XLSX sheet. $sheetName null = the first sheet.
     * Returns [] when the file cannot be read, null when $sheetName does not exist.
     *
     * @return array<int, list<string>>|null
     */
    public static function xlsx(string $filePath, ?string $sheetName = null): ?array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = $sheetName !== null ? self::sheetPathByName($zip, $sheetName) : self::firstSheetPath($zip);
        if ($sheetPath === null) {
            $zip->close();

            return $sheetName !== null ? null : [];
        }

        $sheetXml = self::xml($zip, $sheetPath);
        $zip->close();

        $rows = [];
        $rowNum = 0;
        if ($sheetXml === null || ! isset($sheetXml->sheetData->row)) {
            return $rows;
        }
        foreach ($sheetXml->sheetData->row as $row) {
            $rowNum = isset($row['r']) ? (int) $row['r'] : $rowNum + 1;
            $cells = [];
            foreach ($row->c as $c) {
                $type = (string) ($c['t'] ?? '');
                $value = match ($type) {
                    's' => $sharedStrings[(int) $c->v] ?? '',
                    'inlineStr' => self::richText($c->is),
                    default => (string) $c->v,
                };
                $cells[self::colLetterToIndex((string) ($c['r'] ?? ''))] = trim($value);
            }
            if ($cells !== []) {
                $dense = [];
                for ($i = 0, $max = max(array_keys($cells)); $i <= $max; $i++) {
                    $dense[] = $cells[$i] ?? '';
                }
                $rows[$rowNum] = $dense;
            }
        }

        return $rows;
    }

    /**
     * CSV with delimiter detection (, ; tab) and UTF-8 BOM removal.
     *
     * @return array<int, list<string>>
     */
    public static function csv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }
        $firstLine = (string) fgets($handle);
        rewind($handle);
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        $delimiter = $counts[';'] > $counts[','] ? ';' : ($counts["\t"] > $counts[','] ? "\t" : ',');

        $rows = [];
        $rowNum = 0;
        while (($data = fgetcsv($handle, 10000, $delimiter, '"', '')) !== false) {
            if ($rowNum === 0) {
                $data[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
            }
            $rows[++$rowNum] = array_map(fn ($value): string => trim((string) $value), $data);
        }
        fclose($handle);

        return $rows;
    }

    /** @return list<string> */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = self::xml($zip, 'xl/sharedStrings.xml');
        $strings = [];
        if ($xml === null || ! isset($xml->si)) {
            return $strings;
        }
        foreach ($xml->si as $si) {
            $strings[] = self::richText($si);
        }

        return $strings;
    }

    /** Text of a shared / inline string: plain <t> or rich-text runs <r><t>. */
    private static function richText(?SimpleXMLElement $node): string
    {
        if ($node === null) {
            return '';
        }
        $text = (string) ($node->t ?? '');
        if ($text === '' && isset($node->r)) {
            foreach ($node->r as $run) {
                $text .= (string) $run->t;
            }
        }

        return $text;
    }

    private static function xml(ZipArchive $zip, string $path): ?SimpleXMLElement
    {
        $stat = $zip->statName($path);
        if ($stat === false || $stat['size'] > self::MAX_PART_BYTES) {
            return null;
        }
        $content = $zip->getFromName($path);
        $xml = $content === false ? false : @simplexml_load_string($content);

        return $xml === false ? null : $xml;
    }

    private static function firstSheetPath(ZipArchive $zip): ?string
    {
        if ($zip->statName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (str_starts_with($entry, 'xl/worksheets/') && str_ends_with($entry, '.xml')) {
                return $entry;
            }
        }

        return null;
    }

    /** Worksheet XML path for a sheet name (workbook.xml + workbook.xml.rels). */
    private static function sheetPathByName(ZipArchive $zip, string $sheetName): ?string
    {
        $workbook = self::xml($zip, 'xl/workbook.xml');
        $rels = self::xml($zip, 'xl/_rels/workbook.xml.rels');
        if ($workbook === null || $rels === null || ! isset($workbook->sheets->sheet)) {
            return null;
        }

        $relId = null;
        foreach ($workbook->sheets->sheet as $sheet) {
            if (strcasecmp(trim((string) $sheet['name']), trim($sheetName)) === 0) {
                $relId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                break;
            }
        }
        if (! $relId) {
            return null;
        }

        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $relId) {
                $target = (string) $rel['Target'];

                return $target[0] === '/' ? ltrim($target, '/') : 'xl/'.$target;
            }
        }

        return null;
    }

    private static function colLetterToIndex(string $cellRef): int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellRef, $match)) {
            return 0;
        }
        $number = 0;
        foreach (str_split(strtoupper($match[1])) as $char) {
            $number = $number * 26 + (ord($char) - 64);
        }

        return $number - 1;
    }
}
