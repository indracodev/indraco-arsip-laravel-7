<?php

namespace App\Http\Controllers;

use App\Actions\Borrowing\SubmitBorrowing;
use App\Http\Requests\BorrowingRequest;
use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Services\BorrowingFormPrint;
use App\Services\BorrowingList;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "[Form 2] Penarikan Berkas" (Laravel 7 BorrowingController index/create/store) and the printed
 * "Form Penarikan". Warehouse actions: BorrowingApprovalController. Rules: Actions/Borrowing.
 */
class BorrowingController extends Controller
{
    public function index(Request $request, BorrowingList $list): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(BorrowingList::STATUSES))],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        return view('borrowings.index', ['borrowings' => $list->paginate($this->user($request), $filters)]);
    }

    public function create(Request $request, BorrowingList $list): View
    {
        Gate::authorize('create', BorrowingLog::class);
        $raw = $request->query('archive_ids', $request->query('archive_id', []));
        $selected = array_values(array_filter(array_map('intval', is_array($raw) ? $raw : explode(',', (string) $raw))));

        return view('borrowings.create', [
            'archives' => $list->candidates($this->user($request))->map(fn (Archive $a): array => BorrowingList::candidate($a))->values(),
            'selected' => $selected,
            'from' => $request->query('from') === 'archives' ? 'archives' : 'borrowings',
        ]);
    }

    public function store(BorrowingRequest $request, SubmitBorrowing $submit): RedirectResponse
    {
        $user = $this->user($request);
        Gate::authorize('create', BorrowingLog::class);
        /** @var list<int> $ids */
        $ids = $request->validated('archive_ids');
        foreach (Archive::query()->whereIn('id', $ids)->get(['id', 'department_id']) as $archive) {
            abort_unless($user->can('create', [BorrowingLog::class, $archive]), 403, 'Anda hanya berhak mengajukan penarikan untuk berkas milik departemen Anda.');
        }

        try {
            $message = $submit->handle($user, $ids, (string) $request->validated('purpose'), $request->file('approval_file'));
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route($request->input('from') === 'archives' ? 'archives.index' : 'borrowings.index')->with('success', $message);
    }

    /** Printable "Form Penarikan" of the boxes chosen in the create form (opens in a new tab). */
    public function printForm(Request $request, BorrowingFormPrint $print): View
    {
        Gate::authorize('create', BorrowingLog::class);
        $user = $this->user($request);
        $ids = array_slice(array_map('intval', (array) $request->query('archive_ids', [])), 0, 200);
        $found = Archive::query()->visibleTo($user)->with(['department', 'subDepartment'])->whereIn('id', $ids)->get()->keyBy('id');
        $archives = array_values(array_filter(array_map(fn (int $id): ?Archive => $found->get($id), $ids)));
        $purpose = $request->query('purpose');

        return view('archives.form-print', ['archive' => new Archive(['title' => 'Form Penarikan'])]
            + $print->for($user, $archives, is_string($purpose) ? mb_substr($purpose, 0, 500) : null))
            ->with('backUrl', route('borrowings.create'));
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
