<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route/group to one or more role slugs, e.g.:
 *   Route::middleware('role:admin,storekeeper')->group(...)
 *
 * Admins always pass, since they have full access to everything by design.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'هذا الحساب غير مفعل'], 403);
        }

        if ($user->hasRole(\App\Models\Role::ADMIN)) {
            return $next($request);
        }

        if (! $user->hasRole(...$roles)) {
            return response()->json(['message' => 'لا تملك صلاحية الوصول إلى هذا القسم'], 403);
        }

        return $next($request);
    }
}
