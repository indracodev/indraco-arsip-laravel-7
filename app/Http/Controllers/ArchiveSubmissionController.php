<?php

namespace App\Http\Controllers;

use App\Actions\Archive\DeleteArchiveDrafts;
use App\Actions\Archive\SaveArchiveDraft;
use App\Actions\Archive\SubmitArchiveBoxes;
use App\Http\Requests\ArchiveSubmissionRequest;
use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Services\ArchiveFormData;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Box submission (Laravel 7 ArchiveController create/store/edit/update/destroy and
 * batchDeleteDrafts, multi-box path only). Rules live in the Actions; access in ArchivePolicy.
 */
class ArchiveSubmissionController extends Controller
{
    public function create(Request $request, ArchiveFormData $form): View
    {
        Gate::authorize('create', Archive::class);

        return view('archives.form', ['archive' => null] + $form->for($this->user($request), null, $this->oldBoxes($request)));
    }

    public function store(ArchiveSubmissionRequest $request, SaveArchiveDraft $draft, SubmitArchiveBoxes $submit): RedirectResponse
    {
        Gate::authorize('create', Archive::class);

        return $this->save($request, $draft, $submit, null);
    }

    public function edit(Request $request, Archive $archive, ArchiveFormData $form): View
    {
        Gate::authorize('update', $archive);
        $archive->load(['department', 'items']);

        return view('archives.form', ['archive' => $archive] + $form->for($this->user($request), $archive, $this->oldBoxes($request)));
    }

    public function update(ArchiveSubmissionRequest $request, Archive $archive, SaveArchiveDraft $draft, SubmitArchiveBoxes $submit): RedirectResponse
    {
        Gate::authorize('update', $archive);
        if ($request->isDraft() && $archive->status !== 'draft') {
            return back()->withInput()->with('error', 'Hanya data berstatus draft yang dapat disimpan sebagai draft.');
        }

        return $this->save($request, $draft, $submit, $archive);
    }

    public function destroy(Request $request, Archive $archive, DeleteArchiveDrafts $delete): RedirectResponse
    {
        Gate::authorize('delete', $archive);
        if ($archive->status !== 'draft') {
            return back()->with('error', 'Hanya data berstatus draft yang dapat dihapus.');
        }

        return $this->run(fn () => $delete->handle($this->user($request), [$archive->id], single: true));
    }

    public function batchDestroy(Request $request, DeleteArchiveDrafts $delete): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $ids = is_string($ids) ? explode(',', $ids) : (array) $ids;

        return $this->run(fn () => $delete->handle($this->user($request), array_map('intval', $ids)));
    }

    private function save(ArchiveSubmissionRequest $request, SaveArchiveDraft $draft, SubmitArchiveBoxes $submit, ?Archive $archive): RedirectResponse
    {
        $user = $this->user($request);
        $department = Department::query()->find($request->departmentId()) ?? Department::query()->orderBy('id')->firstOrFail();
        $uploads = [
            'scan' => $request->file('scan_input_form'),
            'file' => $request->file('file'),
            'remove_scan' => $request->boolean('remove_scan_input_form'),
            'remove_file' => $request->boolean('remove_file'),
        ];
        $original = $request->validated('original_updated_at');

        return $this->run(fn () => $request->isDraft()
            ? $draft->handle($user, $department, $request->validated(), $uploads, $archive, $original)
            : $submit->handle($user, $department, $request->validated(), $uploads, $archive, $original), withInput: true);
    }

    /**
     * Run an action; business-rule refusals (DomainException) go back with their message,
     * anything else is a real error (no SQL text shown to users).
     *
     * @param  callable(): string  $action
     */
    private function run(callable $action, bool $withInput = false): RedirectResponse
    {
        try {
            $message = $action();
        } catch (DomainException $e) {
            $back = back()->with('error', $e->getMessage());

            return $withInput ? $back->withInput() : $back;
        }

        return redirect()->route('archives.index')->with('success', $message);
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    /** @return array<int, mixed>|null */
    private function oldBoxes(Request $request): ?array
    {
        $old = $request->old('boxes');

        return is_array($old) ? $old : null;
    }
}
