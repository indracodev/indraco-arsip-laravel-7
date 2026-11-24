<?php

namespace App\Actions\Retention;

use App\Models\Archive;
use App\Models\DestructionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FileStorage;
use App\Services\RetentionList;
use App\Services\SystemEventStream;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Kirim Pengajuan Pemusnahan" (Laravel 7 DestructionController@propose, texts kept): one
 * `destruction_logs` row per box sharing one approval scan; the box becomes `pending_destruction`
 * and stays in its rack. An admin who fills a BAP number completes it at once (Laravel 7
 * "Sahkan Pemusnahan"): log `completed`, box `destroyed` and out of the rack.
 *
 * Fix B2: only candidate boxes (RetentionList::pick), checked again inside the transaction.
 */
final class ProposeDestruction
{
    public const PENDING_BAP = '(Menunggu Penerbitan BAP)';

    public function __construct(private FileStorage $files, private RetentionList $list) {}

    /**
     * @param  list<int>  $ids
     * @param  array{destruction_date: string, method: string, notes?: string|null, bap_number?: string|null}  $data
     */
    public function handle(User $user, array $ids, array $data, UploadedFile $approval, ?UploadedFile $certificate): string
    {
        $bap = trim((string) ($data['bap_number'] ?? ''));
        $immediate = $user->role !== User::ROLE_PIC_DEPT && $bap !== '' && ! str_starts_with($bap, 'DRAFT') && $bap !== self::PENDING_BAP;
        $bap = $immediate ? $bap : self::PENDING_BAP;
        $status = $immediate ? 'completed' : 'pending';
        $this->list->pick($user, $ids);

        $approvalPath = $this->files->store($approval, 'destruction_approvals');
        $certificatePath = $certificate ? $this->files->store($certificate, 'bap_certificates') : null;
        try {
            $archives = DB::transaction(function () use ($user, $ids, $data, $immediate, $bap, $status, $approvalPath, $certificatePath) {
                $archives = $this->list->pick($user, $ids);
                $logs = [];
                foreach ($archives as $archive) {
                    $log = new DestructionLog([
                        'archive_id' => $archive->id, 'proposed_by_user_id' => $user->id,
                        'department_approval_by' => $user->id, 'department_approved_at' => now(),
                        'approved_by_dept_pic_id' => $immediate ? $user->id : null, 'bap_number' => $bap,
                        'destruction_date' => $data['destruction_date'], 'method' => $data['method'], 'certificate_file' => $certificatePath,
                        'approval_file' => $approvalPath, 'scan_approval_destruction' => $approvalPath, 'is_approval_uploaded' => true,
                        'notes' => $data['notes'] ?? null,
                    ]);
                    $log->approval_status = $status;
                    $log->save();
                    $logs[] = $log->setRelation('archive', $archive);
                    if ($immediate) {
                        CompleteDestruction::destroyArchive($archive);
                    } else {
                        $archive->forceFill(['status' => 'pending_destruction'])->save();
                    }
                }
                SystemEventStream::destructions($logs);
                SystemEventStream::archives('archive_updated', $archives, ['changes' => ['status', 'updated_at']]);

                return $archives;
            });
        } catch (Throwable $e) {
            $this->files->delete([$approvalPath, $certificatePath]);
            throw $e;
        }

        ActivityLogger::logBatch($archives->map(fn (Archive $a): array => [
            'action' => $immediate ? 'DESTRUCTION_COMPLETE' : 'DESTRUCTION_PROPOSE',
            'description' => $immediate
                ? "Pemusnahan berkas '{$a->title}' (Box: {$a->box_number}) disahkan dengan No. BAP {$bap}"
                : "Pengajuan usulan pemusnahan berkas '{$a->title}' (Box: {$a->box_number}) oleh {$user->name}",
            'module' => 'PEMUSNAHAN_RETENSI',
            'properties' => ['archive_id' => $a->id, 'bap_number' => $bap, 'method' => $data['method'], 'destruction_date' => $data['destruction_date'], 'status' => $status],
            'reference_id' => $a->box_number ?: "Arsip ID: {$a->id}", 'user' => $user,
        ])->all());

        $count = $archives->count();
        $boxes = $archives->pluck('box_number')->filter()->implode(', ') ?: "{$count} Box";

        return $immediate
            ? "Proses pemusnahan {$count} box berkas ({$boxes}) berhasil disahkan dengan No. BAP {$bap}."
            : "Pengajuan pemusnahan {$count} box berkas ({$boxes}) berhasil dikirim. Menunggu verifikasi dan persetujuan dari PIC Gudang.";
    }
}
