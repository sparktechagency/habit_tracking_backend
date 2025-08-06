<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PartnerAdminMiddleware;
use App\Http\Middleware\PartnerMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\UserPartnerAdminMiddleware;
use App\Http\Middleware\UserPartnerMiddleware;
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
            'partner' => PartnerMiddleware::class,
            'user' => UserMiddleware::class,
            'user.partner.admin' => UserPartnerAdminMiddleware::class,
            'user.partner' => UserPartnerMiddleware::class,
            'partner.admin' => PartnerAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
