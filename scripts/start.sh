#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# キャッシュファイルを物理削除（artisanコマンドが失敗しても確実に消す）
echo "🧹 Force removing cache files..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php
rm -rf bootstrap/cache/*.php

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

# 本番環境での最適化
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# アプリケーションの起動
echo "✅ Starting PHP server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

