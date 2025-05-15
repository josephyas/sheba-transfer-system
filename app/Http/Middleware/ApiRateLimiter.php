<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    protected RateLimiter $limiter;

    public function __construct( RateLimiter $limiter )
    {
        $this->limiter = $limiter;
    }

    public function handle( Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1 ): Response
    {
        $key = $this->resolveRequestSignature( $request );

        if ( $this->limiter->tooManyAttempts( $key, $maxAttempts ) ) {
            return response()->json( [
                'message' => 'Too many requests. Please try again later.',
                'code'    => 'RATE_LIMIT_EXCEEDED'
            ], 429 );
        }

        $this->limiter->hit( $key, $decayMinutes * 60 );

        $response = $next( $request );

        return $this->addRateLimitHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts( $key, $maxAttempts )
        );
    }

    protected function resolveRequestSignature( Request $request ): string
    {
        return Str::lower( $request->ip() );
    }

    protected function calculateRemainingAttempts( string $key, int $maxAttempts ): int
    {
        return $maxAttempts - $this->limiter->attempts( $key ) + 1;
    }

    protected function addRateLimitHeaders( Response $response, int $maxAttempts, int $remainingAttempts ): Response
    {
        return $response->withHeaders( [
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ] );
    }
}
