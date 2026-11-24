<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\NumberingFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Box number (Laravel 7 NumberingService::generateBoxCode): pattern of the active
 * `numbering_formats` row, placeholders {COMPANY} (IND), {DEPT}, {YEAR} / {ROMAN_MONTH} of
 * `period_end_date` (or today), {COUNTER} / {COUNTER_BOX} padded.
 *
 * - The counter only moves through `UPDATE ... RETURNING` (PROJECT_RULES_SQLITE §3.5.a); call
 *   generate() inside the transaction that saves the archive, so a failed save rolls it back.
 * - preview() never writes (§3.5.b: Laravel 7 once burned the counter on every page view).
 * - A number already held by an active box is skipped (Laravel 7 self-healing loop), with the
 *   same condition as the partial UNIQUE index `idx_archives_box_number_active` (§3.5.c).
 */
class NumberingService
{
    /** Archive statuses outside the partial UNIQUE index on box_number. */
    public const INACTIVE = ['taken', 'destroyed', 'borrowed'];

    public static function toRoman(int $month): string
    {
        return [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';
    }

    /** Next number for display only: the counter is not touched. */
    public function preview(Archive $archive): string
    {
        $format = $this->format();

        return $this->code($archive, $format, $format ? (int) $format->getAttribute('current_counter') + 1 : $this->fallbackCounter());
    }

    /** Issue a number: increments the counter atomically, skipping numbers held by active boxes. */
    public function generate(Archive $archive): string
    {
        $format = $this->format();
        do {
            $counter = $format
                ? (int) DB::selectOne('UPDATE numbering_formats SET current_counter = current_counter + 1 WHERE id = ? RETURNING current_counter', [$format->id])->current_counter
                : $this->fallbackCounter(); // ponytail: no active format = Laravel 7 count()+1; unique only inside the write transaction
            $code = $this->code($archive, $format, $counter);
        } while ($format && self::holder($code) !== null);

        return $code;
    }

    /**
     * The active box (other than $exceptId) holding $boxNumber: the condition of the partial
     * UNIQUE index, so a validation message replaces a constraint error.
     */
    public static function holder(string $boxNumber, ?int $exceptId = null): ?Archive
    {
        return Archive::query()->where('box_number', $boxNumber)
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->whereNotIn('status', self::INACTIVE)->first();
    }

    private function format(): ?NumberingFormat
    {
        return NumberingFormat::query()->where('is_active', true)->first();
    }

    private function fallbackCounter(): int
    {
        return Archive::query()->whereNotNull('box_number')->count() + 1;
    }

    private function code(Archive $archive, ?NumberingFormat $format, int $counter): string
    {
        $date = Carbon::parse($archive->period_end_date ?? now());
        $padded = str_pad((string) $counter, $format->padding ?? 4, '0', STR_PAD_LEFT);

        return strtr($format->pattern ?? '{COMPANY}/{DEPT}/{YEAR}/{ROMAN_MONTH}/{COUNTER}', [
            '{COMPANY}' => 'IND',
            '{DEPT}' => strtoupper($archive->department->code ?? 'GEN'),
            '{YEAR}' => $date->format('Y'),
            '{ROMAN_MONTH}' => self::toRoman((int) $date->format('n')),
            '{COUNTER}' => $padded,
            '{COUNTER_BOX}' => $padded,
        ]);
    }
}
