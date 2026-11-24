<?php

namespace App\Support;

use App\Models\Archive;
use Illuminate\Support\Carbon;

/**
 * Laravel 7 catalog column "MASA SIMPAN (EXPIRY)": the retention end is the end of the
 * expiry month; red when passed, yellow in the last 90 days.
 */
final class ArchiveExpiry
{
    /**
     * @return array{text: string, note: string, tone: string}|null null = no expiry date ("-")
     */
    public static function for(Archive $archive): ?array
    {
        if (! $archive->retention_expiry_date) {
            return null;
        }

        $end = Carbon::parse($archive->retention_expiry_date)->endOfMonth()->endOfDay();
        $days = (int) Carbon::today()->diffInDays($end, false);
        $text = $end->format('M Y');

        return match (true) {
            now()->gt($end) => ['text' => $text, 'note' => '(Lewat '.abs($days).' Hari)', 'tone' => 'danger'],
            $days <= 30 => ['text' => $text, 'note' => "(H-{$days})", 'tone' => 'warning'],
            $days <= 90 => ['text' => $text, 'note' => '('.$archive->retentionDisplay().')', 'tone' => 'warning'],
            default => ['text' => $text, 'note' => '('.$archive->retentionDisplay().')', 'tone' => 'neutral'],
        };
    }
}
