<?php

namespace App\Http\Controllers;

use App\Actions\Retention\ProposeDestruction;
use App\Http\Requests\DestructionProposalRequest;
use App\Models\Archive;
use App\Models\DestructionLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RetentionFormPrint;
use App\Services\RetentionList;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "[Form 3] Pengajuan Perpanjangan & Pemusnahan" (Laravel 7 DestructionController index /
 * proposeForm / propose / showBap) and the printed "Form Pemusnahan". Warehouse actions:
 * DestructionApprovalController; extensions: ExtensionController. Rules: Actions/Retention.
 */
class DestructionController extends Controller
{
    public const TABS = ['h30', 'extensions', 'destructions'];

    public function index(Request $request, RetentionList $list): View
    {
        $user = $this->user($request);
        $tab = match ($request->query('view')) {
            'destruction' => 'destructions',
            'expiry' => 'h30',
            default => in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : session('active_tab', 'h30'),
        };
        $tab = in_array($tab, self::TABS, true) ? $tab : 'h30';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $counts = $list->counts($user);
        // P1: only the active tab is built, one page; page links keep the tab (also when it came from the session).
        $rows = match ($tab) {
            'h30' => $list->expiring($user, $search, (string) $request->query('sort', ''), $request->query('direction') === 'desc'),
            'extensions' => $list->extensions($user, $search),
            default => $list->destructions($user, $search),
        };

        return view('destructions.index', [
            'tab' => $tab,
            'counts' => $counts,
            'rows' => $rows->withQueryString()->appends('tab', $tab),
            'legacyExtensions' => $tab === 'extensions' && $counts['legacy'] ? $list->legacyExtensions($user, $search) : collect(),
        ]);
    }

    public function create(Request $request, RetentionList $list, ?Archive $archive = null): View
    {
        Gate::authorize('create', DestructionLog::class);

        return view('destructions.propose', $this->formData($request, $list, $archive));
    }

    public function store(DestructionProposalRequest $request, ProposeDestruction $propose): RedirectResponse
    {
        $user = $this->user($request);
        Gate::authorize('create', DestructionLog::class);
        /** @var list<int> $ids */
        $ids = $request->validated('archive_ids');
        $this->authorizeArchives($user, $ids, DestructionLog::class, 'Anda hanya dapat mengajukan pemusnahan berkas milik departemen Anda.');

        try {
            /** @var array{destruction_date: string, method: string, notes?: string|null, bap_number?: string|null} $data */
            $data = $request->safe()->only(['destruction_date', 'method', 'notes', 'bap_number']);
            $message = $propose->handle($user, $ids, $data,
                $request->file('approval_file') ?? $request->file('scan_approval_destruction'), $request->file('certificate_file'));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['archive_ids' => $e->getMessage()]);
        }

        return redirect()->route('destructions.index', ['tab' => 'destructions'])->with('success', $message);
    }

    /** Printable "Form Pemusnahan" of the chosen boxes (opens in a new tab). */
    public function printForm(Request $request, RetentionFormPrint $print): View
    {
        Gate::authorize('create', DestructionLog::class);
        $notes = mb_substr((string) $request->query('notes', ''), 0, 1000);

        return view('archives.form-print', ['archive' => new Archive(['title' => 'Form Pemusnahan'])]
            + $print->for($this->user($request), self::chosen($request), null, $notes))->with('backUrl', route('destructions.propose'));
    }

    /** Berita Acara Pemusnahan (Laravel 7 showBap): printable once the gudang uploaded the BAP scan. */
    public function bap(DestructionLog $destruction): View|RedirectResponse
    {
        Gate::authorize('view', $destruction);
        if (blank($destruction->certificate_file)) {
            return back()->with('error', 'File Berita Acara Pemusnahan (BAP) belum diunggah oleh Gudang.');
        }
        $destruction->load(['archive.department', 'proposedBy', 'approvedBy', 'departmentApprovedBy']);
        ActivityLogger::log('DESTRUCTION_BAP_PRINT', "Mencetak Berita Acara Pemusnahan (BAP) No: {$destruction->bap_number} untuk arsip '{$destruction->archive->title}'", 'PEMUSNAHAN_RETENSI',
            ['bap_number' => $destruction->bap_number, 'archive_id' => $destruction->archive_id], $destruction->bap_number);

        return view('destructions.bap', ['log' => $destruction]);
    }

    /**
     * Candidates + preselected ids of the propose / extend forms (Laravel 7: route archive,
     * archive_ids[] or archive_id).
     *
     * @return array{archives: list<array<string, mixed>>, selected: list<int>}
     */
    public static function formData(Request $request, RetentionList $list, ?Archive $archive): array
    {
        $raw = $archive ? [$archive->id] : $request->query('archive_ids', $request->query('archive_id', []));
        $selected = array_values(array_filter(array_map('intval', is_array($raw) ? $raw : [$raw])));
        /** @var User $user */
        $user = $request->user();
        $archives = $list->candidates($user)->with(['department:id,code,name', 'location.warehouse:id,name', 'rackSlot:id,slot_code,slot_number',
            'items:id,archive_id,item_number,document_name,period_start,period_end,period_text,notes'])->orderBy('box_number')->get();

        return ['archives' => $archives->map(fn (Archive $a): array => $list->candidate($a))->values()->all(), 'selected' => $selected];
    }

    /**
     * Department check per chosen box (403 before any status message, so titles of other
     * departments do not leak).
     *
     * @param  list<int>  $ids
     * @param  class-string  $model
     */
    public static function authorizeArchives(User $user, array $ids, string $model, string $message): void
    {
        foreach (Archive::query()->whereIn('id', $ids)->get(['id', 'department_id']) as $archive) {
            abort_unless($user->can('create', [$model, $archive]), 403, $message);
        }
    }

    /** @return list<Archive> chosen boxes the user may see, in the chosen order (print forms) */
    public static function chosen(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $ids = array_slice(array_map('intval', (array) $request->query('archive_ids', [])), 0, 200);
        $found = Archive::query()->visibleTo($user)->with(['department', 'subDepartment'])->whereIn('id', $ids)->get()->keyBy('id');

        return array_values(array_filter(array_map(fn (int $id): ?Archive => $found->get($id), $ids)));
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
