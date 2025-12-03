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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectMiddleware::class

        ]);

        $middleware->redirectGuestsTo(function ($request) {
            // Check which guard is being used based on the URL
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            if ($request->is('teacher/*')) {
                return route('teacher.login');
            }
            if ($request->is('student/*')) {
                return route('student.login');
            }
            
            return route('index'); // default fallback
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
