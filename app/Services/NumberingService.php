<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Department;
use App\Models\NumberingFormat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
    public function generateBoxCode(Archive $archive, bool $preview = false): string
    {
        $format = NumberingFormat::where('is_active', true)->first();

        if (!$format) {
            // ponytail: count()+1 is racy; only used when no active format exists
            $pattern = '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}';
            $padding = 4;
            $counter = Archive::whereNotNull('box_number')->count() + 1;
        } else {
            $pattern = $format->pattern;
            $padding = $format->padding ?? 4;

            if ($preview) {
                $counter = $format->current_counter + 1;
            } else {
                // Atomic increment + read in one statement so concurrent requests never share a counter
                $counter = (int) DB::selectOne(
                    'UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter',
                    [$format->id]
                )->current_counter;
            }
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

        $code = strtr($pattern, $replacements);

        if (!$preview) {
            // Self-healing collision guard: pastikan kode unik terhadap arsip aktif
            while (Archive::where('box_number', $code)->whereNotIn('status', ['taken', 'destroyed', 'borrowed'])->exists()) {
                if ($format) {
                    $updated = DB::select("UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter", [$format->id]);
                    $counter = !empty($updated) ? (int) $updated[0]->current_counter : ($counter + 1);
                } else {
                    $counter++;
                }
                $paddedCounter = str_pad((string) $counter, $padding, '0', STR_PAD_LEFT);
                $replacements['{COUNTER}'] = $paddedCounter;
                $replacements['{COUNTER_BOX}'] = $paddedCounter;
                $code = strtr($pattern, $replacements);
            }
        }

        return $code;
    }
}
