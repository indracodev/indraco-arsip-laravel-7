<?php

// TEMPORARY load-test routes (Phase 1). Loaded only when BENCH_ROUTES=true.
// Delete this file and its loader in bootstrap/app.php once the benchmark is accepted.

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Catalog-like page: list + counts + lookup lists.
Route::get('read', function () {
    $deptId = DB::table('departments')->where('is_active', 1)->inRandomOrder()->value('id');

    return response()->json([
        'archives' => DB::table('archives as a')
            ->leftJoin('departments as d', 'd.id', '=', 'a.department_id')
            ->leftJoin('sub_departments as s', 's.id', '=', 'a.sub_department_id')
            ->select('a.id', 'a.box_number', 'a.title', 'a.status', 'a.retention_expiry_date', 'd.name as dept', 's.name as sub')
            ->orderByDesc('a.id')->limit(25)->get(),
        'by_status' => DB::table('archives')->select('status', DB::raw('count(*) as n'))->groupBy('status')->get(),
        'masters' => DB::table('master_archives')->where('department_id', $deptId)->where('is_active', 1)
            ->select('id', 'code', 'name', 'retention_years')->orderBy('name')->get(),
        'departments' => DB::table('departments')->select('id', 'code', 'name')->orderBy('name')->get(),
    ]);
});

// Short write: one IMMEDIATE transaction (transaction_mode in config/database.php).
Route::post('write', function () {
    $id = DB::transaction(fn () => DB::table('bench_writes')->insertGetId([
        'payload' => str_repeat('x', 200),
        'created_at' => now(),
    ]));

    return response()->json(['id' => $id]);
});

// Long request (big file stream / import): must not block other users.
Route::get('slow', function () {
    sleep(3);

    return response()->json(['slept' => 3]);
});

// Download handed to Caddy (X-Accel-Redirect): PHP worker is freed at once.
Route::get('file', fn () => response('', 200, [
    'X-Accel-Redirect' => '/bench/sample.bin',
    'Content-Type' => 'application/octet-stream',
]));

// OPcache / JIT state of the worker that answers.
Route::get('opcache', function () {
    $s = opcache_get_status(false) ?: [];

    return response()->json([
        'enabled' => $s['opcache_enabled'] ?? false,
        'file_cache_only' => $s['file_cache_only'] ?? null,
        'cached_scripts' => $s['opcache_statistics']['num_cached_scripts'] ?? null,
        'hit_rate' => $s['opcache_statistics']['opcache_hit_rate'] ?? null,
        'hits' => $s['opcache_statistics']['hits'] ?? null,
        'misses' => $s['opcache_statistics']['misses'] ?? null,
        'used_mb' => round(($s['memory_usage']['used_memory'] ?? 0) / 1048576, 1),
        'jit' => $s['jit'] ?? null,
        'pid' => getmypid(),
    ]);
});

// Scheme/host/IP as Laravel sees them behind Caddy (http :8000 and https :8443).
Route::get('url', fn () => response()->json([
    'url' => url('/x'),
    'asset' => asset('build/app.js'),
    'secure' => request()->secure(),
    'host' => request()->getHttpHost(),
    'ip' => request()->ip(),
]));

// Create the bench table once.
Route::post('setup', function () {
    if (! Schema::hasTable('bench_writes')) {
        Schema::create('bench_writes', function ($t) {
            $t->id();
            $t->string('payload');
            $t->timestamp('created_at');
        });
    }

    return response()->json(['ok' => true]);
});
