<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->registered(function (Application $app): void {
        RateLimiter::for('web-app', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('app.access', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $isHtmlRequest = ! $request->is('api/*') && ! $request->expectsJson();

            if (! $isHtmlRequest || ! in_array($response->getStatusCode(), [403, 404, 419, 429, 500], true)) {
                return $response;
            }

            $page = 'Errors/'.$response->getStatusCode();

            if (! is_file(resource_path("js/Pages/{$page}.vue"))) {
                return $response;
            }

            return Inertia::render($page, ['status' => $response->getStatusCode()])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
