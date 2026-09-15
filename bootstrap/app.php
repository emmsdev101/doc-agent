<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'widget.origin' => \App\Http\Middleware\EnsureWidgetOrigin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->header('X-Inertia') || str_contains((string) $response->headers->get('Content-Type'), 'application/json')) {
                return $response;
            }

            $status = $response->getStatusCode();

            if ($status < 400 || $status === 409) {
                return $response;
            }

            $message = $status === 419
                ? 'Your session expired. Refresh the page and try again.'
                : (config('app.debug') ? $e->getMessage() : 'Something went wrong while processing that request.');

            if ($request->method() !== 'GET') {
                return back()->with('error', $message);
            }

            return response()->view('inertia-error', [
                'status' => $status,
                'message' => $message,
                'exception' => config('app.debug') ? $e::class : null,
            ], $status);
        });
    })->create();
