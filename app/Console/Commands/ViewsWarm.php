<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Precompiles every application view under the path the view finder uses at runtime.
 *
 * On Windows `view:cache` compiles `resources\views\x\y.blade.php` (backslashes) while a
 * request resolves `resources\views/x/y.blade.php`; the compiled file name is a hash of the
 * path, so every view was compiled again on its first request (finding R14: 2-4 s per page
 * after an update). Runs with `php artisan optimize`.
 */
#[Signature('views:warm')]
#[Description('Kompilasi semua view Blade dengan path yang sama seperti saat request')]
class ViewsWarm extends Command
{
    public function handle(): int
    {
        /** @var BladeCompiler $compiler */
        $compiler = app('blade.compiler');
        $finder = view()->getFinder();
        $count = 0;
        foreach ((array) config('view.paths') as $root) {
            foreach (File::allFiles($root) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }
                $name = str_replace(['\\', '/'], '.', substr($file->getRelativePathname(), 0, -strlen('.blade.php')));
                $compiler->compile($finder->find($name));
                $count++;
            }
        }
        $this->components->info("View dikompilasi: {$count}.");

        return self::SUCCESS;
    }
}
