<?php

namespace App\Http\Controllers;

use App\Actions\Retention\ApproveDestruction;
use App\Actions\Retention\CompleteDestruction;
use App\Actions\Retention\RejectDestruction;
use App\Actions\Retention\UploadDestructionCertificate;
use App\Models\DestructionLog;
use App\Models\User;
use App\Services\FileStorage;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Warehouse side of a pemusnahan (Laravel 7 DestructionController approve / reject / complete /
 * uploadBap): PIC Gudang and admin only (DestructionLogPolicy@process). Source-status checks live
 * in the actions; every answer is `back()` with the Laravel 7 message, tab "destructions".
 */
class DestructionApprovalController extends Controller
{
    public function approve(Request $request, DestructionLog $destruction, ApproveDestruction $action): RedirectResponse
    {
        Gate::authorize('process', $destruction);

        return $this->run(fn () => $action->handle($this->user($request), $destruction));
    }

    public function reject(Request $request, DestructionLog $destruction, RejectDestruction $action): RedirectResponse
    {
        Gate::authorize('process', $destruction);
        $reason = (string) $request->validate(['rejection_note' => ['required', 'string', 'max:500']], [
            'rejection_note.required' => 'Alasan penolakan pemusnahan wajib diisi.',
            'rejection_note.max' => 'Alasan penolakan maksimal 500 karakter.',
        ])['rejection_note'];

        return $this->run(fn () => $action->handle($this->user($request), $destruction, $reason), 'info');
    }

    public function complete(Request $request, DestructionLog $destruction, CompleteDestruction $action): RedirectResponse
    {
        Gate::authorize('process', $destruction);
        /** @var array{bap_number: string, destruction_date: string, notes?: string|null} $data */
        $data = $request->validate([
            'bap_number' => ['required', 'string', 'max:100'],
            'destruction_date' => ['required', 'date'],
            'certificate_file' => FileStorage::rules(),
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'bap_number.required' => 'Nomor BAP resmi wajib diisi oleh Gudang.',
            'destruction_date.required' => 'Tanggal pelaksanaan fisik pemusnahan wajib diisi.',
            'certificate_file.max' => 'Ukuran berkas sertifikat BAP maksimal 500 KB.',
        ]);
        $file = $request->file('certificate_file');

        return $this->run(fn () => $action->handle($this->user($request), $destruction, $data, $file instanceof UploadedFile ? $file : null));
    }

    public function uploadBap(Request $request, DestructionLog $destruction, UploadDestructionCertificate $action): RedirectResponse
    {
        Gate::authorize('process', $destruction);
        $rules = FileStorage::rules();
        $rules[0] = 'required';
        $request->validate(['certificate_file' => $rules], [
            'certificate_file.required' => 'Wajib memilih file sertifikat BAP resmi.',
            'certificate_file.max' => 'Ukuran file sertifikat BAP maksimal 500 KB.',
            'certificate_file.mimes' => 'Format file sertifikat BAP harus berupa PDF, JPG, JPEG, atau PNG.',
        ]);
        /** @var UploadedFile $file */
        $file = $request->file('certificate_file');

        return $this->run(fn () => $action->handle($this->user($request), $destruction, $file));
    }

    /** @param callable(): string $action */
    private function run(callable $action, string $flash = 'success'): RedirectResponse
    {
        try {
            return back()->with('active_tab', 'destructions')->with($flash, $action());
        } catch (DomainException $e) {
            return back()->with('active_tab', 'destructions')->with('error', $e->getMessage());
        }
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
