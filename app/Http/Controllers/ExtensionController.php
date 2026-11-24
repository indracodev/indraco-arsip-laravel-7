<?php

namespace App\Http\Controllers;

use App\Actions\Retention\ApproveExtension;
use App\Actions\Retention\RejectExtension;
use App\Actions\Retention\RequestExtension;
use App\Http\Requests\ExtensionRequest;
use App\Models\Archive;
use App\Models\ArchiveExtensionLog;
use App\Models\User;
use App\Services\RetentionCalculator;
use App\Services\RetentionFormPrint;
use App\Services\RetentionList;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Perpanjangan masa simpan (Laravel 7 trait HandlesArchiveExtensions: extendForm / extendStore /
 * extendPrint / approveExtension / rejectExtension) and the printed "Form Perpanjangan".
 * PIC (own department) and admin request; PIC Gudang and admin decide.
 */
class ExtensionController extends Controller
{
    public function create(Request $request, RetentionList $list, ?Archive $archive = null): View
    {
        Gate::authorize('create', ArchiveExtensionLog::class);
        /** @var User $user */
        $user = $request->user();

        return view('destructions.extend', DestructionController::formData($request, $list, $archive)
            + ['userMax' => RetentionCalculator::maxExtensionMonths($user->department)]);
    }

    public function store(ExtensionRequest $request, RequestExtension $action): RedirectResponse
    {
        $user = $this->user($request);
        Gate::authorize('create', ArchiveExtensionLog::class);
        /** @var list<int> $ids */
        $ids = $request->validated('archive_ids');
        DestructionController::authorizeArchives($user, $ids, ArchiveExtensionLog::class, 'Anda hanya dapat memperpanjang masa simpan berkas milik departemen Anda.');
        $scan = $request->file('scan_extension_form');

        try {
            $message = $action->handle($user, $ids, $request->months(), (string) $request->validated('extension_reason'), $scan instanceof UploadedFile ? $scan : null);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['additional_months' => $e->getMessage()]);
        }

        return redirect()->route('destructions.index', ['tab' => 'extensions'])->with('success', $message);
    }

    /** Printable "Form Perpanjangan" of the chosen boxes (opens in a new tab). */
    public function printForm(Request $request, RetentionFormPrint $print): View
    {
        Gate::authorize('create', ArchiveExtensionLog::class);
        $months = max(1, min(120, (int) $request->query('months', 60)));
        $reason = mb_substr((string) $request->query('reason', ''), 0, 1000);

        return view('archives.form-print', ['archive' => new Archive(['title' => 'Form Perpanjangan'])]
            + $print->for($this->user($request), DestructionController::chosen($request), $months, $reason))->with('backUrl', route('destructions.extend_form'));
    }

    /** "Cetak Form" of one box (Laravel 7 extendPrint; findings #5: now ArchivePolicy@view). */
    public function print(Archive $archive): View
    {
        Gate::authorize('view', $archive);
        $archive->load(['department', 'location.warehouse']);

        return view('destructions.extend-print', ['archive' => $archive]);
    }

    public function approve(Request $request, ArchiveExtensionLog $extension, ApproveExtension $action): RedirectResponse
    {
        Gate::authorize('process', $extension);

        return $this->run(fn () => $action->handle($this->user($request), $extension));
    }

    public function reject(Request $request, ArchiveExtensionLog $extension, RejectExtension $action): RedirectResponse
    {
        Gate::authorize('process', $extension);
        $reason = $request->validate(['rejection_note' => ['nullable', 'string', 'max:500']])['rejection_note'] ?? null;

        return $this->run(fn () => $action->handle($this->user($request), $extension,
            filled($reason) ? (string) $reason : 'Dokumen / surat permohonan perpanjangan tidak memenuhi syarat.'), 'info');
    }

    /** @param callable(): string $action */
    private function run(callable $action, string $flash = 'success'): RedirectResponse
    {
        try {
            return back()->with('active_tab', 'extensions')->with($flash, $action());
        } catch (DomainException $e) {
            return back()->with('active_tab', 'extensions')->with('error', $e->getMessage());
        }
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
