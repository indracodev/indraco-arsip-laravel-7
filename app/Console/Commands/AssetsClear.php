<?php

namespace App\Console\Commands;

use App\Support\Asset;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Removes bootstrap/cache/assets.php (runs with `php artisan optimize:clear`). */
#[Signature('assets:clear')]
#[Description('Hapus cache hash versi aset statis')]
class AssetsClear extends Command
{
    public function handle(): int
    {
        @unlink(Asset::manifestPath());
        Asset::flush();
        $this->components->info('Cache versi aset dihapus.');

        return self::SUCCESS;
    }
}
