<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireIdempotencyKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('Idempotency-Key')) {
            return response()->json([
                    'success' => false,
                    'error' => 'Idempotency-Key header is required',
                    'message' => 'Please generate a unique key and include it in the Idempotency-Key header',
                ], 400);
        }
        return $next($request);
    }
}
