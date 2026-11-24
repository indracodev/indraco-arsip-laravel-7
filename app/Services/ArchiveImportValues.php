<?php

namespace App\Services;

use App\Models\MasterArchive;
use Illuminate\Support\Collection;

/**
 * Cell readers of the multi-box Excel import, same tolerance as Laravel 7
 * (MultiBoxExcelService::detectHeaderAndCols / matchMasterArchive + NormalizesExcelArchiveData):
 * header aliases, month periods ("2024-01", "01/2024", "Jan 25", Excel serial dates), retention
 * ("60", "5 Tahun", "12 Bulan") and fuzzy matching of document names to Master Berkas.
 */
final class ArchiveImportValues
{
    /** @var array<string, list<string>> column key => accepted header texts (Laravel 7 list) */
    private const ALIASES = [
        'box' => ['NO_BOX_SEMENTARA', 'NO_BOX', 'BOX', 'KODE_BOX', 'NOMOR_BOX', 'NO BOX'],
        'title' => ['JUDUL_BOX', 'NAMA_BOX', 'DESKRIPSI_BOX', 'TITLE', 'JUDUL'],
        'dept' => ['KODE_DEPARTEMEN', 'DEPARTEMEN', 'DEPT', 'DEPARTMENT', 'DEPARTEMENT'],
        'sub_dept' => ['KODE_SUB_DEPARTEMEN', 'SUB_DEPARTEMEN', 'SUB_DEPT', 'SUB_DEPT.', 'SUB DEPT', 'SUB DEPT.'],
        'doc_name' => ['NAMA_DOKUMEN', 'NAMA_BERKAS', 'BERKAS', 'LIST_DOKUMEN', 'LIST DOKUMEN', 'DOKUMEN', 'JUDUL_DOKUMEN', 'NAMA DOKUMEN', 'ITEM_DOKUMEN'],
        'item_num' => ['NO_BUTIR', 'NO_URUT', 'NO.', 'NO', 'NOMOR', 'ITEM_NO', 'NO_BERKAS'],
        'period_combined' => ['PERIODE_DOKUMEN', 'PERIODE DOKUMEN', 'PERIODE', 'PERIODE_BOX', 'PERIODE DOK'],
        'period_start' => ['PERIODE_DOK_MULAI', 'PERIODE_MULAI', 'MULAI', 'START_DATE'],
        'period_end' => ['PERIODE_DOK_SELESAI', 'PERIODE_SELESAI', 'SELESAI', 'END_DATE'],
        'retention' => ['MASA_SIMPAN_BULAN', 'MASA_SIMPAN', 'RETENSI_BULAN', 'MASA SIMPAN BULAN', 'MASA_SIMPAN_TAHUN', 'MASA SIMPAN', 'RETENSI', 'RETENTION'],
        'condition' => ['KONDISI_FISIK', 'KONDISI', 'KONDISI FISIK', 'PHYSICAL_CONDITION'],
        'room' => ['RUANG', 'GUDANG', 'LOKASI_RUANG', 'ROOM'],
        'rack' => ['RAK', 'LOKASI_RAK', 'RACK'],
        'slot' => ['NO_SLOT', 'SLOT', 'NOMOR_SLOT'],
        'notes' => ['KETERANGAN_DOKUMEN', 'KETERANGAN_BERKAS', 'KETERANGAN_BUTIR', 'KETERANGAN', 'CATATAN', 'NOTES', 'KET'],
    ];

    private const MONTHS = [
        'jan' => '01', 'januari' => '01', 'january' => '01', 'feb' => '02', 'februari' => '02', 'february' => '02',
        'mar' => '03', 'maret' => '03', 'march' => '03', 'apr' => '04', 'april' => '04', 'mei' => '05', 'may' => '05',
        'jun' => '06', 'juni' => '06', 'june' => '06', 'jul' => '07', 'juli' => '07', 'july' => '07',
        'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08', 'sep' => '09', 'sept' => '09', 'september' => '09',
        'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10', 'nov' => '11', 'november' => '11',
        'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
    ];

    /**
     * Header row within the first 5 rows (the first one with a document or box column), plus an
     * optional sub-header row (Ruang / Rak / No under "Lokasi").
     *
     * @param  list<list<string>>  $rows
     * @return array{0: array<string, int>, 1: int} [column key => index, first data row index]
     */
    public function header(array $rows): array
    {
        $map = [];
        foreach (array_slice($rows, 0, 5) as $i => $row) {
            foreach ($row as $col => $cell) {
                $clean = self::headerText($cell);
                foreach ($clean === '' ? [] : self::ALIASES as $key => $aliases) {
                    if (in_array($clean, array_map(self::headerText(...), $aliases), true)) {
                        $map[$key] = $col;
                        break;
                    }
                }
            }
            if (isset($map['doc_name']) || isset($map['box'])) {
                return [$map, $i + 1 + $this->subHeader($rows[$i + 1] ?? [], $map)];
            }
        }

        return [$map, 1];
    }

    /**
     * "jan 25 - Des 25", "2024-01 s/d 2024-06" or a single month.
     *
     * @return array{0: string|null, 1: string|null}
     */
    public function periodRange(string $raw): array
    {
        $parts = preg_split('/\s*(?:-|s\/d|sd|to|\/)\s*/i', trim($raw)) ?: [];
        if (count($parts) >= 2) {
            $start = $this->yearMonth($parts[0]);
            $end = $this->yearMonth($parts[count($parts) - 1]);

            return [$start ?? $end, $end ?? $start];
        }
        $single = $this->yearMonth($raw);

        return [$single, $single];
    }

