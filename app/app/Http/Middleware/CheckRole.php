<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Usage di route:
     *   Route::get('/admin', ...)->middleware('role:super_admin,manajer');
     *   Route::get('/kasir', ...)->middleware('role:kasir');
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan.'], 403);
        }

        // Super admin selalu diizinkan melewati semua role check
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!empty($roles) && !$user->hasRole(...$roles)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk halaman ini.',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
