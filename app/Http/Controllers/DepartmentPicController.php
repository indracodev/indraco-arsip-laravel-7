<?php

namespace App\Http\Controllers;

use App\Actions\Master\AssignDepartmentPic;
use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kelola Departemen > User PIC (Laravel 7 DepartmentController@assignPic / @removePic).
 * Admin only (routes). The page calls these with fetch() (JSON); a plain form gets a redirect.
 * With `user_id`: assign that user; without: create a new PIC account (UserRequest rules).
 */
class DepartmentPicController extends Controller
{
    public function assign(Request $request, Department $department, AssignDepartmentPic $action): JsonResponse|RedirectResponse
    {
        if ($request->filled('user_id')) {
            $userId = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']])['user_id'];

            return $this->respond($request, fn (): string => $action->assign($department, User::findOrFail($userId)));
        }

        /** @var array{name: string, email: string, password: string, phone?: string|null} $data */
        $data = app(UserRequest::class)->validated();

        return $this->respond($request, fn (): string => $action->create($department, $data));
    }

    public function remove(Request $request, Department $department, User $user, AssignDepartmentPic $action): JsonResponse|RedirectResponse
    {
        return $this->respond($request, fn (): string => $action->remove($department, $user));
    }

    /** @param callable(): string $run */
    private function respond(Request $request, callable $run): JsonResponse|RedirectResponse
    {
        try {
            $message = $run();
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['status' => 'success', 'message' => $message])
            : back()->with('success', $message);
    }
}
