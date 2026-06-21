<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Telegram webhook is called from Telegram servers, no CSRF token possible
        $middleware->validateCsrfTokens(except: [
            'webhooks/telegram',
        ]);

        // Apply locale to every web request
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Named alias so routes/web.php can apply it to specific groups.
        $middleware->alias([
            'approved' => \App\Http\Middleware\RequireApprovedUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
