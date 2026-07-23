<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\AssignApiRequestId;
use App\Http\Middleware\AssignRequestContext;
use App\Http\Middleware\EnsureAdminIsActive;
use App\Http\Middleware\EnsureApiClientKey;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::get('/up', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestContext::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'admin.active' => EnsureAdminIsActive::class,
            'api.request-id' => AssignApiRequestId::class,
            'api.client' => EnsureApiClientKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*'));
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (! $request->is('api/*')) {
                return $response;
            }

            $requestId = $request->attributes->get('request_id');
            if (! is_string($requestId)) {
                $requestId = (string) str()->uuid();
                $request->attributes->set('request_id', $requestId);
            }

            $data = $response instanceof JsonResponse ? $response->getData(true) : [];
            if (! is_array($data)) {
                $data = [];
            }
            $data['request_id'] = $requestId;

            $json = response()->json($data, $response->getStatusCode(), $response->headers->all());
            $json->headers->set('X-Request-ID', $requestId);

            return $json;
        });
    })->create();
