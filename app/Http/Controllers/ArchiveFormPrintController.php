<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\User;
use App\Services\ArchiveFormPrint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Printable "Form Pengajuan" (A4, Laravel 7 form-pengajuan-modal) of a saved draft/submission.
 * Access = reading the archive (ArchivePolicy@view: PIC Departemen only their department).
 */
class ArchiveFormPrintController extends Controller
{
    public function __invoke(Request $request, Archive $archive, ArchiveFormPrint $print): View
    {
        Gate::authorize('view', $archive);
        $archive->load(['department', 'items']);

        /** @var User $user */
        $user = $request->user();

        return view('archives.form-print', ['archive' => $archive] + $print->for($user, $archive))
            ->with('backUrl', $user->can('update', $archive) ? route('archives.edit', $archive) : route('archives.index'));
    }
}
