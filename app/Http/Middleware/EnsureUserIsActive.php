<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== UserStatus::ACTIVE->value) {
            return response()->json([
                'status' => false,
                'message' => __('messages.user_not_active'),
            ], 403);
        }

        return $next($request);
    }
}
