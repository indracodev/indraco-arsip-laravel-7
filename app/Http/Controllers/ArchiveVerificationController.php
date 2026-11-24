<?php

namespace App\Http\Controllers;

use App\Actions\Warehouse\CheckinArchive;
use App\Actions\Warehouse\VerifyArchive;
use App\Models\Archive;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Gudang verification hub and rack check-in on the archive detail page (Laravel 7
 * ArchiveVerificationController@verify / @checkin; PIC Gudang + admin, ArchivePolicy@processWarehouse).
 * `archives/{a}/checkout` is not rebuilt (404): penarikan dispatch is the one exit path
 * (decisions ASK 2-3; 0 checkout log rows in Laravel 7).
 */
class ArchiveVerificationController extends Controller
{
    public function verify(Request $request, Archive $archive, VerifyArchive $action): RedirectResponse
    {
        Gate::authorize('processWarehouse', Archive::class);
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_note' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ]);
        $approve = $data['action'] === 'approve';

        try {
            $message = $action->handle($this->user($request), $archive, $approve, $data['rejection_note'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        $target = $request->input('redirect_to') === 'index' ? route('archives.index') : route('archives.show', $archive);

        return redirect($target)->with($approve ? 'success' : 'warning', $message);
    }

    public function checkin(Request $request, Archive $archive, CheckinArchive $action): RedirectResponse
    {
        Gate::authorize('processWarehouse', Archive::class);
        $data = $request->validate([
            'warehouse_location_id' => ['required', 'integer', 'exists:warehouse_locations,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $message = $action->handle($this->user($request), $archive, (int) $data['warehouse_location_id'], $data['notes'] ?? null);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('archives.show', $archive)->with('success', $message);
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
