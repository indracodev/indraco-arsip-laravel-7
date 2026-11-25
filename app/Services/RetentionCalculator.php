<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Department;
use Illuminate\Support\Carbon;

/**
 * Retention math of RETENSI, the one implementation (Laravel 7 DestructionController@index,
 * HandlesArchiveExtensions@extendStore / @approveExtension). Period / expiry at submission time
 * stay in ArchiveBoxBuilder; the FAT rule (120 months) is its maxPeriodMonths() (decision ASK 12:
 * department code only).
 */
final class RetentionCalculator
{
    /** Laravel 7 [Form 3]: a box is listed from 30 days before the end of its expiry month. */
    public const NOTICE_DAYS = 30;

    /** Maximum extension in months: 120 for FAT/FIN/FATCLM, else 60 (same rule as the period). */
    public static function maxExtensionMonths(?Department ...$departments): int
    {
        foreach ($departments as $department) {
            if (ArchiveBoxBuilder::maxPeriodMonths($department) === 120) {
                return 120;
            }
        }

        return 60;
    }

    /**
     * End of the retention (end of day of the last day of the expiry month), Laravel 7 index:
     * the stored expiry, else the period end (+ retention years unless the period is "N bulan").
     */
    public function expiryEnd(Archive $archive): ?Carbon
    {
        if ($archive->retention_expiry_date) {
            return $archive->retention_expiry_date->copy()->endOfMonth()->endOfDay();
        }
        $periodDoc = (string) $archive->periode_doc;
        $isMonthly = (bool) preg_match('/^(\d+)\s*(?:bulan|bln)/i', $periodDoc, $months);
        if ($archive->period_end_date) {
            $periodEnd = $archive->period_end_date->copy()->endOfMonth();
        } elseif ($isMonthly) {
            $base = $archive->tgl_penyerahan ?? $archive->created_at ?? now();
            $periodEnd = $base->copy()->startOfMonth()->addMonthsNoOverflow((int) $months[1])->endOfMonth();
        } elseif (preg_match('/(\d{4})[\/\-](\d{1,2})/', $periodDoc, $ym)) {
            $periodEnd = Carbon::create((int) $ym[1], (int) $ym[2], 1)->endOfMonth();
        } else {
            return null;
        }

        return ($isMonthly ? $periodEnd : $periodEnd->addYears($archive->retention_years ?: 5))->endOfMonth()->endOfDay();
    }

    /** Whole days from today to the end of the retention (negative = passed), Laravel 7 diff_days. */
    public function daysLeft(Carbon $expiryEnd): int
    {
        return (int) Carbon::today()->diffInDays($expiryEnd, false);
    }

    /**
     * Q2 (owner 2026-11-25): destruction before the retention end stays allowed but is flagged,
     * e.g. "Belum jatuh tempo (sisa 12 hari)"; null when due/passed or no expiry is known.
     */
    public function earlyNote(Archive $archive): ?string
    {
        $end = $this->expiryEnd($archive);
        $days = $end ? $this->daysLeft($end) : 0;

        return $days > 0 ? "Belum jatuh tempo (sisa {$days} hari)" : null;
    }

    public function isDue(Carbon $expiryEnd): bool
    {
        return Carbon::today()->gte($expiryEnd->copy()->subDays(self::NOTICE_DAYS)->startOfDay());
    }

    /**
     * Expiry before and after an extension: base = stored expiry, else period end, else today;
     * new = base + months, end of month. Fix: no month overflow (Laravel 7 addMonths turned
     * 31 Oct + 1 month into 1 Dec, i.e. end of December; its own form preview said November).
     *
     * @return array{previous: Carbon, new: Carbon}
     */
    public function extendedExpiry(Archive $archive, int $months): array
    {
        $base = ($archive->retention_expiry_date ?? $archive->period_end_date ?? now())->copy()->startOfDay();

        return ['previous' => $base, 'new' => $base->copy()->addMonthsNoOverflow($months)->endOfMonth()->startOfDay()];
    }

    /**
     * Archive retention after an approved extension (Laravel 7 approveExtension): base months =
     * "N bulan" period, else masa_simpan_custom or retention_years in years, else 60.
     *
     * @return array{retention_years: int, periode: string}
     */
    public function approvedRetention(Archive $archive, int $months): array
    {
        $period = trim((string) ($archive->periode ?? $archive->periode_doc ?? ''));
        $base = match (true) {
            (bool) preg_match('/^(\d+)\s*(?:bulan|bln)/i', $period, $m) => (int) $m[1],
            (int) $archive->masa_simpan_custom > 0 => (int) $archive->masa_simpan_custom * 12,
            (int) $archive->retention_years > 0 => (int) $archive->retention_years * 12,
            default => 60,
        };
        $total = $base + $months;

        return ['retention_years' => intdiv($total, 12), 'periode' => $total >= 12 && $total % 12 === 0 ? ($total / 12).' Tahun' : $total.' Bulan'];
    }

    /** Laravel 7 duration label: "5 Tahun" when whole years, else "7 Bulan". */
    public static function durationLabel(int $months): string
    {
        return $months > 0 && $months % 12 === 0 ? ($months / 12).' Tahun' : $months.' Bulan';
    }

    /** Laravel 7 retention_duration_label of an archive (BAP, extension form). */
    public static function archiveDuration(Archive $archive): string
    {
        if (preg_match('/^(\d+)\s*(?:bulan|bln)/i', trim((string) ($archive->periode ?? $archive->periode_doc ?? '')), $m)) {
            return self::durationLabel((int) $m[1]);
        }
        foreach ([$archive->masa_simpan_custom, $archive->retention_years] as $years) {
            if ((int) $years > 0) {
                return $years.' Tahun';
            }
        }

        return '-';
    }
}
