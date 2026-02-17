<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // APIリクエストの場合は401エラーを返す
        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            \Log::warning('認証エラー: ユーザーが認証されていません', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => session()->getId(),
                'has_session' => $request->hasSession(),
                'cookies' => $request->cookies->all(),
            ]);
            return null; // 401エラーを返す
        }
        
        return route('login');
    }
}






