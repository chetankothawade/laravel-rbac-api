<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (! in_array($role, [UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value], true)) {
            return response()->json([
                'status' => false,
                'message' => __('messages.forbidden'),
            ], 403);
        }

        return $next($request);
    }
}
