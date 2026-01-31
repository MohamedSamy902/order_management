<?php

use App\Http\Middleware\ApiVersion;
use App\Http\Middleware\ApiSecretKey;
use App\Http\Middleware\ApiGuardToken;
use Illuminate\Foundation\Application;
use App\Http\Middleware\CheckDeprecatedVersion;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
            'api.version'       => ApiVersion::class,
            'api.secretkey'         => ApiSecretKey::class,
            'api.deprecated'    => CheckDeprecatedVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
