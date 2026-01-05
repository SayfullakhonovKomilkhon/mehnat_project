<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthenticated'),
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Load role if not loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Admins have all permissions
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.forbidden_permission'),
            'code' => 'FORBIDDEN',
            'required_permissions' => $permissions,
        ], 403);
    }
}

