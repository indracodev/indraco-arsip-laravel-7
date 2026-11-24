<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowingLog;
use App\Models\Department;
use App\Models\DestructionLog;
use App\Models\User;
use App\Models\WarehouseEntryLog;
use App\Support\ActivityLogLabel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Log History & Global Audit Trail" (Laravel 7 AuditLogController, admin only, read only):
 * tabs activity / entry / borrowing / destruction, filters, 20 / 15 rows per page.
 */
class AuditLogController extends Controller
{
    public const TABS = ['activity', 'entry', 'borrowing', 'destruction'];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'search' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', Rule::in(array_keys(ActivityLogLabel::MODULE_FILTER))],
            'user_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $tab = $filters['tab'] ?? 'activity';

        return view('logs.index', [
            'tab' => $tab,
            'logs' => $this->{$tab}($filters)->withQueryString(),
            'stats' => [
                'total' => ActivityLog::query()->count(),
                'today' => ActivityLog::query()->where('created_at', '>=', Carbon::today())->count(),
                'users' => User::query()->count(),
                'modules' => ActivityLog::query()->distinct()->count('module'),
            ],
            'departments' => Department::query()->orderBy('code')->get(['id', 'code', 'name']),
            'users' => $tab === 'activity' ? User::query()->orderBy('name')->get(['id', 'name', 'role']) : collect(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $f
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    private function activity(array $f): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('department:id,code,name')->latest()->latest('id');
        if (! empty($f['search'])) {
            $query->where(fn (Builder $q) => $q->where('description', 'like', "%{$f['search']}%")->orWhere('action', 'like', "%{$f['search']}%")
                ->orWhere('user_name', 'like', "%{$f['search']}%")->orWhere('reference_id', 'like', "%{$f['search']}%")->orWhere('ip_address', 'like', "%{$f['search']}%"));
        }
        if (! empty($f['module']) && $f['module'] !== 'all') {
            $query->whereIn('module', ActivityLogLabel::MODULE_VALUES[$f['module']]);
        }
        $query->when($f['user_id'] ?? null, fn (Builder $q, $id) => $q->where('user_id', $id))
            ->when($f['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id));

        return $this->dates($query, 'created_at', $f)->paginate(20);
    }

    /**
     * @param  array<string, mixed>  $f
     * @return LengthAwarePaginator<int, WarehouseEntryLog>
     */
    private function entry(array $f): LengthAwarePaginator
    {
        $query = WarehouseEntryLog::query()->with(['archive.department:id,code,name', 'archive.location', 'picGudang:id,name', 'location'])->latest('entry_date');

        return $this->dates($this->byArchive($query, $f), 'entry_date', $f)->paginate(15);
    }

    /**
     * @param  array<string, mixed>  $f
     * @return LengthAwarePaginator<int, BorrowingLog>
     */
    private function borrowing(array $f): LengthAwarePaginator
    {
        $query = BorrowingLog::query()->with(['archive:id,title,box_number,department_id', 'borrower:id,name', 'picGudang:id,name'])->latest('request_date');

        return $this->dates($this->byArchive($query, $f), 'request_date', $f)->paginate(15);
    }

    /**
     * @param  array<string, mixed>  $f
     * @return LengthAwarePaginator<int, DestructionLog>
     */
    private function destruction(array $f): LengthAwarePaginator
    {
        // Laravel 7 added `orWhere(bap_number)` outside the group, which bypassed the other filters.
        $query = DestructionLog::query()->with(['archive:id,title,box_number,department_id', 'proposedBy:id,name'])->latest('destruction_date');

        return $this->dates($this->byArchive($query, $f, 'bap_number'), 'destruction_date', $f)->paginate(15);
    }

    /**
     * @template T of Builder
     *
     * @param  T  $query
     * @param  array<string, mixed>  $f
     * @return T
     */
    private function byArchive(Builder $query, array $f, ?string $ownColumn = null): Builder
    {
        if (! empty($f['search'])) {
            $search = "%{$f['search']}%";
            $query->where(fn (Builder $q) => $q->whereHas('archive', fn (Builder $a) => $a->where('title', 'like', $search)->orWhere('box_number', 'like', $search))
                ->when($ownColumn, fn (Builder $q) => $q->orWhere($ownColumn, 'like', $search)));
        }

        return $query->when($f['department_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('archive', fn (Builder $a) => $a->where('department_id', $id)));
    }

    /**
     * @template T of Builder
     *
     * @param  T  $query
     * @param  array<string, mixed>  $f
     * @return T
     */
    private function dates(Builder $query, string $column, array $f): Builder
    {
        return $query->when($f['date_from'] ?? null, fn (Builder $q, $d) => $q->where($column, '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['date_to'] ?? null, fn (Builder $q, $d) => $q->where($column, '<=', Carbon::parse($d)->endOfDay()));
    }
}
