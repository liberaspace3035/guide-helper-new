#!/bin/bash
set -e

# サーバー情報（環境変数または引数で指定）
DEPLOY_HOST="${DEPLOY_HOST:-your-server-ip}"
DEPLOY_USER="${DEPLOY_USER:-laravel}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/guide-helper}"

# 使用方法の表示
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Environment variables:"
    echo "  DEPLOY_HOST    Server IP address (default: your-server-ip)"
    echo "  DEPLOY_USER    SSH user (default: laravel)"
    echo "  DEPLOY_PORT    SSH port (default: 22)"
    echo "  DEPLOY_PATH    Application path (default: /var/www/guide-helper)"
    echo ""
    echo "Example:"
    echo "  DEPLOY_HOST=123.45.67.89 DEPLOY_USER=laravel ./deploy.sh"
    exit 0
fi

echo "🚀 Deploying to $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH"
echo ""

# サーバーに接続してデプロイを実行
ssh -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST << 'ENDSSH'
  set -e
  cd $DEPLOY_PATH
  
  echo "🔄 Starting deployment..."
  
  # リポジトリの存在確認と初回クローン
  if [ ! -d .git ]; then
    echo "📦 初回デプロイ: Gitリポジトリをクローンします..."
    
    # リポジトリURLが環境変数で指定されているか確認
    if [ -z "$DEPLOY_REPO_URL" ]; then
      echo "❌ Error: DEPLOY_REPO_URL環境変数が設定されていません"
      echo "初回デプロイの場合は、DEPLOY_REPO_URLを設定するか、手動でリポジトリをクローンしてください"
      exit 1
    fi
    
    # ディレクトリが空でない場合は、一時的にバックアップ
    if [ "$(ls -A . 2>/dev/null)" ]; then
      echo "⚠️  ディレクトリにファイルが存在します。一時的にバックアップします..."
      mkdir -p ../backup-$(date +%Y%m%d_%H%M%S)
      mv * ../backup-$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true
      mv .* ../backup-$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true
    fi
    
    # リポジトリをクローン
    git clone $DEPLOY_REPO_URL .
    
    # ブランチを確認
    git checkout main || git checkout master
    
    echo "✅ リポジトリのクローンが完了しました"
  else
    # 既存リポジトリの場合
    echo "📋 既存リポジトリを更新します..."
    
    # リモート設定の確認
    echo "📋 Checking remote configuration..."
    git remote -v
    
    # 最新のコードを取得
    echo "📥 Pulling latest code..."
    git fetch origin
    git checkout main || git checkout master
    git pull origin main || git pull origin master
  fi
  
  # 依存関係の更新
  echo "📦 Installing dependencies..."
  composer install --no-dev --optimize-autoloader
  npm install
  npm run build
  
  # マイグレーション実行
  echo "🗄️  Running migrations..."
  php artisan migrate --force
  
  # キャッシュのクリアと再構築
  echo "🧹 Clearing and rebuilding caches..."
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  
  # サービスの再起動
  echo "🔄 Restarting services..."
  sudo systemctl restart guide-helper-queue
  sudo systemctl restart guide-helper-scheduler
  
  echo "✅ Deployment completed!"
  echo "📅 Deployed at: \$(date)"
  echo "📌 Commit: \$(git rev-parse --short HEAD)"
ENDSSH

echo ""
echo "✅ Deployment script completed!"

