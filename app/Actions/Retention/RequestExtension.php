<?php

namespace App\Actions\Retention;

use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FileStorage;
use App\Services\RetentionCalculator;
use App\Services\RetentionList;
use App\Services\SystemEventStream;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Ajukan Perpanjangan Masa Simpan" (Laravel 7 HandlesArchiveExtensions@extendStore, texts kept):
 * one `archive_extension_logs` row per box (`pending`, expiry before/after from
 * RetentionCalculator), the box becomes `pending_extension`. Only candidate boxes (B2), checked
 * again inside the transaction.
 */
final class RequestExtension
{
    public function __construct(private FileStorage $files, private RetentionList $list, private RetentionCalculator $calculator) {}

    /** @param list<int> $ids */
    public function handle(User $user, array $ids, int $months, string $reason, ?UploadedFile $scan): string
    {
        $this->list->pick($user, $ids);
        $label = RetentionCalculator::durationLabel($months);
        $years = round($months / 12, 1);

        $path = $scan ? $this->files->store($scan, 'archive_extensions') : null;
        try {
            /** @var list<array{0: Archive, 1: string, 2: string|null}> $rows [box, new expiry Y-m-d, scan path] */
            $rows = DB::transaction(function () use ($user, $ids, $months, $years, $reason, $path): array {
                $archives = $this->list->pick($user, $ids);
                $rows = [];
                foreach ($archives as $archive) {
                    $expiry = $this->calculator->extendedExpiry($archive, $months);
                    $file = $path ?: $archive->scan_extension_form;
                    $log = new ArchiveExtensionLog([
                        'archive_id' => $archive->id, 'proposed_by_user_id' => $user->id, 'additional_months' => $months,
                        'additional_years' => $years, 'previous_expiry_date' => $expiry['previous']->format('Y-m-d'),
                        'new_expiry_date' => $expiry['new']->format('Y-m-d'), 'extension_reason' => $reason, 'scan_extension_form' => $file,
                    ]);
                    $log->approval_status = 'pending';
                    $log->save();
                    $archive->fill(['extension_reason' => $reason, 'scan_extension_form' => $file])->forceFill(['status' => 'pending_extension'])->save();
                    $rows[] = [$archive, $expiry['new']->format('Y-m-d'), $file];
                }
                SystemEventStream::archives('archive_updated', $archives, ['changes' => ['extension_reason', 'scan_extension_form', 'status', 'updated_at']]);

                return $rows;
            });
        } catch (Throwable $e) {
            $this->files->delete([$path]);
            throw $e;
        }

        ActivityLogger::logBatch(array_map(fn (array $row): array => [
            'action' => 'RETENTION_EXTEND_PROPOSED',
            'description' => "Pengajuan perpanjangan masa retensi berkas '{$row[0]->title}' (Box: {$row[0]->box_number}) sebanyak +{$label} diajukan oleh {$user->name} dan menunggu verifikasi/ACC Gudang.",
            'module' => 'PEMUSNAHAN_RETENSI',
            'properties' => ['archive_id' => $row[0]->id, 'additional_months' => $months, 'additional_years' => $years, 'new_expiry_date' => $row[1],
                'extension_reason' => $reason, 'scan_extension_form' => $row[2], 'approval_status' => 'pending'],
            'reference_id' => $row[0]->box_number ?: "Arsip ID: {$row[0]->id}", 'user' => $user,
        ], $rows));

        $count = count($rows);
        $boxes = implode(', ', array_filter(array_map(fn (array $row): ?string => $row[0]->box_number, $rows))) ?: "{$count} Box";

        return "Pengajuan perpanjangan masa simpan {$count} box berkas ({$boxes}) sebanyak +{$label} berhasil dikirim. Menunggu pengecekan dokumen & persetujuan (ACC) dari Admin Gudang.";
    }
}
