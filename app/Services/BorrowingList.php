<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read side of PENARIKAN (Laravel 7 BorrowingController@index / @create) and the one map of
 * `borrowing_logs.status` to text and colour (Laravel 7 BorrowingLog::status_label + row badges).
 * Department scoping goes through Archive::visibleTo() (IDOR rule).
 */
final class BorrowingList
{
    /** Statuses that block a new request and that the warehouse can still act on. */
    public const ACTIVE = ['requested', 'dept_approved', 'approved'];

    /** status => [label (status_label), marker (row badge), tone]; Laravel 7 order (filter). */
    public const STATUSES = [
        'requested' => ['Diajukan User', 'BARU', 'warning'],
        'dept_approved' => ['Disetujui Dept', 'VERIF DEPT', 'info'],
        'approved' => ['Disetujui Gudang', 'SIAP AMBIL', 'info'],
        'dispatched' => ['Sudah Ditarik', 'DITARIK', 'info'],
        'returned' => ['Dikembalikan', 'KEMBALI', 'success'],
        'rejected' => ['Ditolak', 'DITOLAK', 'danger'],
    ];

    public const SORTS = ['created_at', 'borrow_date', 'request_date', 'expected_return_date', 'status'];

    public static function label(string $status): string
    {
        return self::STATUSES[$status][0] ?? 'Unknown';
    }

    /**
     * One row per box (its latest penarikan; older ones show as history in the detail).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BorrowingLog>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $isGudang = $user->role === User::ROLE_PIC_GUDANG;
        $query = BorrowingLog::query()
            ->with(['archive.department', 'archive.subDepartment', 'archive.location.warehouse', 'archive.rackSlot',
                'archive.borrowingLogs' => fn ($q) => $q->where('status', 'rejected')->orderByDesc('id'),
                'borrower', 'picGudang', 'departmentApprovedBy'])
            ->when(! $isGudang, fn ($q) => $q->with('archive.items'))
            ->whereHas('archive', fn (Builder $q) => $q->visibleTo($user))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('borrowing_logs as newer')
                ->whereColumn('newer.archive_id', 'borrowing_logs.archive_id')->whereColumn('newer.id', '>', 'borrowing_logs.id'));

        if (filled($filters['search'] ?? null)) {
            $like = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('purpose', 'like', $like)
                ->orWhereHas('archive', fn (Builder $a) => $a->where('title', 'like', $like)->orWhere('box_number', 'like', $like)
                    ->when(! $isGudang, fn ($a) => $a->orWhere('content_description', 'like', $like)) // Laravel 7: not for PIC Gudang
                    ->orWhereHas('location', fn (Builder $l) => $l->where('rack_code', 'like', $like)))
                ->orWhereHas('borrower', fn (Builder $b) => $b->where('name', 'like', $like)));
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        // Laravel 7: requests the warehouse can still act on first, then the chosen sort.
        $query->orderByRaw("CASE WHEN status IN ('requested', 'dept_approved', 'approved') THEN 0 ELSE 1 END");
        $sort = ($filters['sort'] ?? null) === 'borrowed_at' ? 'borrow_date' : ($filters['sort'] ?? 'created_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(in_array($sort, self::SORTS, true) ? $sort : 'created_at', $direction);

        return $query->orderBy('id', $direction)->paginate(10)->withQueryString();
    }

    /**
     * Boxes offered by the create form: `in_warehouse` within what the user may see, with the
     * Laravel 7 markers (active penarikan, rejected before + its note).
     *
     * @return Collection<int, Archive>
     */
    public function candidates(User $user): Collection
    {
        return Archive::query()->visibleTo($user)->where('status', 'in_warehouse')
            ->with(['department:id,code,name', 'subDepartment:id,code,name', 'location.warehouse:id,name', 'rackSlot:id,slot_code,slot_number',
                'items:id,archive_id,item_number,document_name,period_start,period_end,period_text,notes',
                'borrowingLogs' => fn ($q) => $q->select('id', 'archive_id', 'status', 'approval_notes')->orderByDesc('id')])
            ->orderBy('box_number')->get();
    }

    /**
     * Files of one penarikan row (Laravel 7 borrowings/index $borrowingFiles, same names), linked
     * through files.preview_data / files.download only. The gudang receipt scan is listed when it
     * differs from the PIC approval form (see DispatchBorrowing).
     *
     * @return list<array{name: string, note: string, type: string, data: string, download: string}>
     */
    public static function files(BorrowingLog $log): array
    {
        $sources = [
            ['Scan Formulir Approval Penarikan', 'Formulir Persetujuan Penarikan', 'borrowing', $log, filled($log->approval_file) ? 'approval_file' : 'scan_approval_borrow'],
            ['Scan Bukti Tanda Terima Fisik', 'Pengeluaran Gudang', 'borrowing', $log, 'scan_approval_borrow'],
            ['Lampiran Softcopy Arsip', 'Softcopy Dokumen Box', 'archive', $log->archive, 'file_path'],
            ['Scan Formulir Input Box', 'Pendaftaran Awal Box', 'archive', $log->archive, 'scan_input_form'],
        ];
        $files = [];
        $seen = [];
        foreach ($sources as [$name, $note, $source, $record, $field]) {
            $path = (string) $record?->getAttribute($field);
            if ($path === '' || in_array($path, $seen, true)) {
                continue;
            }
            $seen[] = $path;
            $params = ['source' => $source, 'id' => $record->getKey(), 'field' => $field];
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $files[] = ['name' => $name, 'note' => $note.' ('.strtoupper($extension).')', 'type' => $extension === 'pdf' ? 'pdf' : 'image',
                'data' => route('files.preview_data', $params), 'download' => route('files.download', $params)];
        }

        return $files;
    }

    /**
     * Data of one box for the create form (Alpine), Laravel 7 field names.
     *
     * @return array<string, mixed>
     */
    public static function candidate(Archive $archive): array
    {
        $active = $archive->borrowingLogs->first(fn (BorrowingLog $l): bool => in_array($l->status, self::ACTIVE, true));
        $rejected = $active ? null : $archive->borrowingLogs->firstWhere('status', 'rejected');

        return [
            'id' => $archive->id, 'box_number' => $archive->box_number, 'title' => $archive->title,
            'dept' => trim(($archive->department->code ?? '').' - '.($archive->department->name ?? ''), ' -') ?: 'UMUM',
            'dept_code' => $archive->department->code ?? 'GEN',
            'location' => $archive->location ? $archive->displayLocation() : 'Gudang',
            'period' => $archive->period_text ?: ($archive->periode_doc ?: '-'),
            'condition' => $archive->physical_condition ?: 'Baik',
            'items' => $archive->items->map(fn ($item): array => [
                'number' => $item->item_number, 'name' => $item->document_name ?: '-',
                'period' => $item->period_text ?: ($item->period_start ? trim($item->period_start.' s/d '.$item->period_end) : '-'),
                'notes' => $item->notes ?: '-',
            ])->all(),
            'busy' => $active !== null,
            'rejected' => $rejected !== null,
            'rejected_note' => $rejected?->approval_notes,
        ];
    }
}
