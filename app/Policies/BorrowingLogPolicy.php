<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;

/**
 * Penarikan (withdrawal) access. The list itself is scoped by Archive::visibleTo().
 *
 * - create: admin and PIC Departemen (Laravel 7 refused PIC Gudang with 403); with an archive,
 *   PIC Departemen only for their own department.
 * - process (dispatch / reject): PIC Gudang and admin, the only roles with those buttons in
 *   Laravel 7 (its reject route also let PIC Departemen in: findings #14).
 * - dept-approve, approve, update, return: not built (decisions ASK 2-4, triage: no UI caller).
 */
class BorrowingLogPolicy
{
    public function create(User $user, ?Archive $archive = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->role === User::ROLE_PIC_DEPT
            && ($archive === null || ($user->department_id !== null && (int) $archive->department_id === (int) $user->department_id));
    }

    public function process(User $user, BorrowingLog $borrowing): bool
    {
        return $user->isAdmin() || $user->role === User::ROLE_PIC_GUDANG;
    }
}
