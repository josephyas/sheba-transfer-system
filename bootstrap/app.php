<?php

use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\PerformanceMonitoring;
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
            'api.throttle:1200,1',
        ] );

        $middleware->append( PerformanceMonitoring::class );
    } )
    ->withExceptions( function ( Exceptions $exceptions ) {
        //
    } )->create();
