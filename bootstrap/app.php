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
        // Alias utilisables dans routes/web.php.
        $middleware->alias([
            // role:role1,role2 - controle d'acces base sur les roles (RBAC).
            'role' => \App\Http\Middleware\CheckRole::class,
            // password.current - impose le renouvellement d'un mot de passe
            // temporaire ou expire avant tout autre acces.
            'password.current' => \App\Http\Middleware\EnsurePasswordIsCurrent::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
