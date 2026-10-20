<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Carbon\Carbon;

/**
 * Service terpusat untuk kalkulasi masa simpan, rentang periode, dan tanggal kedaluwarsa arsip.
 * DRY: Menghilangkan duplikasi logika retensi antar Controller, Excel Service, dan API.
 */
class ArchiveRetentionService
{
    /**
     * Dapatkan batas maksimal rentang bulan periode untuk suatu departemen/user.
     * Departemen FAT / Keuangan: maksimal 120 bulan (10 tahun).
     * Departemen Umum: maksimal 60 bulan (5 tahun).
     */
    public function getMaxPeriodMonths($departmentId = null, $user = null): int
    {
        $user = $user ?: auth()->user();
        if ($user) {
            $userDept = $user->department;
            if ($userDept && in_array(strtoupper($userDept->code ?? ''), ['FAT', 'FIN', 'FATCLM'])) {
                return 120;
            }
            $userName = strtoupper($user->name ?? '');
            if (str_contains($userName, 'FAT') || str_contains($userName, 'FIN')) {
                return 120;
            }
        }

        if ($departmentId) {
            $dept = is_numeric($departmentId) ? Department::find($departmentId) : $departmentId;
            if ($dept && in_array(strtoupper($dept->code ?? ''), ['FAT', 'FIN', 'FATCLM'])) {
                return 120;
            }
        }

        return 60;
    }

    /**
     * Kalkulasi lengkap masa simpan dokumen arsip.
     */
    public function calculateRetention(
        string $periodeDoc,
        $departmentId = null,
        $subDepartmentId = null,
        $customYears = null,
        ?Carbon $tglPenyerahan = null
    ): array {
        $tglPenyerahan = $tglPenyerahan ?: Carbon::now();
        $periodeDoc = trim($periodeDoc);

        $startDate = null;
        $endDate = null;
        $formattedPeriodDoc = $periodeDoc;
        $periodText = '';
        $isMonthsPeriod = false;
        $monthsCount = 0;

        // Cek format rentang "YYYY/MM - YYYY/MM"
        if (preg_match('/^(\d{4}\/(?:0[1-9]|1[0-2]))\s*(?:-|s\/d|hingga|to)\s*(\d{4}\/(?:0[1-9]|1[0-2]))$/i', $periodeDoc, $matches)) {
            [$sYear, $sMonth] = explode('/', $matches[1]);
            [$eYear, $eMonth] = explode('/', $matches[2]);
            $startDate = Carbon::createFromDate((int)$sYear, (int)$sMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int)$eYear, (int)$eMonth, 1)->endOfMonth()->endOfDay();

            if ($startDate->gt($endDate)) {
                $temp = $startDate;
                $startDate = $endDate->startOfMonth();
                $endDate = $temp->endOfMonth();
            }

            $monthsDiff = $startDate->diffInMonths($endDate) + 1;
            $maxPeriodMonths = $this->getMaxPeriodMonths($departmentId);
            if ($monthsDiff > $maxPeriodMonths) {
                $monthsDiff = $maxPeriodMonths;
                $endDate = $startDate->copy()->addMonths($maxPeriodMonths)->endOfMonth()->endOfDay();
            }

            $formattedPeriodDoc = $startDate->format('Y/m') . ' - ' . $endDate->format('Y/m');
            $periodText = $startDate->isoFormat('MMMM Y') . ' - ' . $endDate->isoFormat('MMMM Y');

        // Cek format single month "YYYY/MM"
        } elseif (preg_match('/^(\d{4})\/(0[1-9]|1[0-2])$/', $periodeDoc, $matches)) {
            $startDate = Carbon::createFromDate((int)$matches[1], (int)$matches[2], 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $startDate->format('Y/m');
            $periodText = $startDate->isoFormat('MMMM Y');

        // Cek format akumulasi bulan murni angka, misal "60" atau "60 Bulan"
        } elseif (preg_match('/^(\d+)\s*(?:bulan|bln|m)?$/i', $periodeDoc, $matches)) {
            $isMonthsPeriod = true;
            $monthsCount = (int)$matches[1];
            $maxPeriodMonths = $this->getMaxPeriodMonths($departmentId);
            if ($monthsCount > $maxPeriodMonths) $monthsCount = $maxPeriodMonths;
            if ($monthsCount < 1) $monthsCount = 1;

            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $startDate->copy()->addMonths($monthsCount)->endOfMonth()->endOfDay();
            $formattedPeriodDoc = "{$monthsCount} Bulan";
            $periodText = "{$monthsCount} Bulan (s/d " . $endDate->isoFormat('MMMM Y') . ")";

        // Fallback default ke bulan penyerahan
        } else {
            $startDate = $tglPenyerahan->copy()->startOfMonth();
            $endDate = $tglPenyerahan->copy()->endOfMonth()->endOfDay();
            $formattedPeriodDoc = $tglPenyerahan->format('Y/m');
            $periodText = $tglPenyerahan->isoFormat('MMMM Y');
        }

        // Tentukan masa simpan efektif (tahun)
        $effectiveRetentionYears = 5;
        if (!empty($customYears) && (int)$customYears > 0) {
            $effectiveRetentionYears = (int)$customYears;
        } elseif ($isMonthsPeriod) {
            $effectiveRetentionYears = $monthsCount >= 12 ? (int)floor($monthsCount / 12) : 0;
        } elseif (!empty($subDepartmentId)) {
            $subDept = is_numeric($subDepartmentId) ? SubDepartment::find($subDepartmentId) : $subDepartmentId;
            if ($subDept && $subDept->retention_years > 0) {
                $effectiveRetentionYears = (int)$subDept->retention_years;
            }
        } elseif (!empty($departmentId)) {
            $dept = is_numeric($departmentId) ? Department::find($departmentId) : $departmentId;
            if ($dept && $dept->retention_years > 0) {
                $effectiveRetentionYears = (int)$dept->retention_years;
            }
        }

        $expiryDate = $isMonthsPeriod 
            ? $endDate->copy()->endOfMonth() 
            : $endDate->copy()->addYears($effectiveRetentionYears)->endOfMonth();

        return [
            'success' => true,
            'periode_doc' => $formattedPeriodDoc,
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'period_text' => $periodText,
            'effective_retention_years' => $effectiveRetentionYears,
            'retention_expiry_date' => $expiryDate->format('Y-m-d'),
            'retention_expiry_formatted' => $expiryDate->isoFormat('D MMMM Y'),
        ];
    }
}
