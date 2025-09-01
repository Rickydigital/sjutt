<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force JSON response
        $request->headers->set('Accept', 'application/json');

        // Check if user is authenticated using Sanctum
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Return JSON response for unauthorized access
        return response()->json([
            'status' => 'Unauthorized',
            'message' => 'Invalid OTP data format'
        ], 401);
    }
}
