<?php

namespace App\Http\Controllers;

use App\Actions\Borrowing\DispatchBorrowing;
use App\Actions\Borrowing\RejectBorrowing;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Services\FileStorage;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Warehouse side of a penarikan (Laravel 7 BorrowingController@dispatch / @reject): PIC Gudang
 * and admin only (BorrowingLogPolicy@process). Transitions and source-status checks live in the
 * actions; both answer with `back()` and the Laravel 7 message, like Laravel 7.
 */
class BorrowingApprovalController extends Controller
{
    public function dispatch(Request $request, BorrowingLog $borrowing, DispatchBorrowing $action): RedirectResponse
    {
        Gate::authorize('process', $borrowing);
        // Laravel 7 accepted the receipt scan as `scan_approval_borrow` (modal) or `approval_file`.
        $request->validate([
            'confirm_file_checked' => ['accepted'],
            'scan_approval_borrow' => FileStorage::rules(),
            'approval_file' => FileStorage::rules(),
        ], ['confirm_file_checked.accepted' => 'Centang konfirmasi bahwa berkas form pengajuan sudah diperiksa sebelum mengeluarkan box.']);
        $receipt = $request->file('scan_approval_borrow') ?? $request->file('approval_file');

        return $this->run(fn () => $action->handle($this->user($request), $borrowing, $receipt));
    }

    public function reject(Request $request, BorrowingLog $borrowing, RejectBorrowing $action): RedirectResponse
    {
        Gate::authorize('process', $borrowing);
        $reason = (string) $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'Alasan Penolakan'])['reason'];

        return $this->run(fn () => $action->handle($this->user($request), $borrowing, $reason));
    }

    /** @param callable(): string $action */
    private function run(callable $action): RedirectResponse
    {
        try {
            return back()->with('success', $action());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
