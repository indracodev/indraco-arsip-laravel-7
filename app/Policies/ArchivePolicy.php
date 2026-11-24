<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

/**
 * Read access to archives (IDOR rule, rules/60-security.md): PIC Departemen only for their
 * own department; admin and PIC Gudang for all (Laravel 7 behaviour). Lists use the same
 * rule through Archive::visibleTo(). Write abilities come with the ARSIP module.
 */
class ArchivePolicy
{
    public function view(User $user, Archive $archive): bool
    {
        return $user->role !== User::ROLE_PIC_DEPT
            || ($user->department_id !== null && (int) $archive->department_id === (int) $user->department_id);
    }
}
