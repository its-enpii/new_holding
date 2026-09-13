<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\Factory as ViewFactory;
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
            $statusCode = $response->getStatusCode();
            $handledErrorStatuses = [401, 403, 404, 419, 429, 500, 503];

            if (! $isHtmlRequest || ! in_array($statusCode, $handledErrorStatuses, true)) {
                return $response;
            }

            $bladeView = "errors.{$statusCode}";
            $page = "Errors/{$statusCode}";

            if ($request->header('X-Inertia') === 'false') {
                $viewFactory = app(ViewFactory::class);

                if ($viewFactory->exists($bladeView)) {
                    return response()->view($bladeView, [], $statusCode);
                }
            }

            if (! is_file(resource_path("js/Pages/{$page}.vue"))) {
                return $response;
            }

            try {
                return Inertia::render($page, ['status' => $statusCode])
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            } catch (Throwable $renderException) {
                report($renderException);

                $viewFactory = app(ViewFactory::class);

                if ($viewFactory->exists($bladeView)) {
                    return response()->view($bladeView, [], $statusCode);
                }

                return $response;
            }
        });
    })->create();
