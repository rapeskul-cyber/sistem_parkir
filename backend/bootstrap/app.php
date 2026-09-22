<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Paksa agar setiap error/exception di API tetap membawa header CORS ke browser
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                
                return response()->json([
                    'message' => $e->getMessage() ?: 'Terjadi kesalahan pada server.',
                ], $status)->header('Access-Control-Allow-Origin', '*')
                          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }
        });
    })->create();