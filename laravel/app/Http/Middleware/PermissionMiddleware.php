<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;

/*
|--------------------------------------------------------------------------
| PermissionMiddleware
|--------------------------------------------------------------------------
| Enforces granular action-level permissions defined in the role_permissions
| table.  Register in app/Http/Kernel.php → $routeMiddleware:
|
|   'permission' => \App\Http\Middleware\PermissionMiddleware::class,
|
| Usage in routes/web.php or routes/api.php:
|
|   Route::get('/admin/users', [AdminController::class, 'index'])
|       ->middleware(['auth', 'permission:user:list_all']);
|
|   Route::post('/consultations/{id}/prescribe', ...)
|       ->middleware(['auth', 'permission:consultation:write_prescription']);
|
| CEO always passes every permission check automatically.
| Inactive accounts are logged out and redirected to login.
|--------------------------------------------------------------------------
*/

class PermissionMiddleware
{
    // Cache permissions per role for 60 minutes to avoid repeated DB queries.
    private const CACHE_TTL = 3600;

    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Deactivated accounts are ejected immediately.
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        if ($this->roleHasPermission($user->role, $permission)) {
            return $next($request);
        }

        $this->writeAuditLog($user->id, $permission, 'denied', $request->ip());

        return $this->denyAccess($request, $permission);
    }

    // ── Permission resolution ─────────────────────────────────────────────────

    private function roleHasPermission(string $role, string $permission): bool
    {
        // CEO bypasses every permission check.
        if ($role === 'ceo') {
            return true;
        }

        $granted = $this->getPermissionsForRole($role);

        return in_array($permission, $granted, true);
    }

    private function getPermissionsForRole(string $role): array
    {
        $cacheKey = "msas:permissions:{$role}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($role) {
            return DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $role)
                ->pluck('permissions.name')
                ->toArray();
        });
    }

    // ── Response helpers ──────────────────────────────────────────────────────

    private function redirectToLogin(Request $request): mixed
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        return redirect()->guest(route('login'));
    }

    private function denyAccess(Request $request, string $permission): mixed
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'      => 'Forbidden.',
                'permission' => $permission,
                'message'    => 'You do not have permission to perform this action.',
            ], 403);
        }
        abort(403, "Permission denied: {$permission}. Contact your administrator if you believe this is an error.");
    }

    // ── Audit trail ───────────────────────────────────────────────────────────

    private function writeAuditLog(int $userId, string $permission, string $result, ?string $ip): void
    {
        try {
            // Only log denied attempts to avoid bloat on every request.
            AuditLog::create([
                'user_id'    => $userId,
                'action'     => "permission_check:{$permission}",
                'model'      => 'Permission',
                'details'    => json_encode(['result' => $result]),
                'ip_address' => $ip,
            ]);
        } catch (\Throwable) {
            // Non-fatal: a broken audit log must not block the user.
        }
    }

    // ── Cache invalidation helper (call after role change) ────────────────────

    public static function clearRoleCache(string $role): void
    {
        Cache::forget("msas:permissions:{$role}");
    }
}
