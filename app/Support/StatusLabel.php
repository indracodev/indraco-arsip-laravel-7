<?php

namespace App\Support;

/**
 * The single map of archive statuses to user-facing text and colour (no second map in JS).
 * `label` = Laravel 7 Archive::status_label; `short` = the shorter text the Laravel 7
 * catalog shows in its STATUS column; `tone` = badge colour from the design system.
 */
final class StatusLabel
{
    /** @var array<string, array{label: string, short: string, tone: string}> */
    public const ARCHIVE = [
        'draft' => ['label' => 'Draft Usulan', 'short' => 'Draft', 'tone' => 'neutral'],
        'pending_verification' => ['label' => 'Menunggu Verifikasi', 'short' => 'Antrean Verifikasi', 'tone' => 'warning'],
        'approved_booked' => ['label' => 'Booking Disetujui', 'short' => 'Approved / Booked', 'tone' => 'info'],
        'in_warehouse' => ['label' => 'Tersimpan di Gudang', 'short' => 'Di Gudang', 'tone' => 'success'],
        'borrowed' => ['label' => 'Sedang Dipinjam', 'short' => 'Keluar (Out)', 'tone' => 'info'],
        'taken' => ['label' => 'Telah Diambil (Permanen)', 'short' => 'Keluar (Out)', 'tone' => 'info'],
        'pending_destruction' => ['label' => 'Pengajuan Pemusnahan', 'short' => 'Pengajuan Pemusnahan', 'tone' => 'danger'],
        'pending_extension' => ['label' => 'Pengajuan Perpanjangan', 'short' => 'Pengajuan Perpanjangan', 'tone' => 'warning'],
        'destroyed' => ['label' => 'Telah Dimusnahkan', 'short' => 'Dimusnahkan', 'tone' => 'danger'],
    ];

    /** Laravel 7 catalog: a draft returned by the warehouse (has a rejection note). */
    public const NEEDS_REVISION = ['label' => 'Perlu Revisi', 'short' => 'Perlu Revisi', 'tone' => 'danger'];

    /** Laravel 7 catalog filter "STATUS WORKFLOW": value => option text, in Laravel 7 order. */
    public const FILTER_OPTIONS = [
        'draft' => 'Draft (Simpan Sementara / Revisi)',
        'pending_verification' => 'Antrean Verifikasi',
        'approved_booked' => 'Approved / Booked',
        'in_warehouse' => 'Di Gudang',
        'pending_extension' => 'Pengajuan Perpanjangan',
        'out' => 'Keluar (Out)',
        'pending_destruction' => 'Antrean Pemusnahan',
        'destroyed' => 'Dimusnahkan',
    ];

    /**
     * @param  string|null  $destructionApproval  destruction_logs.approval_status, when loaded
     * @return array{label: string, short: string, tone: string}
     */
    public static function archive(string $status, bool $hasRejectionNote = false, ?string $destructionApproval = null): array
    {
        if ($status === 'draft' && $hasRejectionNote) {
            return self::NEEDS_REVISION;
        }

        $entry = self::ARCHIVE[$status] ?? ['label' => 'Unknown', 'short' => $status, 'tone' => 'neutral'];
        if ($status === 'pending_destruction' && $destructionApproval === 'approved') {
            $entry['label'] = $entry['short'] = 'Antrean Pemusnahan';
        }

        return $entry;
    }
}
