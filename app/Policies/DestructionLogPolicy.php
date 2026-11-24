<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\DestructionLog;
use App\Models\User;

/**
 * Pemusnahan access. Lists are scoped by Archive::visibleTo().
 *
 * - create (propose): admin and PIC Departemen (Laravel 7 refused PIC Gudang with 403); with an
 *   archive, PIC Departemen only for their own department.
 * - process (approve / reject / complete / upload BAP): PIC Gudang and admin (Laravel 7).
 * - view (BAP print): whoever may view the archive (findings #5: Laravel 7 had no check).
 */
class DestructionLogPolicy
{
    public function create(User $user, ?Archive $archive = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->role === User::ROLE_PIC_DEPT
            && ($archive === null || ($user->department_id !== null && (int) $archive->department_id === (int) $user->department_id));
    }

    public function process(User $user): bool
    {
        return $user->isAdmin() || $user->role === User::ROLE_PIC_GUDANG;
    }

    public function view(User $user, DestructionLog $destruction): bool
    {
        return $destruction->archive !== null && $user->can('view', $destruction->archive);
    }
}
