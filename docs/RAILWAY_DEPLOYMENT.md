# Railway デプロイガイド

このドキュメントでは、LaravelアプリケーションをRailwayにデプロイする手順を説明します。

## 目次

1. [前提条件](#前提条件)
2. [環境変数の設定](#環境変数の設定)
3. [データベースのセットアップ](#データベースのセットアップ)
4. [サービスの構成](#サービスの構成)
5. [静的ファイルの永続化](#静的ファイルの永続化)
6. [デプロイ後の確認事項](#デプロイ後の確認事項)

## 前提条件

- Railwayアカウント
- GitHubリポジトリ（またはGitリポジトリ）
- PostgreSQLデータベース（Railwayでプロビジョニング）

## 環境変数の設定

Railwayのダッシュボードで、プロジェクトの「Variables」タブから以下の環境変数を設定してください。

### 必須環境変数

```bash
# アプリケーション基本設定
APP_NAME="ガイドヘルパーマッチング"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app

# アプリケーションキー（本番用に新規生成）
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# データベース接続（Railwayが自動提供するDATABASE_URLから設定）
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

# または、Railwayが提供するDATABASE_URLを直接使用
# DATABASE_URL=postgresql://user:password@host:port/database
```

### 推奨環境変数

```bash
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
```

### APP_KEYの生成方法

ローカルの`.env`からコピーせず、本番環境用に新しく生成してください。

Railwayのコンソールで以下のコマンドを実行するか、ローカルで生成した値を設定します：

```bash
php artisan key:generate --show
```

生成されたキーを`APP_KEY`環境変数に設定してください。

## データベースのセットアップ

### 1. PostgreSQLのプロビジョニング

1. Railwayダッシュボードで「New」→「Database」→「PostgreSQL」を選択
2. データベースが作成されたら、「Variables」タブで接続情報を確認
3. データベースサービスをメインアプリケーションサービスに「Private Networking」で接続

### 2. データベース接続の確認

Railwayは`DATABASE_URL`環境変数を自動的に提供しますが、Laravelが認識できるよう、以下の環境変数を設定してください：

```bash
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}
```

または、`config/database.php`で`DATABASE_URL`を直接使用するように設定することもできます。

### 3. マイグレーションの実行

`scripts/start.sh`が自動的にマイグレーションを実行しますが、手動で実行する場合は：

```bash
php artisan migrate --force
```

## サービスの構成

Railwayでは、メインアプリケーション、キューワーカー、スケジューラを別々のサービスとして起動する必要があります。

### 1. メインアプリケーションサービス

- **Start Command**: `bash scripts/start.sh`
- **Port**: Railwayが自動的に`PORT`環境変数を提供

このサービスは以下を自動実行します：
- データベースマイグレーション
- ストレージリンクの作成
- 本番環境での最適化（設定・ルート・ビューのキャッシュ）
- PHPサーバーの起動

### 2. キューワーカーサービス（オプション）

バックグラウンドジョブ（メール送信など）を使用する場合：

1. メインサービスを「Duplicate」して新しいサービスを作成
2. **Start Command**を`bash scripts/queue-worker.sh`に変更
3. 同じ環境変数とデータベース接続を使用

### 3. スケジューラサービス（オプション）

定期実行タスク（利用時間の自動計算など）を使用する場合：

1. メインサービスを「Duplicate」して新しいサービスを作成
2. **Start Command**を`bash scripts/scheduler.sh`に変更
3. 同じ環境変数とデータベース接続を使用

## 静的ファイルの永続化

Railwayのファイルシステムはエフェメラル（一時的）です。再起動やデプロイのたびに、`storage/app/public`に保存したファイルは消去されます。

### 解決策：Amazon S3を使用

1. AWS S3バケットを作成
2. IAMユーザーを作成し、S3へのアクセス権限を付与
3. 以下の環境変数を設定：

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.ap-northeast-1.amazonaws.com
```

### 注意事項

- ユーザーのプロフィール画像
- 報告書の添付ファイル
- その他のアップロードファイル

これらは必ず外部ストレージ（S3など）に保存するように設定してください。

## Viteビルドプロセス

Railway（Nixpacks）は`package.json`を検知して自動的に`npm install && npm run build`を実行します。

### ビルドの確認

- `public/build`ディレクトリにビルドされたアセットが正しく配置されているか確認
- `public/build/manifest.json`が存在するか確認
- ブラウザの開発者ツールで、CSS/JSファイルが正しく読み込まれているか確認

### トラブルシューティング

ビルドが失敗する場合：

1. `package.json`の`build`スクリプトが正しく設定されているか確認
2. `vite.config.js`の設定を確認
3. Railwayのビルドログを確認してエラーを特定

## デプロイ後の確認事項

### 1. HTTPSの確認

- `AppServiceProvider.php`で本番環境でHTTPSが強制されていることを確認
- ブラウザでアプリケーションにアクセスし、Mixed Contentエラーが発生していないか確認

### 2. データベース接続の確認

- アプリケーションが正常にデータベースに接続できているか確認
- マイグレーションが正常に実行されているか確認

### 3. ストレージの確認

- ファイルアップロード機能が正常に動作するか確認
- S3を使用している場合、ファイルが正しくアップロードされているか確認

### 4. メール送信の確認

- メール送信機能が正常に動作するか確認
- Gmailのアプリパスワードが正しく設定されているか確認

### 5. キューワーカーとスケジューラの確認

- キューワーカーサービスが正常に起動しているか確認
- スケジューラサービスが正常に起動しているか確認
- ログを確認してエラーが発生していないか確認

## トラブルシューティング

### マイグレーションエラー

- データベース接続情報が正しいか確認
- `--force`フラグが使用されているか確認（本番環境では必須）

### Mixed Contentエラー

- `AppServiceProvider.php`でHTTPSが強制されているか確認
- `APP_URL`が`https://`で始まっているか確認

### ファイルが保存されない

- `FILESYSTEM_DISK`が`s3`に設定されているか確認
- S3の認証情報が正しいか確認
- S3バケットの権限設定を確認

### キューワーカーが動作しない

- キューワーカーサービスが起動しているか確認
- `QUEUE_CONNECTION`が`database`に設定されているか確認
- ログを確認してエラーを特定

## 参考リンク

- [Railway Documentation](https://docs.railway.app/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Scheduler Documentation](https://laravel.com/docs/scheduling)

