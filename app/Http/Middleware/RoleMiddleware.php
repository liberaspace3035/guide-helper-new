<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // セッション認証を確認（APIルートも含む）
        if (!auth()->check()) {
            // AJAXリクエストやAPIリクエストの場合はJSONを返す
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                \Log::warning('認証エラー: セッションが無効です', [
                    'url' => $request->fullUrl(),
                    'session_id' => session()->getId()
                ]);
                return response()->json(['error' => '認証が必要です'], 401);
            }
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        
        if (!in_array($user->role, $roles)) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'この操作を実行する権限がありません'], 403);
            }
            abort(403, 'この操作を実行する権限がありません');
        }

        return $next($request);
    }
}

