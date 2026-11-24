<?php

namespace App\Http\Controllers;

use App\Actions\Warehouse\ChangeArchiveStatus;
use App\Http\Requests\ArchiveStatusRequest;
use App\Models\Archive;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;

/**
 * "Super Admin: Ubah Status Workflow" (Laravel 7 ArchiveStatusController@superAdminUpdateStatus,
 * PIC Gudang + admin). Form post from the detail page, answered with back() + the Laravel 7 message.
 */
class ArchiveStatusController extends Controller
{
    public function __invoke(ArchiveStatusRequest $request, Archive $archive, ChangeArchiveStatus $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            return back()->with('success', $action->handle($user, $archive, $request->safe()->only(['target_status', 'reason', 'box_number']), $request->uploads()));
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
