<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileAuthMiddleware
{
    /**
     * Handle an incoming requebv  st.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force JSON response
        $request->headers->set('Accept', 'application/json');

        // Check if user is authenticated using Sanctum
        if (Auth::guard('sanctum')->check()) {
            // Bind the authenticated user so $request->user() works in controllers
            $user = Auth::guard('sanctum')->user();
            $request->setUserResolver(fn () => $user);
            return $next($request);
        }

        // Return JSON response for unauthorized access
        return response()->json([
            'status' => 'Unauthorized',
            'message' => 'You are not authorized to access this resource.'
        ], 401);
    }
}
