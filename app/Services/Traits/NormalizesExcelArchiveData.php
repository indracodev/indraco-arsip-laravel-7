<?php

namespace App\Services\Traits;

trait NormalizesExcelArchiveData
{
    protected function colLetterToIndex(string $cellRef): int
    {
        if (preg_match('/^([A-Z]+)/i', $cellRef, $matches)) {
            $letters = strtoupper($matches[1]);
            $len = strlen($letters);
            $num = 0;
            for ($i = 0; $i < $len; $i++) {
                $num = $num * 26 + (ord($letters[$i]) - ord('A') + 1);
            }
            return $num - 1;
        }
        return 0;
    }

    /**
     * Smart Period Range parser (e.g. "jan 25- Des 25" -> ["2025-01", "2025-12"]).
     */
    protected function parsePeriodRange(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) {
            return [null, null];
        }

        $parts = preg_split('/\s*(?:-|s\/d|sd|to|\/)\s*/i', $raw);
        if (count($parts) >= 2) {
            $start = $this->normalizeYearMonth($parts[0]);
            $end = $this->normalizeYearMonth($parts[count($parts) - 1]);
            if ($start && !$end) {
                $end = $start;
            }
            if (!$start && $end) {
                $start = $end;
            }
            return [$start, $end];
        }

        $single = $this->normalizeYearMonth($raw);
        return [$single, $single];
    }

    /**
     * Normalize date string to YYYY-MM with 2-digit and 4-digit year support.
     */
    protected function normalizeYearMonth(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if (empty($raw)) {
            return null;
        }

        // 1. Already YYYY-MM
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // 2. YYYY/MM
        if (preg_match('/^(\d{4})\/(0[1-9]|1[0-2])$/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        // 3. MM-YYYY or MM/YYYY
        if (preg_match('/^(0[1-9]|1[0-2])[\/-](\d{4})$/', $raw, $m)) {
            return "{$m[2]}-{$m[1]}";
        }

        // 4. Year only: YYYY -> YYYY-01
        if (preg_match('/^(\d{4})$/', $raw, $m)) {
            return "{$m[1]}-01";
        }

        // 5. Excel serial date number
        if (is_numeric($raw) && (int)$raw > 30000 && (int)$raw < 60000) {
            $unixTimestamp = ((int)$raw - 25569) * 86400;
            return date('Y-m', $unixTimestamp);
        }

        // 6. Indonesian / English Month Name: e.g. "Jan 25", "Januari 2024", "Sept 25"
        $monthMap = [
            'jan' => '01', 'januari' => '01', 'january' => '01',
            'feb' => '02', 'februari' => '02', 'february' => '02',
            'mar' => '03', 'maret' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06', 'june' => '06',
            'jul' => '07', 'juli' => '07', 'july' => '07',
            'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
        ];

        // Format: MonthName + 2 or 4 digit year: "Jan 25", "Sept 25", "Maret 2024"
        if (preg_match('/([a-z]+)\s*(\d{2,4})/i', $raw, $m)) {
            $mName = strtolower($m[1]);
            $year = $m[2];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }
            if (isset($monthMap[$mName])) {
                return "{$year}-{$monthMap[$mName]}";
            }
        }

        return null;
    }

    /**
     * Smart Retention Parser.
     * Murni bulan jika angka (misal: 60 -> 5 tahun, 12 -> 1 tahun).
     * Jika format teks lama (misal: "5 Tahun" -> 5 tahun, "12 Bulan" -> 1 tahun).
     */
    protected function parseRetentionYears(?string $raw, int $default = 5): int
    {
        if ($raw === null) {
            return $default;
        }
        $raw = trim(strtolower($raw));
        if (empty($raw)) {
            return $default;
        }

        // 1. Text Format Years: "5 Tahun", "10 Thn"
        if (preg_match('/(\d+)\s*(?:tahun|thn)/i', $raw, $m)) {
            return max(1, (int)$m[1]);
        }

        // 2. Text Format Months: "12 Bulan", "24 Bulan", "60 Bulan"
        if (preg_match('/(\d+)\s*bulan/i', $raw, $m)) {
            $months = (int)$m[1];
            return max(1, (int)round($months / 12));
        }

        // 3. Plain integer / numeric: Kolom MASA_SIMPAN_BULAN (pure hitungan bulan tanpa keterangan)
        // Contoh: 60 -> 5 tahun, 36 -> 3 tahun, 12 -> 1 tahun, 120 -> 10 tahun
        if (is_numeric($raw)) {
            $val = (float)$raw;
            if ($val >= 12) {
                return max(1, (int)round($val / 12));
            }
            // Jika di bawah 12 bulan (misal 6 bulan), retensi tahunan minimum arsip adalah 1 tahun
            return 1;
        }

        return $default;
    }
}
