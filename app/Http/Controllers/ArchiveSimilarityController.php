<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ArchiveSimilarity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/archives/check-similarity (Laravel 7 ArchiveController@apiCheckSimilarity): live title
 * check in the box form. PIC Departemen always checks their own department.
 */
class ArchiveSimilarityController extends Controller
{
    public function __invoke(Request $request, ArchiveSimilarity $similarity): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer'],
            'exclude_archive_id' => ['nullable', 'integer'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $departmentId = $user->role === User::ROLE_PIC_DEPT ? (int) $user->department_id : (int) ($data['department_id'] ?? 0);

        return response()->json($similarity->check((string) ($data['title'] ?? ''), $departmentId, array_filter([(int) ($data['exclude_archive_id'] ?? 0)])));
    }
}
