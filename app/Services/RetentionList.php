<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\DestructionLog;
use App\Models\User;
use App\Support\StatusLabel;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read side of RETENSI ([Form 3] Laravel 7 DestructionController@index / proposeForm /
 * extendForm) and the one map of destruction / extension statuses to text and colour.
 * Department scoping goes through Archive::visibleTo() (IDOR rule).
 */
final class RetentionList
{
    /**
     * Archive status a box must have to be proposed for destruction or extension. Fix B2:
     * Laravel 7 only excluded destroyed / draft / pending_destruction / pending_extension, so a
     * box still waiting for verification, booked, or withdrawn (taken) could be proposed, and
     * approving an extension then put it back to `in_warehouse`.
     */
    public const CANDIDATE_STATUS = 'in_warehouse';

    /** Rows per page of each [Form 3] tab (P1, PROJECT_RULES_SQLITE 3.3). */
    public const PER_PAGE = 50;

    /** SQL: last day of the stored expiry month (Y-m-d), the end RetentionCalculator::expiryEnd() uses. */
    private const EXPIRY_END_SQL = "date(archives.retention_expiry_date, 'start of month', '+1 month', '-1 day')";

    /** destruction_logs.approval_status => [label, tone] (Laravel 7 index). */
    public const DESTRUCTION = [
        'pending' => ['Pengajuan Pemusnahan', 'warning'],
        'approved' => ['Antrean Pemusnahan', 'info'],
        'rejected' => ['Ditolak', 'danger'],
        'completed' => ['Telah Dimusnahkan', 'success'],
    ];

    /** archive_extension_logs.approval_status => [label, tone] (Laravel 7 ArchiveExtensionLog). */
    public const EXTENSION = [
        'pending' => ['Menunggu ACC Gudang', 'warning'],
        'approved' => ['Disetujui Gudang', 'success'],
        'rejected' => ['Ditolak', 'danger'],
    ];

    public function __construct(private RetentionCalculator $calculator) {}

    /**
     * Boxes that may be proposed (destruction or extension): in the warehouse, no active
     * destruction log, no pending extension (Laravel 7 candidate query + B2).
     *
     * @return Builder<Archive>
     */
    public function candidates(User $user): Builder
    {
        return Archive::query()->visibleTo($user)->where('status', self::CANDIDATE_STATUS)
            ->whereDoesntHave('destructionLogs', fn (Builder $q) => $q->whereIn('approval_status', ['pending', 'approved', 'completed']))
            ->whereDoesntHave('extensionLogs', fn (Builder $q) => $q->where('approval_status', 'pending'));
    }

    /**
     * The chosen boxes, refused (DomainException, Laravel 7 text) when one is not a candidate.
     * Called before the upload and again inside the IMMEDIATE transaction (double submit / two
     * sessions). Department ownership is checked by the controller (policy, 403) before this.
     *
     * @param  list<int>  $ids
     * @return EloquentCollection<int, Archive>
     */
    public function pick(User $user, array $ids): EloquentCollection
    {
        $archives = Archive::query()->with('department')->whereIn('id', $ids)->orderBy('id')->get();
        if ($archives->count() !== count($ids)) {
            throw new DomainException('Satu atau lebih berkas arsip yang dipilih tidak ditemukan.');
        }
        $allowed = $this->candidates($user)->whereIn('id', $ids)->pluck('id')->all();
        foreach ($archives as $archive) {
            if (in_array($archive->id, $allowed)) {
                continue;
            }
            throw new DomainException($archive->status === self::CANDIDATE_STATUS || in_array($archive->status, ['pending_destruction', 'pending_extension', 'destroyed'], true)
                ? "Berkas '{$archive->title}' sedang dalam proses pengajuan pemusnahan/perpanjangan atau telah dimusnahkan."
                : "Berkas '{$archive->title}' tidak tersimpan di gudang (status: ".StatusLabel::archive($archive->status)['label'].'), sehingga belum dapat diajukan pemusnahan/perpanjangan.');
        }

        return $archives;
    }

