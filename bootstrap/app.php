<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
//USE FOR Rate Limiting MIDDLEWARE
use App\Http\Middleware\RateLimitByRole;
use App\Http\Middleware\RateLimitByIP;
use App\Http\Middleware\BurstLimit;
use App\Http\Middleware\RateLimitByToken;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enable CORS globally
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        //USE FOR Rate Limiting MIDDLEWARE
        $middleware->alias([
            'role.throttle' => RateLimitByRole::class,
            'ip.throttle' => RateLimitByIP::class,
            'burst.throttle' => BurstLimit::class,
            'token.throttle' => RateLimitByToken::class,
            'active.user' => EnsureUserIsActive::class,
            'admin.only' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.validation_failed'),
                'errors' => $e->errors(),
                'code' => 422,
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.unauthenticated'),
                'code' => 401,
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.record_not_found'),
                'code' => 404,
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.endpoint_not_found'),
                'code' => 404,
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.method_not_allowed'),
                'code' => 405,
            ], 405);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : __('messages.invalid'),
                'code' => $e->getStatusCode(),
            ], $e->getStatusCode());
        });

        $exceptions->render(function (\DomainException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : __('messages.invalid'),
                'code' => 422,
            ], 422);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $isLocal = app()->environment('local');
            return response()->json([
                'status' => false,
                'message' => $isLocal ? $e->getMessage() : __('messages.something_went_wrong'),
                'code' => 500,
            ], 500);
        });
    })
    ->create();
