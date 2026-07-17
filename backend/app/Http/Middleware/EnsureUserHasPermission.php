<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route/group to one or more fine-grained permission slugs, e.g.:
 *   Route::middleware('permission:invoices.create')->group(...)
 *
 * This is the primary authorization mechanism: it guarantees that a Sales
 * user can never hit a Storekeeper/Production/Accountant endpoint (and vice
 * versa) even if the routes are exposed on the same API, because each
 * endpoint requires a permission that only the intended role(s) were granted.
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
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

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'لا تملك الصلاحية اللازمة لتنفيذ هذا الإجراء'], 403);
    }
}
