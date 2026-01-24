<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // DB設定が現在どうなっているかログに出力（デバッグ用）
        try {
            $dbConfig = config('database.connections.pgsql', []);
            \Log::info('DB Config Type Check:', [
                'search_path_type' => gettype($dbConfig['search_path'] ?? 'not set'),
                'search_path_value' => $dbConfig['search_path'] ?? 'not set',
                'search_path_is_array' => is_array($dbConfig['search_path'] ?? null),
                'prefix_type' => gettype($dbConfig['prefix'] ?? 'not set'),
                'prefix_value' => $dbConfig['prefix'] ?? 'not set',
                'prefix_is_array' => is_array($dbConfig['prefix'] ?? null),
                'url_exists' => isset($dbConfig['url']),
                'url_value' => $dbConfig['url'] ?? 'not set',
            ]);

            // もし配列なら、どこで書き換わったかスタックトレースを出す
            if (is_array($dbConfig['search_path'] ?? null)) {
                \Log::error('CRITICAL: search_path is an ARRAY!', [
                    'value' => $dbConfig['search_path'],
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);
            }

            if (is_array($dbConfig['prefix'] ?? null)) {
                \Log::error('CRITICAL: prefix is an ARRAY!', [
                    'value' => $dbConfig['prefix'],
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);
            }

            // 環境変数の確認
            \Log::info('Environment Variables Check:', [
                'DB_SCHEMA' => env('DB_SCHEMA', 'not set'),
                'DB_SCHEMA_type' => gettype(env('DB_SCHEMA')),
                'SEARCH_PATH' => env('SEARCH_PATH', 'not set'),
                'SEARCH_PATH_type' => gettype(env('SEARCH_PATH')),
                'DATABASE_URL' => env('DATABASE_URL') ? 'set (should be removed)' : 'not set (good)',
                'DB_PREFIX' => env('DB_PREFIX', 'not set'),
                'DB_PREFIX_type' => gettype(env('DB_PREFIX')),
                'PGSCHEMA' => $_ENV['PGSCHEMA'] ?? $_SERVER['PGSCHEMA'] ?? 'not set',
                'PGOPTIONS' => $_ENV['PGOPTIONS'] ?? $_SERVER['PGOPTIONS'] ?? 'not set',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking DB config', ['exception' => $e->getMessage()]);
        }

        // 本番環境でHTTPSを強制（Mixed Content対策）
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // 本番環境でのセッション設定（HTTPS環境用）
        if (config('app.env') === 'production') {
            // HTTPS環境ではセキュアCookieを有効化
            config(['session.secure' => true]);
            // SameSite属性をnoneに設定（クロスオリジンリクエスト対応）
            // ただし、SameSite=noneの場合はsecure=trueが必須
            config(['session.same_site' => 'none']);
        }
    }
}

