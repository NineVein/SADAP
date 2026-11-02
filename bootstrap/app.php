<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role'   => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (
            Response $response,
            Throwable $e,
            Request $request
        ) {
            $status = $response->getStatusCode();

            // Tampilkan halaman Error Inertia untuk status tertentu
            // hanya di luar environment local dan testing.
            if (
                ! app()->environment(['local', 'testing']) &&
                in_array($status, [403, 404, 419, 500, 503])
            ) {
                return Inertia::render('Error', [
                    'status' => $status,
                ])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            // Tangani sesi yang kedaluwarsa di environment local/testing.
            if ($status === 419) {
                return back()->with(
                    'error',
                    'Sesi berakhir, silakan coba lagi.'
                );
            }

            return $response;
        });
    })
    ->create();