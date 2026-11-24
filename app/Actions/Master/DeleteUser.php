<?php

namespace App\Actions\Master;

use App\Models\User;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Hapus User (Laravel 7 UserController@destroy). Not one's own account (Laravel 7). New guard:
 * the schema deletes a user's boxes, penarikan, pemusnahan, perpanjangan and warehouse entries
 * with the user (ON DELETE CASCADE), so a user with such history is refused instead of silently
 * deleting archive data.
 */
class DeleteUser
{
    /** Tables whose rows are deleted together with the user (baseline schema). */
    private const CASCADES = [
        'archives' => 'created_by_user_id',
        'borrowing_logs' => 'borrower_user_id',
        'destruction_logs' => 'proposed_by_user_id',
        'archive_extension_logs' => 'proposed_by_user_id',
        'warehouse_entry_logs' => 'pic_gudang_id',
    ];

    /** @return string success message */
    public function handle(User $actor, User $user): string
    {
        if ($actor->id === $user->id) {
            throw new DomainException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $email = $user->email;
        $role = $user->roleLabel();

        DB::transaction(function () use ($user, $name): void {
            foreach (self::CASCADES as $table => $column) {
                if (DB::table($table)->where($column, $user->id)->exists()) {
                    throw new DomainException("Pengguna {$name} tidak dapat dihapus karena memiliki data arsip / riwayat transaksi terkait. Ubah perannya atau reset password-nya saja.");
                }
            }
            $user->delete();
        });

        ActivityLogger::log('USER_DELETE', "Menghapus akun pengguna '{$name}' ({$role}, Email: {$email}).", 'USER_MANAGEMENT',
            ['deleted_user_email' => $email, 'deleted_user_name' => $name], $name, $actor);

        return "Pengguna {$name} berhasil dihapus.";
    }
}
