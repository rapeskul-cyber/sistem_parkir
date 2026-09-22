<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Autentikasi diperlukan.'
            ], 401);
        }

        $user = Auth::user();
        $normalizedUserRole = $this->normalizeRole($user->role);
        $normalizedAllowedRoles = array_map(fn ($role) => $this->normalizeRole($role), $roles);

        if (empty($roles) || !in_array($normalizedUserRole, $normalizedAllowedRoles, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Role Anda tidak memiliki izin untuk halaman ini.'
            ], 403);
        }

        return $next($request);
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) $role ?? ''));
        $normalized = str_replace([' ', '-', '/'], '_', $normalized);

        return match ($normalized) {
            'superadmin', 'super_admin' => 'super_admin',
            default => $normalized,
        };
    }
}
