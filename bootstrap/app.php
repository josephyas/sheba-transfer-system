<?php

use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure( basePath: dirname( __DIR__ ) )
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware( function ( Middleware $middleware ) {
        $middleware->alias( [
            'api.throttle' => ApiRateLimiter::class,
        ] );

        $middleware->group( 'api', [
            'api.throttle:120,1',
        ] );
    } )
    ->withExceptions( function ( Exceptions $exceptions ) {
        //
    } )->create();
