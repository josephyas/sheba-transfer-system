<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle( Request $request, Closure $next ): Response
    {
        // Start measuring
        $startTime = microtime( true );
        $startMemory = memory_get_usage();

        // Process request
        $response = $next( $request );

        // Calculate metrics
        $duration = microtime( true ) - $startTime;
        $memory = memory_get_usage() - $startMemory;

        // Add performance headers to response
        $response->headers->add( [
            'X-Request-Duration' => round( $duration * 1000, 2 ) . 'ms',
            'X-Memory-Usage'     => round( $memory / 1024 / 1024, 2 ) . 'MB'
        ] );

        // Log if request is slow (over 500ms)
        if ( $duration > 0.5 ) {
            Log::warning( 'Slow request detected', [
                'path'     => $request->path(),
                'method'   => $request->method(),
                'duration' => round( $duration * 1000, 2 ) . 'ms',
                'memory'   => round( $memory / 1024 / 1024, 2 ) . 'MB'
            ] );
        }

        if ( str_starts_with( $request->path(), 'api/' ) ) {
            Log::info( 'API request metrics', [
                'path'     => $request->path(),
                'method'   => $request->method(),
                'duration' => round( $duration * 1000, 2 ) . 'ms',
                'status'   => $response->getStatusCode(),
                'memory'   => round( $memory / 1024 / 1024, 2 ) . 'MB'
            ] );
        }

        return $response;
    }
}
