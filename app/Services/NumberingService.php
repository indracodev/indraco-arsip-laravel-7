<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Department;
use App\Models\NumberingFormat;
use Carbon\Carbon;

class NumberingService
{
    /**
     * Convert month number (1-12) to Roman numerals.
     */
    public static function toRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $map[$month] ?? 'I';
    }

    /**
     * Generate the next box code for an archive based on active format.
     */
    public function generateBoxCode(Archive $archive): string
    {
        $format = NumberingFormat::where('is_active', true)->first();

        if (!$format) {
            $pattern = '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}';
            $padding = 4;
            $counter = Archive::whereNotNull('box_number')->count() + 1;
        } else {
            $pattern = $format->pattern;
            $padding = $format->padding ?? 4;
            $counter = $format->current_counter + 1;

            // Increment format counter
            $format->increment('current_counter');
        }

        $department = $archive->department ? $archive->department->code : 'GEN';
        $company = 'IND';
        $year = Carbon::parse($archive->period_end_date ?? now())->format('Y');
        $monthNum = (int) Carbon::parse($archive->period_end_date ?? now())->format('n');
        $romanMonth = self::toRoman($monthNum);
        $paddedCounter = str_pad((string) $counter, $padding, '0', STR_PAD_LEFT);

        $replacements = [
            '{COMPANY}' => $company,
            '{DEPT}' => strtoupper($department),
            '{YEAR}' => $year,
            '{ROMAN_MONTH}' => $romanMonth,
            '{COUNTER}' => $paddedCounter,
            '{COUNTER_BOX}' => $paddedCounter,
        ];

        return strtr($pattern, $replacements);
    }
}
