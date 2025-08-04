<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PatnerAdminMiddleware;
use App\Http\Middleware\PatnerMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\UserPatnerAdminMiddleware;
use App\Http\Middleware\UserPatnerMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'patner' => PatnerMiddleware::class,
            'user' => UserMiddleware::class,
            'user.patner.admin' => UserPatnerAdminMiddleware::class,
            'user.patner' => UserPatnerMiddleware::class,
            'patner.admin' => PatnerAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
