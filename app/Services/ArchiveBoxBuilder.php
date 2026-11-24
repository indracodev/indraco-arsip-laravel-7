<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Support\Carbon;

/**
 * Column values of submitted boxes and of the one-row multi-box draft: document lines, period,
 * retention and expiry. The single implementation of these rules (Laravel 7
 * HandlesMultiBoxCreation/Update + ArchiveRetentionService::getMaxPeriodMonths).
 *
 * Texts like period_text keep the English month names Laravel 7 stores (its locale is `en`).
 */
final class ArchiveBoxBuilder
{
    public const DEFAULT_CONDITION = 'Baik / Box Karton Standar TB 30g';

    public const DEFAULT_COMPANY = 'PT INDRACO GLOBAL INDONESIA';

    /** Departments allowed 120 months instead of 60 (decision ASK 12: department code only). */
    public const LONG_PERIOD_CODES = ['FAT', 'FIN', 'FATCLM'];

    public static function maxPeriodMonths(?Department $department): int
    {
        return in_array(strtoupper((string) $department?->code), self::LONG_PERIOD_CODES, true) ? 120 : 60;
    }

    /**
     * Document lines of one box; lines without a document name are skipped. A submission clamps
     * periods to the current month and start <= end (Laravel 7), a draft keeps them as typed.
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return list<array{item_number: int, document_name: string, period_start: string|null, period_end: string|null, period_text: string|null, notes: string|null}>
     */
    public function items(array $rawItems, bool $clamp): array
    {
        $currentMonth = now()->format('Y-m');
        $items = [];
        foreach ($rawItems as $raw) {
            $name = trim((string) ($raw['document_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $start = trim((string) ($raw['period_start'] ?? ''));
            $end = trim((string) ($raw['period_end'] ?? ''));
            if ($clamp) {
                $end = $end !== '' && $end > $currentMonth ? $currentMonth : $end;
                $start = $start !== '' && $start > $currentMonth ? $currentMonth : $start;
                $start = $start !== '' && $end !== '' && $start > $end ? $end : $start;
            }
            $text = $start !== '' && $end !== '' ? ($start === $end ? $start : "{$start} s/d {$end}") : $start;
            $notes = trim((string) ($raw['notes'] ?? ''));
            $items[] = [
                'item_number' => count($items) + 1,
                'document_name' => $name,
                'period_start' => $start ?: null,
                'period_end' => $end ?: null,
                'period_text' => $text ?: null,
                'notes' => $notes ?: null,
            ];
        }

        return $items;
    }

    /**
     * Box line text kept in content_description of a submitted box (Laravel 7 format).
     *
     * @param  list<array{item_number: int, document_name: string, period_text: string|null, notes: string|null}>  $items
     */
    public function contentLines(array $items): string
    {
        return implode("\n", array_map(fn (array $it): string => $it['item_number'].". {$it['document_name']}"
            .($it['period_text'] ? " ({$it['period_text']})" : '').($it['notes'] ? " - {$it['notes']}" : ''), $items));
    }

    /**
     * Period, retention and expiry of one submitted box.
     *
     * @param  array<string, mixed>  $box
     * @param  list<array{period_start: string|null, period_end: string|null}>  $items
     * @return array<string, mixed>
     */
    public function boxAttributes(array $box, array $items, Department $department, Carbon $handover): array
    {
        $starts = array_filter(array_column($items, 'period_start'));
        $ends = array_filter(array_column($items, 'period_end'));
        $min = $starts ? min($starts) : null;
        $max = $ends ? max($ends) : null;
        $months = $this->months((string) ($box['periode'] ?? ''), $department);

        if ($months !== null) {
            $attributes = $this->monthsPeriod($months, $handover);
            $attributes['periode_doc'] = $min ? ($min === $max ? $min : "{$min} - {$max}") : "{$months} Bulan";
        } elseif ($min && $max) {
            $start = Carbon::createFromDate((int) substr($min, 0, 4), (int) substr($min, 5, 2), 1)->startOfDay();
            $end = Carbon::createFromDate((int) substr($max, 0, 4), (int) substr($max, 5, 2), 1)->endOfMonth();
            $limit = self::maxPeriodMonths($department);
            $count = (int) $start->diffInMonths($end) + 1;
            if ($count > $limit) {
                $count = $limit;
                $end = $start->copy()->addMonths($limit)->endOfMonth();
            }
            $attributes = [
                'periode' => "{$count} Bulan",
                'periode_doc' => $min === $max ? $min : "{$min} - {$max}",
                'period_text' => $this->monthName($start).($min !== $max ? ' - '.$this->monthName($end) : ''),
                'period_start_date' => $start->format('Y-m-d'),
                'period_end_date' => $end->format('Y-m-d'),
            ];
            $attributes += $this->retention($box, $department, $end);
        } else {
            $attributes = $this->handoverMonth($box, $department, $handover);
        }
        $attributes['period_yy_mm'] = $attributes['periode_doc'];

        return $attributes;
    }

    /**
     * Columns of the single draft row that holds a whole multi-box submission (Laravel 7: values
     * of the first box, every box as JSON in content_description).
     *
     * @param  list<array<string, mixed>>  $boxes
     * @return array<string, mixed>
     */
    public function draftAttributes(array $boxes, Department $department, Carbon $handover, string $fallbackTitle): array
    {
        $first = $boxes[0] ?? [];
        $title = trim((string) ($first['title'] ?? '')) ?: $fallbackTitle;
        $months = $this->months((string) ($first['periode'] ?? ''), $department);
        if ($months !== null) {
            $attributes = $this->monthsPeriod($months, $handover);
            $attributes['periode_doc'] = $handover->copy()->startOfMonth()->format('Y/m');
        } else {
            $attributes = $this->handoverMonth($first, $department, $handover);
        }

        return $attributes + [
            'title' => count($boxes) > 1 ? "{$title} (".count($boxes).' Box)' : $title,
            'period_yy_mm' => $attributes['periode_doc'],
            'sub_department_id' => $first['sub_department_id'] ?? null,
            'document_type' => $first['document_type'] ?? null,
            'physical_condition' => ($first['physical_condition'] ?? null) ?: self::DEFAULT_CONDITION,
            'content_description' => json_encode(['is_multi_box_draft' => true, 'boxes' => $boxes], JSON_UNESCAPED_UNICODE),
        ];
    }

    /** "N Bulan" (the form always sends this) clamped to 1..max, or null for another format. */
    private function months(string $periode, Department $department): ?int
    {
        if (! preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', trim($periode), $match)) {
            return null;
        }

        return max(1, min((int) $match[1], self::maxPeriodMonths($department)));
    }

    /** @return array<string, mixed> */
    private function monthsPeriod(int $months, Carbon $handover): array
    {
        $start = $handover->copy()->startOfMonth();
        $end = $start->copy()->addMonths($months)->endOfMonth();

        return [
            'periode' => "{$months} Bulan",
            'period_text' => "{$months} Bulan (s/d ".$this->monthName($end).')',
            'period_start_date' => $start->format('Y-m-d'),
            'period_end_date' => $end->format('Y-m-d'),
            'retention_years' => $months >= 12 ? intdiv($months, 12) : 0,
            'retention_expiry_date' => $end->format('Y-m-d'),
        ];
    }

    /**
     * @param  array<string, mixed>  $box
     * @return array<string, mixed>
     */
    private function handoverMonth(array $box, Department $department, Carbon $handover): array
    {
        $end = $handover->copy()->endOfMonth();

        return [
            'periode' => '1 Bulan',
            'periode_doc' => $handover->format('Y/m'),
            'period_text' => $this->monthName($handover),
            'period_start_date' => $handover->copy()->startOfMonth()->format('Y-m-d'),
            'period_end_date' => $end->format('Y-m-d'),
        ] + $this->retention($box, $department, $end);
    }

    /**
     * Retention years: box value, else sub-department, else department, else 5; expiry = end of
     * month of (period end + years).
     *
     * @param  array<string, mixed>  $box
     * @return array{retention_years: int, retention_expiry_date: string}
     */
    private function retention(array $box, Department $department, Carbon $periodEnd): array
    {
        $years = (int) ($box['retention_years'] ?? 0);
        if ($years < 1) {
            $sub = empty($box['sub_department_id']) ? null : SubDepartment::query()->whereKey($box['sub_department_id'])->first();
            $years = $sub !== null && $sub->retention_years > 0 ? $sub->retention_years
                : (($department->retention_years ?? 0) > 0 ? (int) $department->retention_years : 5);
        }

        return [
            'retention_years' => $years,
            'retention_expiry_date' => $periodEnd->copy()->addYears($years)->endOfMonth()->format('Y-m-d'),
        ];
    }

    private function monthName(Carbon $date): string
    {
        return $date->copy()->locale('en')->isoFormat('MMMM Y');
    }
}
