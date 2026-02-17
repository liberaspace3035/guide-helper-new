# CONOHA VPS デプロイガイド

このドキュメントでは、LaravelアプリケーションをCONOHA VPSにデプロイする手順を説明します。

## 目次

1. [前提条件](#前提条件)
2. [サーバー初期セットアップ](#サーバー初期セットアップ)
3. [アプリケーションのデプロイ](#アプリケーションのデプロイ)
4. [データベース設定](#データベース設定)
5. [Webサーバー設定（Nginx）](#webサーバー設定nginx)
6. [SSL証明書設定](#ssl証明書設定)
7. [キューワーカーとスケジューラの設定](#キューワーカーとスケジューラの設定)
8. [環境変数の設定](#環境変数の設定)
9. [セキュリティ設定](#セキュリティ設定)
10. [デプロイ後の確認事項](#デプロイ後の確認事項)
11. [トラブルシューティング](#トラブルシューティング)
12. [更新デプロイ手順（Git連携）](#更新デプロイ手順git連携)
13. [CI/CDを使った自動デプロイ（サーバーログイン情報不要）](#cicdを使った自動デプロイサーバーログイン情報不要)
14. [📋 クライアント側の作業チェックリスト](#-クライアント側の作業チェックリスト)

---

## 前提条件

- CONOHA VPSのインスタンス（推奨: 2GB RAM以上）
- ドメイン（オプション、SSL証明書取得に必要）
- SSHアクセス権限
- ルート権限またはsudo権限
- **Gitリポジトリ（GitHub、GitLabなど）へのアクセス権限**

---

## サーバー初期セットアップ

### 1. システムの更新

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. 必要なソフトウェアのインストール

```bash
# PHP 8.2と必要な拡張機能
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl php8.2-readline

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Node.js 18.x（Viteビルド用）
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Nginx
sudo apt install -y nginx

# PostgreSQL（またはMySQL）
sudo apt install -y postgresql postgresql-contrib
# または
# sudo apt install -y mysql-server

# Git
sudo apt install -y git

# その他
sudo apt install -y unzip zip
```

### 3. ファイアウォール設定

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## アプリケーションのデプロイ

### 1. アプリケーションディレクトリの作成

```bash
# アプリケーション用ユーザー作成（推奨）
sudo adduser --disabled-password --gecos "" laravel
sudo usermod -aG www-data laravel

# アプリケーションディレクトリ作成
sudo mkdir -p /var/www/guide-helper
sudo chown -R laravel:www-data /var/www/guide-helper
```

### 2. Git連携のセットアップ

#### 2.1 SSH鍵の生成と設定

GitHubやGitLabなどのGitリポジトリにアクセスするために、SSH鍵を設定します。

```bash
# アプリケーションユーザーに切り替え
sudo su - laravel

# SSH鍵の生成（既に存在する場合はスキップ）
ssh-keygen -t ed25519 -C "deploy@guide-helper" -f ~/.ssh/id_ed25519 -N ""

# 公開鍵を表示（GitHub/GitLabに登録する）
cat ~/.ssh/id_ed25519.pub
```

**GitHubにSSH鍵を登録する場合：**
1. GitHubにログイン
2. Settings → SSH and GPG keys → New SSH key
3. 上記で表示した公開鍵をコピー＆ペースト

**GitLabにSSH鍵を登録する場合：**
1. GitLabにログイン
2. Preferences → SSH Keys
3. 上記で表示した公開鍵をコピー＆ペースト

#### 2.2 Git設定

```bash
# Gitのユーザー情報を設定
git config --global user.name "Deploy User"
git config --global user.email "deploy@yourdomain.com"

# SSH接続のテスト（GitHubの場合）
ssh -T git@github.com

# SSH接続のテスト（GitLabの場合）
ssh -T git@gitlab.com
```

### 3. 初回デプロイ（Gitリポジトリからクローン）

#### 3.1 既存リポジトリの確認

サーバー上にすでにリポジトリが存在するか確認します：

```bash
# アプリケーションユーザーに切り替え
sudo su - laravel

# リポジトリの存在確認
cd /var/www/guide-helper
if [ -d .git ]; then
    echo "✅ Gitリポジトリが既に存在します"
    git remote -v
    git status
else
    echo "📦 新規にリポジトリをクローンします"
fi
```

#### 3.2 新規クローンの場合

リポジトリが存在しない場合：

```bash
# アプリケーションディレクトリに移動
cd /var/www/guide-helper

# Gitリポジトリからクローン
git clone git@github.com:your-username/your-repository.git .

# またはHTTPSを使用する場合（SSH鍵を設定しない場合）
# git clone https://github.com/your-username/your-repository.git .

# ブランチの確認と切り替え（必要に応じて）
git branch -a
git checkout main  # または production ブランチ
```

#### 3.3 既存リポジトリがある場合

すでにリポジトリが存在する場合、リモートの設定を確認・更新します：

```bash
# アプリケーションディレクトリに移動
cd /var/www/guide-helper

# 現在のリモート設定を確認
git remote -v

# リモートURLを変更する必要がある場合
git remote set-url origin git@github.com:your-username/your-repository.git
# または
# git remote set-url origin https://github.com/your-username/your-repository.git

# リモートの最新情報を取得
git fetch origin

# 現在のブランチを確認
git branch

# 必要に応じてブランチを切り替え
git checkout main  # または production ブランチ

# リモートと同期（ローカルの変更を破棄する場合）
# 注意: このコマンドはローカルの変更を失います
git reset --hard origin/main
```

**既存リポジトリを使用する場合の注意事項：**
- ローカルの変更がある場合は、事前にバックアップを取得してください
- `.env`ファイルなどの環境固有のファイルは、Gitにコミットされていないことを確認してください
- リモートURLが正しく設定されているか確認してください

#### 3.4 リポジトリの状態確認

デプロイ前にリポジトリの状態を確認：

```bash
cd /var/www/guide-helper

# リモートとの差分を確認
git fetch origin
git log HEAD..origin/main --oneline  # 未取得のコミットを確認

# ローカルの変更を確認
git status

# 現在のブランチとコミットを確認
git branch
git log --oneline -5
```

**注意事項：**
- 本番環境では`main`または`production`ブランチを使用することを推奨します
- 開発ブランチを直接デプロイしないでください
- デプロイ前に必ずリポジトリの状態を確認してください

### 4. 依存関係のインストール

```bash
cd /var/www/guide-helper

# Composer依存関係のインストール
composer install --no-dev --optimize-autoloader

# Node.js依存関係のインストールとビルド
npm install
npm run build
```

### 5. ディレクトリ権限の設定

```bash
# storageとbootstrap/cacheに書き込み権限を付与
sudo chown -R laravel:www-data /var/www/guide-helper
sudo chmod -R 775 /var/www/guide-helper/storage
sudo chmod -R 775 /var/www/guide-helper/bootstrap/cache
```

### 6. .gitignoreの確認

`.env`ファイルやその他の機密情報がGitにコミットされないよう、`.gitignore`を確認してください：

```bash
# .gitignoreの確認
cat .gitignore | grep -E "\.env|vendor|node_modules"
```

重要なファイルが`.gitignore`に含まれていることを確認：
- `.env`
- `vendor/`
- `node_modules/`
- `storage/logs/*.log`
- `bootstrap/cache/*.php`

---

## データベース設定

### PostgreSQLの場合

```bash
# PostgreSQLユーザーとデータベース作成
sudo -u postgres psql

# PostgreSQLコンソール内で実行
CREATE DATABASE guide_helper;
CREATE USER guide_user WITH ENCRYPTED PASSWORD 'your-secure-password';
GRANT ALL PRIVILEGES ON DATABASE guide_helper TO guide_user;
\q
```

### MySQLの場合

```bash
sudo mysql -u root -p

# MySQLコンソール内で実行
CREATE DATABASE guide_helper CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'guide_user'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON guide_helper.* TO 'guide_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Webサーバー設定（Nginx）

### 1. Nginx設定ファイルの作成

```bash
sudo nano /etc/nginx/sites-available/guide-helper
```

以下の設定を追加：

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/guide-helper/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静的ファイルのキャッシュ設定
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 2. シンボリックリンクの作成とNginx再起動

```bash
sudo ln -s /etc/nginx/sites-available/guide-helper /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## SSL証明書設定

### Let's Encryptを使用（推奨）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

自動更新の確認：

```bash
sudo certbot renew --dry-run
```

---

## キューワーカーとスケジューラの設定

### 1. systemdサービスファイルの作成

#### キューワーカーサービス

```bash
sudo nano /etc/systemd/system/guide-helper-queue.service
```

```ini
[Unit]
Description=Guide Helper Queue Worker
After=network.target

[Service]
Type=simple
User=laravel
Group=www-data
WorkingDirectory=/var/www/guide-helper
ExecStart=/usr/bin/php /var/www/guide-helper/artisan queue:work \
    --queue=default \
    --tries=3 \
    --timeout=90 \
    --max-time=3600 \
    --sleep=3 \
    --max-jobs=1000
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

#### スケジューラサービス

```bash
sudo nano /etc/systemd/system/guide-helper-scheduler.service
```

```ini
[Unit]
Description=Guide Helper Scheduler
After=network.target

[Service]
Type=simple
User=laravel
Group=www-data
WorkingDirectory=/var/www/guide-helper
ExecStart=/bin/bash -c "while true; do /usr/bin/php /var/www/guide-helper/artisan schedule:run --verbose --no-interaction; sleep 60; done"
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### 2. サービスの有効化と起動

```bash
sudo systemctl daemon-reload
sudo systemctl enable guide-helper-queue
sudo systemctl enable guide-helper-scheduler
sudo systemctl start guide-helper-queue
sudo systemctl start guide-helper-scheduler

# ステータス確認
sudo systemctl status guide-helper-queue
sudo systemctl status guide-helper-scheduler
```

---

## 環境変数の設定

### 1. .envファイルの作成

```bash
cd /var/www/guide-helper
sudo -u laravel cp .env.example .env
sudo -u laravel nano .env
```

### 2. 必須環境変数の設定

```env
# アプリケーション基本設定
APP_NAME="ガイドヘルパーマッチング"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# アプリケーションキー（新規生成）
APP_KEY=

# データベース接続（PostgreSQLの場合）
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=guide_helper
DB_USERNAME=guide_user
DB_PASSWORD=your-secure-password

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120

# キャッシュ設定
CACHE_DRIVER=database
QUEUE_CONNECTION=database

# メール設定（Gmailを使用する場合）
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# ファイルストレージ（S3を使用する場合）
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.ap-northeast-1.amazonaws.com

# 管理者アカウント作成用（初回デプロイ時のみ）
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=your-secure-password
ADMIN_NAME=管理者

# OpenAI API（オプション）
OPENAI_API_KEY=sk-your-openai-api-key-here
```

### 3. アプリケーションキーの生成

```bash
cd /var/www/guide-helper
sudo -u laravel php artisan key:generate
```

### 4. マイグレーションとシーダーの実行

```bash
cd /var/www/guide-helper
sudo -u laravel php artisan migrate --force
sudo -u laravel php artisan db:seed --class=AdminUserSeeder --force
sudo -u laravel php artisan db:seed --class=EmailTemplatesSeeder --force
```

### 5. ストレージリンクの作成

```bash
sudo -u laravel php artisan storage:link
```

### 6. 本番環境の最適化

```bash
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache
```

---

## セキュリティ設定

### 1. .envファイルの権限設定

```bash
sudo chmod 600 /var/www/guide-helper/.env
sudo chown laravel:laravel /var/www/guide-helper/.env
```

### 2. PHP-FPM設定の最適化

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

推奨設定：

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php8.2-fpm.log
```

```bash
sudo systemctl restart php8.2-fpm
```

### 3. 定期的なバックアップ設定

```bash
sudo nano /usr/local/bin/backup-guide-helper.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/guide-helper"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# データベースバックアップ
sudo -u postgres pg_dump guide_helper > $BACKUP_DIR/db_$DATE.sql
# またはMySQLの場合
# mysqldump -u guide_user -p guide_helper > $BACKUP_DIR/db_$DATE.sql

# ファイルのバックアップ（storage/app/publicなど）
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/guide-helper/storage/app/public

# 古いバックアップの削除（30日以上前）
find $BACKUP_DIR -type f -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-guide-helper.sh

# 毎日実行するようにcronに追加
sudo crontab -e
# 以下を追加
0 2 * * * /usr/local/bin/backup-guide-helper.sh
```

---

## デプロイ後の確認事項

### 1. アプリケーションの動作確認

- [ ] ホームページが表示される
- [ ] ログイン・登録が動作する
- [ ] ダッシュボードが表示される
- [ ] HTTPSが正しく動作している
- [ ] 静的ファイル（CSS/JS）が読み込まれている

### 2. データベース接続の確認

```bash
cd /var/www/guide-helper
sudo -u laravel php artisan tinker
# コンソール内で
DB::connection()->getPdo();
```

### 3. キューワーカーとスケジューラの確認

```bash
sudo systemctl status guide-helper-queue
sudo systemctl status guide-helper-scheduler
sudo journalctl -u guide-helper-queue -f
sudo journalctl -u guide-helper-scheduler -f
```

### 4. ログの確認

```bash
tail -f /var/www/guide-helper/storage/logs/laravel.log
```

### 5. メール送信の確認

- テストメールを送信して動作確認

---

## トラブルシューティング

### よくある問題と解決方法

#### 1. 500エラーが発生する

```bash
# ログを確認
tail -f /var/www/guide-helper/storage/logs/laravel.log

# 権限を確認
ls -la /var/www/guide-helper/storage
ls -la /var/www/guide-helper/bootstrap/cache

# 設定キャッシュをクリア
sudo -u laravel php artisan config:clear
sudo -u laravel php artisan cache:clear
```

#### 2. 静的ファイルが読み込まれない

```bash
# Viteビルドを再実行
cd /var/www/guide-helper
npm run build

# ストレージリンクを確認
ls -la /var/www/guide-helper/public/storage
```

#### 3. データベース接続エラー

```bash
# 接続情報を確認
sudo -u laravel php artisan tinker
# DB::connection()->getPdo();

# PostgreSQLの接続確認
sudo -u postgres psql -d guide_helper -U guide_user
```

#### 4. キューワーカーが動作しない

```bash
# ログを確認
sudo journalctl -u guide-helper-queue -n 50

# 手動で実行してエラーを確認
cd /var/www/guide-helper
sudo -u laravel php artisan queue:work --once
```

#### 5. スケジューラが動作しない

```bash
# ログを確認
sudo journalctl -u guide-helper-scheduler -n 50

# 手動で実行
cd /var/www/guide-helper
sudo -u laravel php artisan schedule:run
```

#### 6. Mixed Contentエラー

- `APP_URL`が`https://`で始まっているか確認
- `AppServiceProvider.php`でHTTPSが強制されているか確認（既に実装済み）

#### 7. 権限エラー

```bash
# ディレクトリの所有権と権限を確認・修正
sudo chown -R laravel:www-data /var/www/guide-helper
sudo chmod -R 775 /var/www/guide-helper/storage
sudo chmod -R 775 /var/www/guide-helper/bootstrap/cache
```

#### 8. PHP-FPMエラー

```bash
# PHP-FPMのログを確認
sudo tail -f /var/log/php8.2-fpm.log

# PHP-FPMの再起動
sudo systemctl restart php8.2-fpm
```

#### 9. Git関連のエラー

**SSH接続エラー**

```bash
# SSH接続のテスト
sudo -u laravel ssh -T git@github.com
# または
sudo -u laravel ssh -T git@gitlab.com

# SSH鍵の確認
sudo -u laravel ls -la ~/.ssh/

# SSH鍵の再生成（必要に応じて）
sudo -u laravel ssh-keygen -t ed25519 -C "deploy@guide-helper" -f ~/.ssh/id_ed25519
```

**Git pull時の競合エラー**

```bash
cd /var/www/guide-helper

# ローカルの変更を確認
sudo -u laravel git status

# ローカルの変更を破棄してリモートに合わせる（注意：ローカルの変更は失われます）
sudo -u laravel git fetch origin
sudo -u laravel git reset --hard origin/main

# または、変更を保持してマージ
sudo -u laravel git stash
sudo -u laravel git pull origin main
sudo -u laravel git stash pop
```

**ブランチの切り替えエラー**

```bash
# 現在のブランチを確認
sudo -u laravel git branch

# リモートブランチの一覧を確認
sudo -u laravel git branch -r

# 特定のブランチに切り替え
sudo -u laravel git fetch origin
sudo -u laravel git checkout -b production origin/production
```

**デプロイスクリプトの実行エラー**

```bash
# スクリプトの権限を確認
ls -la /usr/local/bin/deploy-guide-helper.sh

# 実行権限を付与
sudo chmod +x /usr/local/bin/deploy-guide-helper.sh

# 手動で実行してエラーを確認
sudo /usr/local/bin/deploy-guide-helper.sh 2>&1 | tee /tmp/deploy-error.log
```

---

## 更新デプロイ手順（Git連携）

### 基本的なデプロイ手順

コードを更新する場合：

```bash
cd /var/www/guide-helper

# 現在のブランチと状態を確認
sudo -u laravel git status
sudo -u laravel git branch

# リモートの最新情報を取得
sudo -u laravel git fetch origin

# リモートとの差分を確認（デプロイ前に推奨）
sudo -u laravel git log HEAD..origin/main --oneline

# 指定ブランチに切り替え（必要に応じて）
sudo -u laravel git checkout main

# コードを更新
sudo -u laravel git pull origin main

# または、ローカルの変更を破棄してリモートに合わせる場合
# sudo -u laravel git reset --hard origin/main
```

### 既存リポジトリの状態確認

既存リポジトリを使用している場合、デプロイ前に以下を確認：

```bash
cd /var/www/guide-helper

# 1. リモート設定の確認
sudo -u laravel git remote -v

# 2. 現在のブランチとコミットの確認
sudo -u laravel git branch
sudo -u laravel git log --oneline -5

# 3. ローカルの変更の確認
sudo -u laravel git status

# 4. リモートとの差分の確認
sudo -u laravel git fetch origin
sudo -u laravel git log HEAD..origin/main --oneline
```

# 依存関係の更新
sudo -u laravel composer install --no-dev --optimize-autoloader
sudo -u laravel npm install
sudo -u laravel npm run build

# マイグレーション実行
sudo -u laravel php artisan migrate --force

# キャッシュのクリアと再構築
sudo -u laravel php artisan config:clear
sudo -u laravel php artisan route:clear
sudo -u laravel php artisan view:clear
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache

# キューワーカーとスケジューラの再起動
sudo systemctl restart guide-helper-queue
sudo systemctl restart guide-helper-scheduler
```

### デプロイスクリプトの作成

更新作業を自動化するために、デプロイスクリプトを作成します：

```bash
sudo nano /usr/local/bin/deploy-guide-helper.sh
```

```bash
#!/bin/bash
set -e

APP_DIR="/var/www/guide-helper"
BRANCH="${1:-main}"  # デフォルトはmainブランチ

echo "🔄 Starting deployment for branch: $BRANCH"
echo "📁 Working directory: $APP_DIR"

cd $APP_DIR

# 現在の状態を確認
echo "📋 Current status:"
sudo -u laravel git status --short

# 最新のコードを取得
echo "📥 Fetching latest code..."
sudo -u laravel git fetch origin

# ブランチに切り替え
echo "🔀 Checking out branch: $BRANCH"
sudo -u laravel git checkout $BRANCH

# コードを更新
echo "⬇️  Pulling latest changes..."
sudo -u laravel git pull origin $BRANCH

# 変更されたファイルを確認
echo "📝 Changed files:"
sudo -u laravel git diff --name-only HEAD@{1} HEAD

# 依存関係の更新
echo "📦 Installing dependencies..."
sudo -u laravel composer install --no-dev --optimize-autoloader
sudo -u laravel npm install
sudo -u laravel npm run build

# マイグレーション実行
echo "🗄️  Running migrations..."
sudo -u laravel php artisan migrate --force

# キャッシュのクリアと再構築
echo "🧹 Clearing and rebuilding caches..."
sudo -u laravel php artisan config:clear
sudo -u laravel php artisan route:clear
sudo -u laravel php artisan view:clear
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache

# サービスの再起動
echo "🔄 Restarting services..."
sudo systemctl restart guide-helper-queue
sudo systemctl restart guide-helper-scheduler

# デプロイ完了
echo "✅ Deployment completed successfully!"
echo "📅 Deployed at: $(date)"
echo "🌿 Branch: $BRANCH"
echo "📌 Commit: $(sudo -u laravel git rev-parse --short HEAD)"
```

```bash
sudo chmod +x /usr/local/bin/deploy-guide-helper.sh
```

**使用方法：**

```bash
# デフォルト（mainブランチ）をデプロイ
sudo /usr/local/bin/deploy-guide-helper.sh

# 特定のブランチをデプロイ
sudo /usr/local/bin/deploy-guide-helper.sh production
```

### Gitフックを使った自動デプロイ（オプション）

GitHubやGitLabのWebhookを使用して、プッシュ時に自動デプロイを設定することもできます。

#### 1. デプロイ用のエンドポイントスクリプト作成

```bash
sudo nano /var/www/guide-helper/deploy-webhook.php
```

```php
<?php
/**
 * Git Webhook デプロイスクリプト
 * 
 * セキュリティのため、以下の設定を推奨：
 * 1. このファイルへのアクセスをIP制限する
 * 2. シークレットトークンを使用する
 * 3. 特定のブランチのみデプロイを許可する
 */

// シークレットトークン（GitHub/GitLabのWebhook設定と一致させる）
$secret = 'your-secret-token-here';

// デプロイを許可するブランチ
$allowed_branches = ['main', 'production'];

// リクエストの検証
$headers = getallheaders();
$payload = file_get_contents('php://input');
$signature = $headers['X-Hub-Signature-256'] ?? '';

// GitHub/GitLabからのリクエストか確認
if (empty($signature)) {
    http_response_code(403);
    die('Invalid request');
}

// シークレットトークンの検証（GitHubの場合）
$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

// ペイロードの解析
$data = json_decode($payload, true);
$branch = str_replace('refs/heads/', '', $data['ref'] ?? '');

// ブランチの確認
if (!in_array($branch, $allowed_branches)) {
    http_response_code(200);
    die("Branch $branch is not allowed for deployment");
}

// デプロイスクリプトの実行
$output = [];
$return_var = 0;
exec("sudo /usr/local/bin/deploy-guide-helper.sh $branch 2>&1", $output, $return_var);

// 結果のログ記録
file_put_contents(
    '/var/www/guide-helper/storage/logs/deploy.log',
    date('Y-m-d H:i:s') . " - Deployed branch: $branch\n" . implode("\n", $output) . "\n\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'branch' => $branch,
    'output' => $output
]);
```

#### 2. Nginx設定にWebhookエンドポイントを追加

```bash
sudo nano /etc/nginx/sites-available/guide-helper
```

Nginx設定に以下を追加：

```nginx
# Webhookエンドポイント（IP制限を推奨）
location = /deploy-webhook {
    # 特定のIPからのみアクセスを許可（GitHub/GitLabのIP範囲）
    # allow 140.82.112.0/20;  # GitHub
    # allow 172.16.0.0/12;     # GitLab
    # deny all;
    
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /var/www/guide-helper/deploy-webhook.php;
    include fastcgi_params;
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### 3. GitHub/GitLabのWebhook設定

**GitHubの場合：**
1. リポジトリの Settings → Webhooks → Add webhook
2. Payload URL: `https://your-domain.com/deploy-webhook`
3. Content type: `application/json`
4. Secret: スクリプトで設定したシークレットトークン
5. Events: `Just the push event` を選択
6. Active: チェックを入れる

**GitLabの場合：**
1. リポジトリの Settings → Webhooks
2. URL: `https://your-domain.com/deploy-webhook`
3. Secret token: スクリプトで設定したシークレットトークン
4. Trigger: `Push events` を選択

### デプロイ前の確認事項

デプロイ前に以下を確認してください：

```bash
# 1. 現在のブランチとコミットを確認
cd /var/www/guide-helper
sudo -u laravel git log --oneline -5

# 2. ローカルの変更がないか確認
sudo -u laravel git status

# 3. リモートとの差分を確認
sudo -u laravel git fetch origin
sudo -u laravel git log HEAD..origin/main --oneline

# 4. データベースのバックアップ（推奨）
sudo -u postgres pg_dump guide_helper > /var/backups/guide-helper/pre-deploy-$(date +%Y%m%d_%H%M%S).sql
```

### ロールバック手順

問題が発生した場合のロールバック手順：

```bash
cd /var/www/guide-helper

# 1. 前のコミットに戻る
sudo -u laravel git log --oneline -10  # コミット履歴を確認
sudo -u laravel git checkout <previous-commit-hash>

# 2. 依存関係とビルドを再実行
sudo -u laravel composer install --no-dev --optimize-autoloader
sudo -u laravel npm install
sudo -u laravel npm run build

# 3. キャッシュのクリアと再構築
sudo -u laravel php artisan config:clear
sudo -u laravel php artisan route:clear
sudo -u laravel php artisan view:clear
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache

# 4. サービスの再起動
sudo systemctl restart guide-helper-queue
sudo systemctl restart guide-helper-scheduler
```

---

## 重要な注意事項

### 1. ファイルストレージ

本番環境では、`storage/app/public`に保存されたファイルはサーバー再起動時に失われる可能性があります。**必ずS3などの外部ストレージを使用してください。**

### 2. 環境変数の管理

- `.env`ファイルは**絶対にGitにコミットしないでください**
- 本番環境の`.env`は適切な権限（600）で保護してください
- 機密情報は環境変数として管理し、設定ファイルに直接書かないでください

### 3. バックアップ

- データベースの定期バックアップを必ず設定してください
- バックアップファイルは別のサーバーやクラウドストレージに保存することを推奨します
- バックアップの復元テストを定期的に実施してください

### 4. セキュリティ

- 定期的にシステムとパッケージを更新してください
- 不要なポートは開けないでください
- SSH鍵認証を使用し、パスワード認証を無効化することを推奨します
- ファイアウォール（UFW）を適切に設定してください

### 5. ログ管理

- ログファイルが大きくなりすぎないよう、ログローテーションを設定してください
- 本番環境では`APP_DEBUG=false`に設定してください
- エラーログを定期的に確認してください

### 6. リソース監視

- メモリ、CPU、ディスク使用量を定期的に監視してください
- リソース不足の場合は、VPSのプランをアップグレードすることを検討してください

### 7. SSL証明書の更新

Let's Encryptの証明書は90日で期限切れになります。自動更新が設定されていることを確認してください。

### 8. Git連携のベストプラクティス

- **ブランチ管理**: 本番環境には`main`または`production`ブランチのみをデプロイしてください
- **コミット前の確認**: デプロイ前に必ずローカル環境でテストを実行してください
- **デプロイ前のバックアップ**: 重要な変更をデプロイする前にデータベースのバックアップを取得してください
- **SSH鍵の管理**: デプロイ用のSSH鍵は適切に保護し、定期的にローテーションしてください
- **Webhookのセキュリティ**: 自動デプロイを使用する場合、WebhookエンドポイントにIP制限とシークレットトークンを設定してください
- **デプロイログ**: デプロイの履歴をログに記録し、問題発生時に追跡できるようにしてください

---

## 参考リンク

- [CONOHA VPS 公式ドキュメント](https://support.conoha.jp/v/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [systemd Documentation](https://www.freedesktop.org/software/systemd/man/systemd.service.html)

---

## CI/CDを使った自動デプロイ（サーバーログイン情報不要）

クライアントがサーバーログイン情報を共有したくない場合、**CI/CDパイプライン（GitHub ActionsやGitLab CI）を使用した自動デプロイ**が最適です。この方法では、クライアントはGitリポジトリへのアクセス権限のみを提供すればよく、サーバーのログイン情報を共有する必要がありません。

### メリット

- ✅ **セキュリティ**: サーバーログイン情報を共有する必要がない
- ✅ **自動化**: コードをプッシュするだけで自動的にデプロイされる
- ✅ **透明性**: デプロイ履歴とログがGitリポジトリで確認できる
- ✅ **権限管理**: クライアントはGitリポジトリへのアクセス権限のみで管理可能

### 前提条件

- GitHubまたはGitLabのリポジトリ
- サーバー側でデプロイ用のSSH鍵を生成（クライアントは鍵の公開鍵のみを提供）
- サーバーのIPアドレスとSSHポート情報（クライアントが提供）

---

## 📋 クライアント側の作業チェックリスト

CI/CDを使った自動デプロイを設定する場合、クライアントが行う必要がある作業は以下の通りです。

### ✅ 必須作業（初回のみ）

#### 1. サーバーの初期セットアップ

以下の作業をサーバー上で実行してください：

```bash
# 1. サーバーにSSH接続
ssh user@your-server-ip

# 2. システムの更新
sudo apt update && sudo apt upgrade -y

# 3. 必要なソフトウェアのインストール（PHP、Composer、Node.js、Nginx、データベースなど）
# 詳細は「サーバー初期セットアップ」セクションを参照

# 4. アプリケーションユーザーの作成
sudo adduser --disabled-password --gecos "" laravel
sudo usermod -aG www-data laravel

# 5. アプリケーションディレクトリの作成
sudo mkdir -p /var/www/guide-helper
sudo chown -R laravel:www-data /var/www/guide-helper
```

#### 2. データベースのセットアップ

```bash
# PostgreSQLの場合
sudo -u postgres psql
CREATE DATABASE guide_helper;
CREATE USER guide_user WITH ENCRYPTED PASSWORD 'your-secure-password';
GRANT ALL PRIVILEGES ON DATABASE guide_helper TO guide_user;
\q
```

#### 3. デプロイ用SSH鍵の生成

**重要**: この作業で生成した**秘密鍵**の内容を開発者に提供する必要があります。

```bash
# サーバーにSSH接続
ssh user@your-server-ip

# アプリケーションユーザーに切り替え
sudo su - laravel

# デプロイ用のSSH鍵を生成
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy -N ""

# 公開鍵をauthorized_keysに追加（サーバーへのSSH接続用）
cat ~/.ssh/github_actions_deploy.pub >> ~/.ssh/authorized_keys

# 秘密鍵の内容を表示（開発者に提供）
cat ~/.ssh/github_actions_deploy
```

**⚠️ 注意**: 秘密鍵の内容をコピーして、安全な方法で開発者に提供してください。

#### 4. サーバー情報の提供

開発者に以下の情報を提供してください：

- ✅ **サーバーのIPアドレス**（例: `123.45.67.89`）
- ✅ **SSH接続ユーザー名**（例: `laravel`）
- ✅ **SSHポート**（通常は`22`）
- ✅ **アプリケーションディレクトリのパス**（例: `/var/www/guide-helper`）
- ✅ **デプロイ用SSH鍵の秘密鍵**（上記で生成したもの）
- ✅ **GitリポジトリのURL**（例: `git@github.com:your-username/your-repository.git`）

#### 5. Gitリポジトリへのアクセス権限の付与

開発者がGitリポジトリにアクセスできるよう、以下のいずれかの方法で権限を付与してください：

**GitHubの場合:**
- リポジトリの **Settings** → **Collaborators** から開発者を追加
- または、Organizationのメンバーとして追加

**GitLabの場合:**
- プロジェクトの **Settings** → **Members** から開発者を追加
- 適切なロール（Developer以上）を付与

### ✅ オプション作業

#### 6. NginxとSSL証明書の設定（推奨）

```bash
# Nginx設定ファイルの作成
sudo nano /etc/nginx/sites-available/guide-helper
# （設定内容は「Webサーバー設定（Nginx）」セクションを参照）

# SSL証明書の取得（Let's Encrypt）
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

#### 7. キューワーカーとスケジューラの設定（推奨）

```bash
# systemdサービスファイルの作成
# （詳細は「キューワーカーとスケジューラの設定」セクションを参照）

sudo systemctl enable guide-helper-queue
sudo systemctl enable guide-helper-scheduler
sudo systemctl start guide-helper-queue
sudo systemctl start guide-helper-scheduler
```

### 📝 クライアントが提供する情報のまとめ

開発者に提供する必要がある情報をまとめたテンプレート：

```
【サーバー情報】
- IPアドレス: 123.45.67.89
- SSHユーザー: laravel
- SSHポート: 22
- アプリケーションパス: /var/www/guide-helper

【SSH鍵】
（秘密鍵の内容をここに貼り付け）

【Gitリポジトリ】
- URL: git@github.com:your-username/your-repository.git
- ブランチ: main

【データベース情報】（オプション、開発者が設定する場合は不要）
- ホスト: localhost
- ポート: 5432
- データベース名: guide_helper
- ユーザー名: guide_user
- パスワード: （開発者に提供するか、開発者が設定）
```

### ⚠️ 重要な注意事項

1. **秘密鍵の管理**
   - 秘密鍵は絶対にGitリポジトリにコミットしないでください
   - 安全な方法（暗号化されたメッセージ、セキュアなファイル共有サービスなど）で開発者に提供してください

2. **サーバーへのアクセス**
   - 初回セットアップ後は、開発者が直接サーバーにアクセスする必要はありません
   - すべてのデプロイはCI/CDパイプライン経由で自動実行されます

3. **環境変数の設定**
   - `.env`ファイルの設定は開発者が行いますが、データベース接続情報など、クライアントが提供する必要がある情報もあります

4. **初回デプロイ後の確認**
   - 初回デプロイが完了したら、アプリケーションが正常に動作するか確認してください
   - 問題がある場合は、開発者に連絡してください

---

### 開発者側の作業

クライアントが上記の作業を完了したら、開発者は以下を実行します：

1. GitHub Secrets / GitLab CI/CD Variablesの設定
2. CI/CDパイプラインの確認
3. 初回デプロイの実行
4. 動作確認

詳細は各セクションを参照してください。

### 方法1: GitHub Actionsを使用

#### 1. サーバー側の準備（クライアントが実行）

クライアント側でデプロイ用のSSH鍵を生成します：

```bash
# サーバーにSSH接続（初回のみ、クライアントが実行）
ssh user@your-server-ip

# デプロイ用のSSH鍵を生成
sudo -u laravel ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy -N ""

# 公開鍵を表示（GitHub Secretsに登録する）
cat ~/.ssh/github_actions_deploy.pub

# 公開鍵をauthorized_keysに追加
cat ~/.ssh/github_actions_deploy.pub >> ~/.ssh/authorized_keys
```

#### 2. GitHub Secretsの設定（開発者が実行）

1. GitHubリポジトリの **Settings** → **Secrets and variables** → **Actions** に移動
2. 以下のSecretsを追加：

   - `DEPLOY_HOST`: サーバーのIPアドレス（例: `123.45.67.89`）
   - `DEPLOY_USER`: SSH接続ユーザー（例: `laravel`）
   - `DEPLOY_PORT`: SSHポート（例: `22`）
   - `DEPLOY_SSH_KEY`: サーバーで生成した**秘密鍵**の内容（`~/.ssh/github_actions_deploy`の内容）
   - `DEPLOY_PATH`: アプリケーションディレクトリ（例: `/var/www/guide-helper`）
   - `DEPLOY_REPO_URL`: GitリポジトリのURL（例: `git@github.com:your-username/your-repository.git` または `https://github.com/your-username/your-repository.git`）

**重要**: `DEPLOY_REPO_URL`は初回デプロイ時にリポジトリを自動クローンするために必要です。既存リポジトリがある場合は省略可能ですが、設定しておくことを推奨します。

#### 3. GitHub Actionsワークフローの作成

プロジェクトルートに `.github/workflows/deploy.yml` を作成：

```yaml
name: Deploy to CONOHA VPS

on:
  push:
    branches:
      - main  # または production
    tags:
      - 'v*'  # タグが付けられた場合もデプロイ

jobs:
  deploy:
    name: Deploy to Production
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup SSH
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.DEPLOY_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.DEPLOY_PORT }} ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Deploy to server
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          DEPLOY_USER: ${{ secrets.DEPLOY_USER }}
          DEPLOY_PORT: ${{ secrets.DEPLOY_PORT }}
          DEPLOY_PATH: ${{ secrets.DEPLOY_PATH }}
        run: |
          ssh -i ~/.ssh/deploy_key -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST << 'ENDSSH'
            set -e
            cd $DEPLOY_PATH
            
            echo "🔄 Starting deployment..."
            
            # リポジトリの存在確認
            if [ ! -d .git ]; then
              echo "❌ Error: Gitリポジトリが見つかりません"
              echo "初回デプロイの場合は、手動でリポジトリをクローンしてください"
              exit 1
            fi
            
            # リモート設定の確認
            echo "📋 Checking remote configuration..."
            git remote -v
            
            # 最新のコードを取得
            echo "📥 Pulling latest code..."
            git fetch origin
            git checkout main
            git pull origin main
            
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
            echo "📅 Deployed at: $(date)"
            echo "📌 Commit: $(git rev-parse --short HEAD)"
          ENDSSH

      - name: Deployment notification
        if: success()
        run: |
          echo "✅ Deployment completed successfully!"
          echo "Commit: ${{ github.sha }}"
          echo "Branch: ${{ github.ref }}"
```

#### 4. 初回デプロイと既存リポジトリの扱い

**自動クローン機能**: CI/CDパイプラインは、リポジトリが存在しない場合に自動的にクローンします。そのため、クライアントが手動でリポジトリをクローンする必要は**ありません**。

**初回デプロイの流れ:**

1. GitHub Secretsに`DEPLOY_REPO_URL`を設定（必須）
2. `main`ブランチにプッシュ
3. CI/CDパイプラインが自動的に以下を実行：
   - リポジトリが存在しない場合、自動的にクローン
   - 依存関係のインストール
   - ビルドとマイグレーション
   - サービスの再起動

**既存リポジトリがある場合:**

CI/CDパイプラインは自動的に`git pull`を実行します。手動操作は不要です。

**手動でリポジトリをクローンする場合（オプション）:**

クライアントが手動でクローンしたい場合：

```bash
# サーバーにSSH接続
ssh user@your-server-ip

# アプリケーションディレクトリに移動
cd /var/www/guide-helper

# リポジトリをクローン（初回のみ）
sudo -u laravel git clone git@github.com:your-username/your-repository.git .

# または既存のリポジトリがある場合、リモートURLを確認・更新
sudo -u laravel git remote -v
sudo -u laravel git remote set-url origin git@github.com:your-username/your-repository.git
```

この場合、CI/CDパイプラインは既存リポジトリを更新するだけです。

#### 5. デプロイの実行

`main`ブランチにプッシュすると、自動的にデプロイが開始されます：

```bash
git push origin main
```

GitHub Actionsのタブでデプロイの進行状況を確認できます。

### 方法2: GitLab CIを使用

#### 1. サーバー側の準備（方法1と同じ）

**注意**: サーバー上にすでにGitリポジトリが存在する場合、CI/CDパイプラインは自動的に`git pull`を実行します。初回デプロイの場合は、クライアントが手動でリポジトリをクローンする必要があります。

#### 2. GitLab CI/CD Variablesの設定

1. GitLabリポジトリの **Settings** → **CI/CD** → **Variables** に移動
2. 以下のVariablesを追加（**Masked**と**Protected**にチェック）：

   - `DEPLOY_HOST`: サーバーのIPアドレス
   - `DEPLOY_USER`: SSH接続ユーザー
   - `DEPLOY_PORT`: SSHポート
   - `DEPLOY_SSH_KEY`: サーバーで生成した秘密鍵の内容
   - `DEPLOY_PATH`: アプリケーションディレクトリ
   - `DEPLOY_REPO_URL`: GitリポジトリのURL（例: `git@gitlab.com:your-username/your-repository.git`）

**重要**: `DEPLOY_REPO_URL`は初回デプロイ時にリポジトリを自動クローンするために必要です。

#### 3. GitLab CI設定ファイルの作成

プロジェクトルートに `.gitlab-ci.yml` を作成：

```yaml
stages:
  - deploy

variables:
  DEPLOY_PATH: "/var/www/guide-helper"

deploy_production:
  stage: deploy
  image: alpine:latest
  only:
    - main  # または production ブランチ
    - tags  # タグが付けられた場合もデプロイ
  before_script:
    - apk add --no-cache openssh-client git
    - mkdir -p ~/.ssh
    - echo "$DEPLOY_SSH_KEY" > ~/.ssh/deploy_key
    - chmod 600 ~/.ssh/deploy_key
    - ssh-keyscan -p $DEPLOY_PORT $DEPLOY_HOST >> ~/.ssh/known_hosts
  script:
    - |
      ssh -i ~/.ssh/deploy_key -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST << 'ENDSSH'
        set -e
        cd $DEPLOY_PATH
        
        echo "🔄 Starting deployment..."
        
        # リポジトリの存在確認
        if [ ! -d .git ]; then
          echo "❌ Error: Gitリポジトリが見つかりません"
          echo "初回デプロイの場合は、手動でリポジトリをクローンしてください"
          exit 1
        fi
        
        # リモート設定の確認
        echo "📋 Checking remote configuration..."
        git remote -v
        
        # 最新のコードを取得
        echo "📥 Pulling latest code..."
        git fetch origin
        git checkout main
        git pull origin main
        
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
        echo "📅 Deployed at: $(date)"
        echo "📌 Commit: $(git rev-parse --short HEAD)"
      ENDSSH
  after_script:
    - rm -f ~/.ssh/deploy_key
```

### 方法3: デプロイスクリプトをリポジトリに含める（簡易版）

クライアントが手動でデプロイスクリプトを実行する方法です。

**初回デプロイ**: `DEPLOY_REPO_URL`環境変数を設定することで、スクリプトが自動的にリポジトリをクローンします。

**使用方法:**

```bash
# 環境変数を設定
export DEPLOY_HOST="your-server-ip"
export DEPLOY_USER="laravel"
export DEPLOY_PORT="22"
export DEPLOY_PATH="/var/www/guide-helper"
export DEPLOY_REPO_URL="git@github.com:your-username/your-repository.git"  # 初回デプロイに必要

# デプロイスクリプトを実行
./deploy.sh
```

**既存リポジトリがある場合**: `DEPLOY_REPO_URL`は省略可能です。スクリプトは既存リポジトリを更新します。

#### 1. デプロイスクリプトをリポジトリに追加

プロジェクトルートに `deploy.sh` を作成：

```bash
#!/bin/bash
set -e

# サーバー情報（クライアントが提供）
DEPLOY_HOST="${DEPLOY_HOST:-your-server-ip}"
DEPLOY_USER="${DEPLOY_USER:-laravel}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/guide-helper}"

echo "🚀 Deploying to $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH"

# サーバーに接続してデプロイを実行
ssh -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST << 'ENDSSH'
  set -e
  cd $DEPLOY_PATH
  
  echo "🔄 Starting deployment..."
  
  # 最新のコードを取得
  echo "📥 Pulling latest code..."
  git fetch origin
  git checkout main
  git pull origin main
  
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
ENDSSH

echo "✅ Deployment script completed!"
```

#### 2. クライアント側での実行

クライアントは、ローカル環境から以下のコマンドでデプロイを実行：

```bash
# 環境変数を設定
export DEPLOY_HOST="your-server-ip"
export DEPLOY_USER="laravel"
export DEPLOY_PORT="22"
export DEPLOY_PATH="/var/www/guide-helper"

# デプロイスクリプトを実行
chmod +x deploy.sh
./deploy.sh
```

### セキュリティ上の注意事項

1. **SSH鍵の管理**
   - デプロイ用のSSH鍵は専用のものを使用
   - 秘密鍵は絶対にGitにコミットしない
   - 定期的に鍵をローテーション

2. **GitHub Secrets / GitLab Variables**
   - すべての機密情報をMasked/Protectedに設定
   - 必要最小限の権限のみを付与

3. **SSH接続の制限**
   - 特定のIPアドレスからのみSSH接続を許可（可能な場合）
   - デプロイ用ユーザーには必要最小限の権限のみを付与

4. **デプロイの承認**
   - 本番環境へのデプロイは承認フローを設定（GitHub Environments、GitLab Protected Environments）

### 推奨される方法

**GitHub ActionsまたはGitLab CIを使用する方法（方法1または方法2）を強く推奨します。**

理由：
- サーバーログイン情報を共有する必要がない
- デプロイ履歴が自動的に記録される
- デプロイの失敗時に通知を受け取れる
- チーム全体でデプロイ状況を確認できる

---

## サポート

問題が発生した場合は、以下のログを確認してください：

- アプリケーションログ: `/var/www/guide-helper/storage/logs/laravel.log`
- Nginxログ: `/var/log/nginx/error.log`
- PHP-FPMログ: `/var/log/php8.2-fpm.log`
- キューワーカーログ: `sudo journalctl -u guide-helper-queue`
- スケジューラログ: `sudo journalctl -u guide-helper-scheduler`
- **CI/CDログ**: GitHub ActionsまたはGitLab CIのパイプラインログ

