<?php

namespace Tests\Feature\Shell;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\BladeCompiler;
use Tests\TestCase;

/**
 * Finding R14: views must be precompiled under the exact path a request uses, otherwise the
 * first request of every page compiles them again (view:cache uses other paths on Windows).
 */
class ViewsWarmTest extends TestCase
{
    public function test_views_warm_precompiles_the_paths_requests_use(): void
    {
        /** @var BladeCompiler $compiler */
        $compiler = app('blade.compiler');
        $views = ['auth.login', 'layouts.app', 'components.button', 'archives.partials.catalog-row'];
        foreach ($views as $name) {
            File::delete($compiler->getCompiledPath(view()->getFinder()->find($name)));
        }

        Artisan::call('views:warm');

        foreach ($views as $name) {
            $this->assertFileExists($compiler->getCompiledPath(view()->getFinder()->find($name)), $name);
        }
    }
}
