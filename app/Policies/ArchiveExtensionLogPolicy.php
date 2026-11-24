<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

/**
 * Perpanjangan masa simpan access: create = same rule as a destruction proposal (admin + PIC
 * Departemen of the box's department; PIC Gudang 403 like Laravel 7); process (ACC / Tolak) =
 * PIC Gudang and admin. The printed extension form uses ArchivePolicy@view (findings #5).
 */
class ArchiveExtensionLogPolicy
{
    public function create(User $user, ?Archive $archive = null): bool
    {
        return (new DestructionLogPolicy)->create($user, $archive);
    }

    public function process(User $user): bool
    {
        return (new DestructionLogPolicy)->process($user);
    }
}