    /**
     * Tab "Berkas Jatuh Tempo (H-30)": candidates whose retention ends within 30 days or passed,
     * one page, filtered / searched / sorted in SQL (P1). Days and badge per row: RetentionCalculator.
     *
     * @return LengthAwarePaginator<int, array{archive: Archive, expiry: Carbon, days: int, expired: bool, badge: string}>
     */
    public function expiring(User $user, string $search = '', string $sort = '', bool $desc = false): LengthAwarePaginator
    {
        $direction = $desc ? 'desc' : 'asc';
        $query = $this->due($user)->with(['department', 'location.warehouse', 'rackSlot'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('box_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")->orWhereHas('department', fn (Builder $d) => $d->where('code', 'like', "%{$search}%"))));
        if (in_array($sort, ['box_number', 'title'], true)) {
            $query->orderByRaw("lower(archives.{$sort}) {$direction}")->orderBy('expiry_end');
        } else {
            $query->orderBy('expiry_end', $direction);
        }

        return $query->orderBy('archives.id')->paginate(self::PER_PAGE)->through(function (Archive $archive): array {
            /** @var Carbon $end due() only returns boxes with a known end */
            $end = $this->calculator->expiryEnd($archive);
            $days = $this->calculator->daysLeft($end);

            return [
                'archive' => $archive, 'expiry' => $end, 'days' => $days, 'expired' => $days < 0,
                'badge' => $days < 0 ? 'Lewat '.abs($days).' Hari' : ($days === 0 ? 'Hari Ini Jatuh Tempo' : "Jatuh Tempo Bulan Ini (Sisa {$days} Hari)"),
            ];
        });
    }

    /**
     * Due candidates (H-30) with `expiry_end` (Y-m-d). Stored expiry: SQL form of
     * RetentionCalculator::isDue() (end of the expiry month <= today + 30 days, as NotificationFeed);
     * boxes without one (legacy rows) go through the PHP rule itself.
     *
     * @return Builder<Archive>
     */
    public function due(User $user): Builder
    {
        $legacy = $this->legacyDueEnds($user);
        $end = self::EXPIRY_END_SQL;
        $bindings = [];
        if ($legacy !== []) {
            $end = "COALESCE({$end}, CASE archives.id".str_repeat(' WHEN ? THEN ?', count($legacy)).' END)';
            foreach ($legacy as $id => $date) {
                array_push($bindings, $id, $date);
            }
        }
        $limit = Carbon::today()->addDays(RetentionCalculator::NOTICE_DAYS)->toDateString();

        return $this->candidates($user)
            ->where(fn (Builder $q) => $q->whereRaw(self::EXPIRY_END_SQL.' <= ?', [$limit])->orWhereIn('archives.id', array_keys($legacy)))
            ->select('archives.*')->selectRaw("{$end} as expiry_end", $bindings);
    }

    /**
     * id => end of retention (Y-m-d) of the due candidates without a stored expiry.
     * ponytail: evaluated in PHP (period / "N bulan" rules); fine while such legacy rows are few (real data: 1).
     *
     * @return array<int, string>
     */
    private function legacyDueEnds(User $user): array
    {
        return $this->candidates($user)->whereNull('retention_expiry_date')
            ->get(['id', 'retention_expiry_date', 'period_end_date', 'periode_doc', 'tgl_penyerahan', 'created_at', 'retention_years'])
            ->mapWithKeys(fn (Archive $archive): array => [$archive->id => $this->calculator->expiryEnd($archive)])
            ->filter(fn (?Carbon $end): bool => $end !== null && $this->calculator->isDue($end))
            ->map(fn (Carbon $end): string => $end->toDateString())->all();
    }

    /**
     * Tab badges with COUNT queries only (P1); `legacy` = no extension log yet, so the old-code
     * history is shown instead (Laravel 7).
     *
     * @return array{h30: int, extensions: int, destructions: int, legacy: bool}
     */
    public function counts(User $user): array
    {
        $extensions = $this->extensionQuery($user)->count();

        return [
            'h30' => $this->due($user)->count(),
            'extensions' => $extensions ?: $this->legacyQuery($user)->count(),
            'destructions' => $this->destructionQuery($user)->count(),
            'legacy' => $extensions === 0,
        ];
    }

    /** @return LengthAwarePaginator<int, ArchiveExtensionLog> */
    public function extensions(User $user, string $search = ''): LengthAwarePaginator
    {
        $like = "%{$search}%";

        return $this->extensionQuery($user)->with(['archive.department', 'proposedBy', 'approvedBy'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('extension_reason', 'like', $like)
                ->orWhereHas('archive', fn (Builder $a) => $a->where('box_number', 'like', $like)->orWhere('title', 'like', $like))
                ->orWhereHas('proposedBy', fn (Builder $u) => $u->where('name', 'like', $like))))
            ->latest()->orderByDesc('id')->paginate(self::PER_PAGE);
    }

    /**
     * Laravel 7 showed extensions made by the old code (activity log RETENTION_EXTEND, before
     * archive_extension_logs existed) when there is no extension log yet. Read only, not paged.
     *
     * @return Collection<int, array{box: string, title: string, dept: string, months: int, expiry: string, reason: string, user: string, date: string}>
     */
    public function legacyExtensions(User $user, string $search = ''): Collection
    {
        return $this->legacyQuery($user)->latest()->get()->map(function (ActivityLog $log): array {
            $props = (array) $log->getAttribute('properties');
            $archive = isset($props['archive_id']) ? Archive::query()->with('department')->find($props['archive_id']) : null;

            return [
                'box' => (string) ($log->getAttribute('reference_id') ?? $archive->box_number ?? '-'),
                'title' => (string) ($archive->title ?? $log->getAttribute('description')),
                'dept' => (string) ($archive->department->code ?? $log->getAttribute('user_role') ?? '-'),
                'months' => (int) ($props['additional_months'] ?? 0),
                'expiry' => isset($props['new_expiry_date']) ? Carbon::parse($props['new_expiry_date'])->format('M Y') : '-',
                'reason' => (string) ($props['extension_reason'] ?? '-'),
                'user' => (string) ($log->getAttribute('user_name') ?? 'PIC'),
                'date' => (string) $log->getAttribute('created_at')?->format('d M Y H:i'),
            ];
        })->filter(fn (array $row): bool => $search === '' || str_contains(mb_strtolower($row['box'].' '.$row['title'].' '.$row['user'].' '.$row['reason']), mb_strtolower($search)))->values();
    }

    /** @return LengthAwarePaginator<int, DestructionLog> */
    public function destructions(User $user, string $search = ''): LengthAwarePaginator
    {
        $like = "%{$search}%";
        $statuses = array_keys(array_filter(self::DESTRUCTION, fn (array $status): bool => str_contains(mb_strtolower($status[0]), mb_strtolower($search))));

        return $this->destructionQuery($user)->with(['archive.department', 'proposedBy'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('bap_number', 'like', $like)->orWhere('method', 'like', $like)
                ->orWhereIn('approval_status', $statuses)
                ->orWhereHas('archive', fn (Builder $a) => $a->where('box_number', 'like', $like)->orWhere('title', 'like', $like))
                ->orWhereHas('proposedBy', fn (Builder $u) => $u->where('name', 'like', $like))))
            ->latest()->orderByDesc('id')->paginate(self::PER_PAGE);
    }

    /** @return Builder<ArchiveExtensionLog> */
    private function extensionQuery(User $user): Builder
    {
        return ArchiveExtensionLog::query()->whereHas('archive', fn (Builder $q) => $q->visibleTo($user));
    }

    /** @return Builder<DestructionLog> */
    private function destructionQuery(User $user): Builder
    {
        return DestructionLog::query()->whereHas('archive', fn (Builder $q) => $q->visibleTo($user));
    }

    /** @return Builder<ActivityLog> */
    private function legacyQuery(User $user): Builder
    {
        return ActivityLog::query()->where('action', 'RETENTION_EXTEND')
            ->when($user->role === User::ROLE_PIC_DEPT, fn ($q) => $q->where('department_id', $user->department_id));
    }

    /**
     * Data of one box for the propose / extend form (Alpine), Laravel 7 field names.
     *
     * @return array<string, mixed>
     */
    public function candidate(Archive $archive): array
    {
        $end = $this->calculator->expiryEnd($archive);

        return [
            'id' => $archive->id, 'box_number' => $archive->box_number, 'title' => $archive->title,
            'dept' => trim(($archive->department->code ?? '').' - '.($archive->department->name ?? ''), ' -') ?: 'UMUM',
            'dept_code' => $archive->department->code ?? 'GEN',
            'long' => ArchiveBoxBuilder::maxPeriodMonths($archive->department) === 120,
            'location' => $archive->location ? $archive->displayLocation() : 'Gudang',
            'period' => $archive->period_text ?: ($archive->periode_doc ?: '-'),
            'retention' => RetentionCalculator::archiveDuration($archive),
            'expiry' => $end?->format('M Y') ?? '-',
            'expiry_ym' => ($archive->retention_expiry_date ?? $archive->period_end_date)?->format('Y-m'),
            'early' => $this->calculator->earlyNote($archive),
            'items' => $archive->items->map(fn ($item): array => [
                'number' => $item->item_number, 'name' => $item->document_name ?: '-',
                'period' => $item->period_text ?: ($item->period_start ? trim($item->period_start.' s/d '.$item->period_end) : '-'),
                'notes' => $item->notes ?: '-',
            ])->all(),
        ];
    }

    /**
     * One entry of the page file viewer (#file-viewer, same shape as ArchiveFileList).
     *
     * @return array{name: string, note: string, type: string, data: string, download: string}|null
     */
    public static function file(string $name, string $source, int $id, string $field, ?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }
        $params = ['source' => $source, 'id' => $id, 'field' => $field];
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return ['name' => $name, 'note' => strtoupper($extension), 'type' => $extension === 'pdf' ? 'pdf' : 'image',
            'data' => route('files.preview_data', $params), 'download' => route('files.download', $params)];
    }
}
