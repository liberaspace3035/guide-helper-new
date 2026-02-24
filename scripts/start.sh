#!/bin/bash
set -e

# プロジェクトルートに移動（Railway 等で CWD が不定な場合に備える）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/.."
echo "📂 Working directory: $(pwd)"

# Railway でスケジューラ用サービスでは環境変数 RAILWAY_SCHEDULER=1 を設定し、
# railway.json の Start Command は start.sh のままにしておく
if [ "${RAILWAY_SCHEDULER}" = "1" ]; then
    echo "⏰ Scheduler mode: starting schedule loop..."
    exec bash "$SCRIPT_DIR/scheduler.sh"
fi

echo "🚀 Starting Laravel application..."

# キャッシュファイルを物理削除（artisanコマンドが失敗しても確実に消す）
echo "🧹 Force removing cache files..."
echo "📋 Checking for existing cache files:"
ls -la bootstrap/cache/config.php 2>/dev/null && echo "  ⚠️  WARNING: config.php cache file exists!" || echo "  ✅ No config.php cache file"
ls -la bootstrap/cache/services.php 2>/dev/null && echo "  ⚠️  WARNING: services.php cache file exists!" || echo "  ✅ No services.php cache file"
ls -la bootstrap/cache/packages.php 2>/dev/null && echo "  ⚠️  WARNING: packages.php cache file exists!" || echo "  ✅ No packages.php cache file"

rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php
rm -rf bootstrap/cache/*.php

echo "📋 Cache files after removal:"
ls -la bootstrap/cache/*.php 2>/dev/null && echo "  ⚠️  WARNING: Cache files still exist!" || echo "  ✅ All cache files removed"

# 設定キャッシュのクリア（古い設定を削除）
echo "🧹 Clearing configuration cache..."
php artisan config:clear || true
php artisan cache:clear || true

# 環境変数の確認
if [ -z "$APP_KEY" ]; then
    echo "⚠️  APP_KEY is not set. Generating new key..."
    php artisan key:generate --force
fi

# ストレージリンクの作成（存在しない場合）
if [ ! -L public/storage ]; then
    echo "📦 Creating storage link..."
    php artisan storage:link
fi

# データベースマイグレーションの実行（詳細なエラー出力を有効化）
echo "🗄️  Running database migrations..."
echo "📋 Database configuration check:"
echo "  DB_CONNECTION: ${DB_CONNECTION:-not set}"
echo "  DB_HOST: ${DB_HOST:-not set}"
echo "  DB_PORT: ${DB_PORT:-not set}"
echo "  DB_DATABASE: ${DB_DATABASE:-not set}"
echo "  DB_USERNAME: ${DB_USERNAME:-not set}"
echo "  DATABASE_URL: ${DATABASE_URL:-not set (good)}"
echo "  DB_SCHEMA: ${DB_SCHEMA:-not set (good)}"
echo "  SEARCH_PATH: ${SEARCH_PATH:-not set (good)}"

# マイグレーション実行（エラー時は詳細を出力）
if ! php artisan migrate --force -vvv 2>&1; then
    echo "❌ Migration failed. Detailed error information:"
    echo "=========================================="
    echo "Full error output:"
    php artisan migrate --force 2>&1 || true
    echo "=========================================="
    echo "📋 Checking database connection..."
    php -r "
    try {
        \$config = require 'config/database.php';
        echo 'Database config (pgsql):' . PHP_EOL;
        echo '  search_path: ' . var_export(\$config['connections']['pgsql']['search_path'] ?? 'not set', true) . PHP_EOL;
        echo '  schema: ' . var_export(\$config['connections']['pgsql']['schema'] ?? 'not set', true) . PHP_EOL;
        echo '  url: ' . var_export(\$config['connections']['pgsql']['url'] ?? 'not set', true) . PHP_EOL;
        echo '  host: ' . var_export(\$config['connections']['pgsql']['host'] ?? 'not set', true) . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Error reading config: ' . \$e->getMessage() . PHP_EOL;
    }
    " 2>&1 || true
    echo "=========================================="
    exit 1
fi

# 管理者アカウントのシーダー実行（初回のみ、または管理者が存在しない場合のみ）
echo "👤 Running admin user seeder..."
php artisan db:seed --class=AdminUserSeeder --force || {
    echo "⚠️  Admin user seeder failed, but continuing..."
}

# 【一時的】管理者パスワード変更（Railway 等でシェルがない場合用）
# Variables に ADMIN_NEW_PASSWORD を設定してデプロイすると実行される。完了したらこのブロックと ADMIN_NEW_PASSWORD を削除すること。
# if [ -n "${ADMIN_NEW_PASSWORD}" ]; then
#     echo "🔐 Running admin password reset (ADMIN_NEW_PASSWORD is set)..."
#     php artisan admin:reset-password || {
#         echo "⚠️  Admin password reset failed, but continuing..."
#     }
# fi

# メールテンプレートと通知設定のシーダー実行
echo "📧 Running email templates seeder..."
php artisan db:seed --class=EmailTemplatesSeeder --force || {
    echo "⚠️  Email templates seeder failed, but continuing..."
}

# 管理者設定キャッシュをウォーム（初回リクエストの遅延対策）
echo "🔥 Warming admin settings cache..."
php artisan admin:warm-settings-cache || true

# 本番環境での最適化
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache || true   # 失敗しても起動は続行（後で route:clear で再生成する）
    php artisan view:cache
fi

# ルートキャッシュが 404 の原因になることがあるため、起動直前にクリアしてから再生成
echo "🔄 Refreshing route cache..."
php artisan route:clear || true
if [ "$APP_ENV" = "production" ]; then
    php artisan route:cache || true
fi

# アプリケーションの起動（artisan serve は public をドキュメントルートにする）
echo "✅ Starting PHP server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

