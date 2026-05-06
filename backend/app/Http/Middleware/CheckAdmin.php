<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    /**
     * Kiểm tra người dùng có vai trò quản lý không.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !in_array($user->vai_tro, ['admin', 'quan_ly'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này.'
            ], 403);
        }

        return $next($request);
    }
}
