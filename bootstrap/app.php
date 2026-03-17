<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:     __DIR__ . '/../routes/web.php',
        api:     __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:  '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware for all web requests
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);

        // Alias
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        // Trust proxies (needed behind load balancer / Swoole)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Always return JSON for API requests
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson());

        // Domain exceptions (ApiException subclasses) — delegate to their own render()
        $exceptions->render(function (\App\Exceptions\ApiException $e, Request $request) {
            return $e->render();
        });

        // Validation errors → 422
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'I dati forniti non sono validi.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // Authentication → 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Non autenticato.'], 401);
            }
            return redirect()->guest(route('login'));
        });

        // Authorization → 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Accesso non autorizzato.'], 403);
            }
        });

        // Model not found → 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json(['message' => "{$model} non trovato."], 404);
            }
        });

        // Route not found → 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Risorsa non trovata.'], 404);
            }
        });

        // Method not allowed → 405
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Metodo HTTP non consentito.'], 405);
            }
        });

        // Rate limiting → 429
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Troppe richieste. Riprova tra poco.'], 429);
            }
        });
    })
    ->create();
