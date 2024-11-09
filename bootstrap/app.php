<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Rakutentech\LaravelRequestDocs\LaravelRequestDocsMiddleware::class,
            \Illuminate\Http\Middleware\HandleCors::class
        ]);

        $middleware->alias([
            'admin'=>\App\Http\Middleware\IsAdmin::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
