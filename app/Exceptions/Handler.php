<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception with Trace ID.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        if ($this->shouldReport($exception)) {
            $traceId = request()->attributes->get('trace_id') ?? 'ERR-' . date('Ymd-His') . '-' . Str::random(6);
            request()->attributes->set('trace_id', $traceId);

            Log::error("[{$traceId}] " . $exception->getMessage(), [
                'trace_id' => $traceId,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_id' => optional(auth()->user())->id,
                'user_name' => optional(auth()->user())->name,
                'file' => $exception->getFile() . ':' . $exception->getLine(),
            ]);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $traceId = $request->attributes->get('trace_id') ?? 'ERR-' . date('Ymd-His') . '-' . Str::random(6);
        $request->attributes->set('trace_id', $traceId);

        // 1. Session Token Mismatch (419)
        if ($exception instanceof TokenMismatchException) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'SESSION_EXPIRED',
                    'message' => 'Sesi keamanan Anda telah berakhir. Silakan muat ulang halaman atau login kembali.',
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()->route('login')
                ->with('warning', 'Sesi keamanan Anda telah diperbarui atau berakhir. Silakan masukkan kredensial kembali.');
        }

        // 2. Structured JSON API Responses
        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Terdapat kesalahan pada input formulir.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Sesi login tidak valid. Silakan login kembali.',
                    'redirect' => route('login'),
                ], 401);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'FORBIDDEN',
                    'message' => 'Izin ditolak: Anda tidak memiliki wewenang untuk tindakan ini.',
                ], 403);
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'NOT_FOUND',
                    'message' => 'Data arsip atau entitas yang diminta tidak ditemukan di database.',
                ], 404);
            }

            if ($exception instanceof QueryException) {
                $isSqliteLock = Str::contains($exception->getMessage(), ['database is locked', 'busy', 'SQLSTATE[HY000]: General error: 5']);
                return response()->json([
                    'status' => 'error',
                    'trace_id' => $traceId,
                    'code' => $isSqliteLock ? 'DATABASE_BUSY' : 'DATABASE_QUERY_ERROR',
                    'message' => $isSqliteLock 
                        ? 'Database lokal sedang sibuk memproses transaksi workstation lain. Silakan coba kembali dalam beberapa detik.'
                        : 'Terjadi kesalahan eksekusi database.',
                    'detail' => config('app.debug') ? $exception->getMessage() : null,
                ], $isSqliteLock ? 503 : 500);
            }

            $statusCode = 500;
            if ($exception instanceof HttpException) {
                $statusCode = $exception->getStatusCode();
            }

            return response()->json([
                'status' => 'error',
                'trace_id' => $traceId,
                'code' => 'SERVER_ERROR',
                'message' => $exception->getMessage() ?: 'Terjadi kesalahan sistem internal.',
                'detail' => config('app.debug') ? [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => collect($exception->getTrace())->take(5),
                ] : null,
            ], $statusCode);
        }

        // 3. Web Views Error Handling
        if ($exception instanceof QueryException && !config('app.debug')) {
            $isSqliteLock = Str::contains($exception->getMessage(), ['database is locked', 'busy']);
            return response()->view('errors.500', [
                'traceId' => $traceId,
                'title' => $isSqliteLock ? 'Database Sedang Sibuk' : 'Kesalahan Database',
                'message' => $isSqliteLock 
                    ? 'Workstation lain sedang melakukan penulisan database. Harap tunggu sebentar lalu coba kembali.'
                    : 'Terjadi kegagalan komunikasi database lokal.',
                'exception' => $exception,
            ], $isSqliteLock ? 503 : 500);
        }

        return parent::render($request, $exception);
    }
}
