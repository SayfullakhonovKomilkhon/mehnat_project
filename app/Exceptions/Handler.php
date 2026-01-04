<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'two_factor_secret',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle API exceptions
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Handle API exceptions.
     */
    protected function handleApiException(Throwable $e, Request $request)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthenticated'),
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => __('auth.forbidden'),
                'code' => 'FORBIDDEN',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => __('messages.not_found'),
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'HTTP Error',
                'code' => 'HTTP_ERROR',
            ], $e->getStatusCode());
        }

        // Log the exception
        report($e);

        // In production, don't expose internal errors
        if (app()->isProduction()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.server_error'),
                'code' => 'SERVER_ERROR',
            ], 500);
        }

        // In development, show more details
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'code' => 'SERVER_ERROR',
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->take(5)->toArray(),
        ], 500);
    }
}



