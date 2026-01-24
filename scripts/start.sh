#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# 設定キャッシュのクリア（古い設定を削除）
echo "🧹 Clearing configuration cache..."
php artisan config:clear
php artisan cache:clear

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

# データベースマイグレーションの実行
echo "🗄️  Running database migrations..."
php artisan migrate --force

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

