<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Department;
use App\Models\MasterArchive;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Read-only catalog query with the Laravel 7 ArchiveController@index filters, sort and paging.
 * Department scoping goes through Archive::visibleTo() (IDOR rule).
 */
class ArchiveCatalog
{
    /** Laravel 7 allowed sort columns. */
    public const SORTS = ['box_number', 'title', 'created_at', 'updated_at', 'retention_expiry_date', 'status'];

    /** Laravel 7 default page size is 10; at most 50 rows per page (rules/50-frontend-blade.md). */
    private const MAX_PER_PAGE = 50;

    /**
     * @param  array<string, mixed>  $filters  validated query string
     * @return LengthAwarePaginator<int, Archive>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Archive::query()
            ->visibleTo($user)
            ->with([
                'department:id,code,name', 'subDepartment:id,code,name', 'creator:id,name',
                'location.warehouse:id,name', 'rackSlot:id,slot_code,slot_number', 'destructionLog:id,archive_id,approval_status',
                'borrowingLogs' => fn ($q) => $q->select('id', 'archive_id', 'status', 'approval_file', 'scan_approval_borrow', 'approval_notes')
                    ->orderByDesc('created_at')->orderByDesc('id'),
            ]);

        $this->applyFilters($query, $user, $filters);
        $this->applySort($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->paginate(min($perPage > 0 ? $perPage : 10, self::MAX_PER_PAGE))->withQueryString();
    }

    /**
     * Departments for the filter: PIC Departemen only their own (locked); non-admin only active.
     *
     * @return Collection<int, Department>
     */
    public function departments(User $user): Collection
    {
        return Department::query()
            ->with(['subDepartments' => fn ($q) => $q->when(! $user->isAdmin(), fn ($q) => $q->where('is_active', true))->orderBy('name')])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('is_active', true))
            ->when($user->role === User::ROLE_PIC_DEPT, fn ($q) => $q->whereKey($user->department_id))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    /**
     * Document types offered by the filter: active Master Berkas types plus types already used
     * by archives (Laravel 7 merged both), within what the user may see.
     *
     * @return list<string>
     */
    public function documentTypes(User $user): array
    {
        $pic = $user->role === User::ROLE_PIC_DEPT;
        $master = MasterArchive::query()->where('is_active', true)
            ->when($pic, fn ($q) => $q->where('department_id', $user->department_id))
            ->whereNotNull('document_type')->where('document_type', '!=', '')
            ->distinct()->pluck('document_type');
        $used = Archive::query()->visibleTo($user)
            ->whereNotNull('document_type')->where('document_type', '!=', '')
            ->distinct()->pluck('document_type');

        return $master->concat($used)->map(fn ($t) => trim((string) $t))->unique()->sort()->values()->all();
    }

    /**
     * @param  Builder<Archive>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, User $user, array $filters): void
    {
        if (filled($filters['search'] ?? null)) {
            $like = '%'.$filters['search'].'%';
            $query->where(function (Builder $q) use ($like, $user) {
                $q->where('title', 'like', $like)->orWhere('box_number', 'like', $like);
                // Laravel 7: PIC Gudang searches only box number, title and rack.
                if ($user->role !== User::ROLE_PIC_GUDANG) {
                    $q->orWhere('custom_doc_name', 'like', $like)->orWhere('periode_doc', 'like', $like)
                        ->orWhere('period_text', 'like', $like)->orWhere('content_description', 'like', $like);
                }
                $q->orWhereHas('location', fn (Builder $l) => $l->where('rack_code', 'like', $like));
            });
        }

        foreach (['department_id', 'sub_department_id', 'document_type'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, $filters[$column]);
            }
        }

        match ($filters['status'] ?? null) {
            null, '' => null,
            'borrow_requested' => $query->whereHas('borrowingLogs', fn (Builder $q) => $q->whereIn('status', ['requested', 'dept_approved', 'approved'])),
            'borrowed', 'out' => $query->whereIn('status', ['borrowed', 'taken']),
            default => $query->where('status', $filters['status']),
        };

        if (in_array($filters['expiry_filter'] ?? null, ['expiring_soon', 'expired'], true)) {
            $query->whereNotNull('retention_expiry_date')->whereNotIn('status', ['destroyed', 'draft']);
            $filters['expiry_filter'] === 'expired'
                ? $query->whereDate('retention_expiry_date', '<', Carbon::today())
                : $query->whereDate('retention_expiry_date', '>=', Carbon::today())->whereDate('retention_expiry_date', '<=', Carbon::now()->addDays(90));
        }
    }

    /**
     * Default (Laravel 7): submitted/active boxes first, drafts last, newest change first.
     *
     * @param  Builder<Archive>  $query
     */
    private function applySort(Builder $query, ?string $sort, ?string $direction): void
    {
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, self::SORTS, true)) {
            $query->orderBy($sort, $direction);
            if (in_array($sort, ['created_at', 'updated_at'], true)) {
                $query->orderBy('id', $direction);
            }

            return;
        }

        $query->orderByRaw("CASE WHEN status = 'draft' THEN 1 ELSE 0 END ASC")->orderByDesc('updated_at')->orderByDesc('id');
    }
}
