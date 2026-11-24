<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        $slowMs = (int) config('app.slow_query_ms');
        if ($slowMs > 0) {
            // Per query, SQL text only: bindings may hold personal data.
            DB::listen(function (QueryExecuted $query) use ($slowMs) {
                if ($query->time >= $slowMs) {
                    Log::warning('Slow query', ['ms' => round($query->time, 1), 'sql' => $query->sql]);
                }
            });
            // Whole request spending too long in the database.
            DB::whenQueryingForLongerThan($slowMs * 4, function (Connection $connection) {
                Log::warning('Request spent long in DB', [
                    'ms' => round($connection->totalQueryDuration(), 1),
                    'path' => request()->path(),
                ]);
            });
        }
    }
}
