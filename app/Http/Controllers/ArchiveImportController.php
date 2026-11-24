<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveImportRequest;
use App\Models\Archive;
use App\Models\User;
use App\Services\ArchiveImport;
use App\Services\ArchiveImportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Impor Multi-Box (Excel)" (Laravel 7 ArchiveImportController, same URLs/names): template,
 * preview, commit, load-to-form. Route `role:pic_dept` (admin passes) + ArchivePolicy create,
 * so PIC Gudang gets 403 like Laravel 7. Rules: App\Services\ArchiveImport.
 */
class ArchiveImportController extends Controller
{
    public function template(Request $request, ArchiveImportTemplate $template): Response
    {
        Gate::authorize('create', Archive::class);

        return $template->download((string) $request->query('format', 'xlsx'), $this->user($request));
    }

    public function preview(ArchiveImportRequest $request, ArchiveImport $import): JsonResponse
    {
        Gate::authorize('create', Archive::class);

        return response()->json($import->preview($this->file($request), $this->user($request)));
    }

    public function commit(ArchiveImportRequest $request, ArchiveImport $import): JsonResponse
    {
        Gate::authorize('create', Archive::class);
        $scan = $request->file('scan_input_form');
        $result = $import->commit($this->file($request), $this->user($request), (string) $request->validated('status'), $scan instanceof UploadedFile ? $scan : null);
        if ($result['errors'] !== []) {
            return response()->json(['success' => false, 'errors' => $result['errors']], 422);
        }
        $request->session()->flash('success', $result['message']);

        return response()->json(['success' => true, 'message' => $result['message'], 'redirect_url' => route('archives.index')]);
    }

    /** Fills the create form through old input (the form already restores `boxes` from it). */
    public function loadToForm(ArchiveImportRequest $request, ArchiveImport $import): JsonResponse
    {
        Gate::authorize('create', Archive::class);
        $result = $import->formInput($this->file($request), $this->user($request));
        if ($result['errors'] !== []) {
            return response()->json(['success' => false, 'errors' => $result['errors']], 422);
        }
        $request->session()->flashInput($result['input']);
        $request->session()->flash('excel_import', ['boxes' => $result['boxes'], 'items' => $result['items']]);

        return response()->json(['success' => true, 'redirect_url' => route('archives.create', ['from' => 'excel_import'])]);
    }

    private function file(Request $request): UploadedFile
    {
        /** @var UploadedFile */
        return $request->file('file');
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
