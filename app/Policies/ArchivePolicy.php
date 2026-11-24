<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

/**
 * Archive access (IDOR rule, rules/60-security.md). Lists use the same read rule through
 * Archive::visibleTo().
 *
 * - view: PIC Departemen only their own department; admin and PIC Gudang all (Laravel 7).
 * - create: admin and PIC Departemen (Laravel 7 refused PIC Gudang with 403).
 * - update: admin any archive (Laravel 7 "Edit Data"); PIC Departemen only a draft of their own
 *   department (Laravel 7 showed Edit only for drafts but did not check it: findings #4/B3).
 * - delete: admin, or PIC Departemen for their own department; "draft only" is checked by the
 *   action with the Laravel 7 message.
 */
class ArchivePolicy
{
    public function view(User $user, Archive $archive): bool
    {
        return $user->role !== User::ROLE_PIC_DEPT || $this->ownsDepartment($user, $archive);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_PIC_DEPT], true);
    }

    public function update(User $user, Archive $archive): bool
    {
        return $user->isAdmin()
            || ($user->role === User::ROLE_PIC_DEPT && $archive->status === 'draft' && $this->ownsDepartment($user, $archive));
    }

    public function delete(User $user, Archive $archive): bool
    {
        return $user->isAdmin() || ($user->role === User::ROLE_PIC_DEPT && $this->ownsDepartment($user, $archive));
    }

    private function ownsDepartment(User $user, Archive $archive): bool
    {
        return $user->department_id !== null && (int) $archive->department_id === (int) $user->department_id;
    }
}
