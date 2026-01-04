<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            // Revoke all tokens for banned user
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => __('auth.account_deactivated'),
                'code' => 'ACCOUNT_DEACTIVATED',
            ], 403);
        }

        return $next($request);
    }
}



