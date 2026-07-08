<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có quyền truy cập.'], 403);
            }
            return redirect()->route('user.dashboard')->with('error', 'Bạn không có quyền admin.');
        }

        return $next($request);
    }
}