    /** A month as YYYY-MM, or null when not recognized. */
    public function yearMonth(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        return match (true) {
            $raw === '' => null,
            (bool) preg_match('/^(\d{4})[-\/](0[1-9]|1[0-2])$/', $raw, $m) => "{$m[1]}-{$m[2]}",
            (bool) preg_match('/^(0[1-9]|1[0-2])[\/-](\d{4})$/', $raw, $m) => "{$m[2]}-{$m[1]}",
            (bool) preg_match('/^(\d{4})$/', $raw, $m) => "{$m[1]}-01",
            is_numeric($raw) && (int) $raw > 30000 && (int) $raw < 60000 => gmdate('Y-m', ((int) $raw - 25569) * 86400),
            (bool) preg_match('/([a-z]+)\s*(\d{2,4})/i', $raw, $m) && isset(self::MONTHS[strtolower($m[1])]) => (strlen($m[2]) === 2 ? '20'.$m[2] : $m[2]).'-'.self::MONTHS[strtolower($m[1])],
            default => null,
        };
    }

    /** Retention in years: "5 Tahun" -> 5, "24 Bulan" -> 2, plain months "60" -> 5 (below 12 -> 1); empty -> $default. */
    public function retentionYears(?string $raw, int $default = 5): int
    {
        $raw = strtolower(trim((string) $raw));

        return match (true) {
            $raw === '' => $default,
            (bool) preg_match('/(\d+)\s*(?:tahun|thn)/', $raw, $m) => max(1, (int) $m[1]),
            (bool) preg_match('/(\d+)\s*bulan/', $raw, $m) => max(1, (int) round((int) $m[1] / 12)),
            is_numeric($raw) => (float) $raw >= 12 ? max(1, (int) round((float) $raw / 12)) : 1,
            default => $default,
        };
    }

    /**
     * Fuzzy match to Master Berkas (typos, case, symbols, word order, missing/extra words,
     * prefixes like "plast" -> "Plastik"). Pool = the department's masters when it has any.
     *
     * @param  Collection<int, MasterArchive>  $masters
     */
    public function matchMaster(string $rawName, ?int $departmentId, Collection $masters): ?MasterArchive
    {
        $clean = self::alnum($rawName);
        if (strlen($clean) < 3) {
            return null;
        }
        $own = $masters->where('department_id', $departmentId);
        $pool = $departmentId !== null && $own->isNotEmpty() ? $own : $masters;

        $words = self::words($rawName);
        $sorted = $words;
        sort($sorted);
        foreach ($pool as $master) {
            $masterWords = self::words($master->name);
            sort($masterWords);
            if (strcasecmp(trim($master->name), trim($rawName)) === 0 || self::alnum($master->name) === $clean || ($sorted !== [] && $sorted === $masterWords)) {
                return $master;
            }
        }

        $best = null;
        $bestScore = 0.0;
        foreach ($pool as $master) {
            $score = $this->similarity($words, $clean, $master->name);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$master, $score];
            }
        }

        return $bestScore >= 68 ? $best : null;
    }

    /** @param list<string> $words */
    private function similarity(array $words, string $clean, string $masterName): float
    {
        $masterWords = self::words($masterName);
        $masterClean = self::alnum($masterName);
        if ($masterWords === [] || $masterClean === '') {
            return 0.0;
        }
        $matched = 0.0;
        foreach ($words as $word) {
            foreach ($masterWords as $masterWord) {
                $longEnough = strlen($word) >= 4 || strlen($masterWord) >= 4;
                if ($word === $masterWord) {
                    $matched += 1.0;
                    break;
                }
                if ((strlen($word) >= 4 && str_starts_with($masterWord, $word)) || (strlen($masterWord) >= 4 && str_starts_with($word, $masterWord))) {
                    $matched += 0.95;
                    break;
                }
                similar_text($word, $masterWord, $wordSim);
                if ($wordSim >= 75 && $longEnough) {
                    $matched += $wordSim / 100;
                    break;
                }
            }
        }
        $wordScore = $words !== [] ? $matched / count($words) * 85 : 0;
        // Only when the input is an abbreviation / prefix of the master name (not the reverse).
        $position = strlen($clean) >= 4 ? strpos($masterClean, $clean) : false;
        $substrScore = $position === false ? 0 : ($position === 0 ? 88 : 78);
        similar_text($clean, $masterClean, $sim);
        // Penalize very different lengths ("Form Utilitas" is not "Utilitas").
        $lenRatio = min(strlen($clean), strlen($masterClean)) / max(strlen($clean), strlen($masterClean));

        return max($wordScore, $substrScore, $lenRatio < 0.7 ? $sim * $lenRatio : $sim);
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $map
     */
    private function subHeader(array $row, array &$map): int
    {
        $found = 0;
        foreach ($row as $col => $cell) {
            $clean = strtoupper(trim($cell));
            if (in_array($clean, ['RUANG', 'GUDANG', 'ROOM'], true)) {
                [$map['room'], $found] = [$col, 1];
            } elseif (in_array($clean, ['RAK', 'RACK'], true)) {
                [$map['rack'], $found] = [$col, 1];
            } elseif (in_array($clean, ['NO', 'SLOT', 'NO.'], true) && isset($map['rack']) && $col > $map['rack']) {
                [$map['slot'], $found] = [$col, 1];
            }
        }

        return $found;
    }

    private static function headerText(string $cell): string
    {
        return strtoupper(trim((string) preg_replace('/[_\s]+/', ' ', $cell)));
    }

    private static function alnum(string $text): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $text));
    }

    /** @return list<string> */
    private static function words(string $text): array
    {
        return array_values(array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower($text)) ?: [], fn (string $w): bool => $w !== ''));
    }
}
