<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorizationHeader = $request->header('Authorization');
        $token = null;

        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);
        } elseif ($request->has('token')) {
            $token = $request->query('token');
        }

        if (!$token) {
            return response()->json([
                'message' => 'Unauthorized. Token is missing.'
            ], 401);
        }

        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return response()->json([
                'message' => 'Unauthorized. Token has expired or is invalid.'
            ], 401);
        }

        // Authenticate the user contextually for this request lifecycle
        $role = $payload['role'] ?? 'admin';
        if ($role === 'employee') {
            Auth::shouldUse('employee');
        } else {
            Auth::shouldUse('web');
        }

        Auth::loginUsingId($payload['sub']);

        return $next($request);
    }
}
