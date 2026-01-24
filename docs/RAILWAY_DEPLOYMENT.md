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

### APP_URLの確認方法

**重要**: `APP_URL`は、Railwayが自動生成するアプリケーションのURLです。以下の手順で確認できます：

1. **Railwayダッシュボードで確認（最も簡単）**
   - Railwayダッシュボードにログイン
   - プロジェクトを選択
   - サービス（Service）を選択
   - サービス名の下、または「Settings」タブの「Domains」セクションでURLを確認
     - 例: `https://your-app-name.up.railway.app`
     - または、カスタムドメインを設定している場合はそのURL

2. **デプロイログから確認**
   - デプロイが完了すると、ログに表示されるURLを確認
   - サービス一覧のカードに表示されるURLを確認

3. **環境変数タブで確認**
   - Railwayダッシュボードで「Variables」タブを開く
   - 既に`APP_URL`が設定されている場合は、そこで確認できます
   - 設定されていない場合は、上記の方法でURLを確認してから設定してください

4. **環境変数での設定**
   - 確認したURLを`APP_URL`環境変数に設定
   - **必ず`https://`で始まるURLを設定してください**
   - 末尾にスラッシュ（`/`）は付けないでください

### 必須環境変数

```bash
# アプリケーション基本設定
APP_NAME="ガイドヘルパーマッチング"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.up.railway.app  # ← Railwayで確認したURLを設定

# アプリケーションキー（本番用に新規生成）
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# データベース接続（Railwayが自動提供する個別変数を使用）
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

# ⚠️ 重要: 以下の環境変数は設定しないでください（削除してください）
# - DATABASE_URL: RailwayのVariablesから完全に削除（Laravelのパース処理で配列として誤認される）
# - DB_SCHEMA: search_pathはコード内で'public'に固定されています
# - SEARCH_PATH: Railwayが自動注入する可能性があるが、削除してください
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

### プロジェクト特有の環境変数

このプロジェクトで使用される追加の環境変数：

```bash
# 管理者アカウント作成用（初回デプロイ時のみ）
# マイグレーション実行時に自動的に管理者アカウントを作成します
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=your-secure-password
ADMIN_NAME=管理者

# OpenAI API（AI入力補助機能用、オプション）
# 設定しない場合、基本的な整形処理が実行されます
OPENAI_API_KEY=sk-your-openai-api-key-here
```

**注意事項**:
- `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`: 初回デプロイ時に管理者アカウントを自動作成するために使用されます。設定しない場合はデフォルト値（`admin@example.com` / `admin123456`）が使用されますが、**本番環境では必ず設定してください**。
- `OPENAI_API_KEY`: AI入力補助機能（音声入力テキストの整形）で使用されます。設定しない場合でもアプリケーションは動作しますが、基本的な整形処理のみが実行されます。

### OPENAI_API_KEYの取得方法

OpenAI APIキーは以下の手順で取得できます：

1. **OpenAIアカウントの作成**
   - [OpenAI Platform](https://platform.openai.com/) にアクセス
   - アカウントを作成（既にアカウントがある場合はログイン）

2. **APIキーの生成**
   - ログイン後、右上のプロフィールアイコンをクリック
   - 「API keys」を選択
   - 「Create new secret key」をクリック
   - キー名を入力（例: "Guide Helper App"）
   - 「Create secret key」をクリック
   - **重要**: 表示されたキーをコピーしてください。このキーは一度しか表示されません

3. **キーの形式**
   - APIキーは `sk-` で始まる文字列です
   - 例: `sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

4. **Railwayでの設定**
   - Railwayダッシュボードの「Variables」タブで`OPENAI_API_KEY`を追加
   - コピーしたキーを値として設定

**注意事項**:
- APIキーは秘密情報です。Gitにコミットしないでください
- APIキーには使用量に応じた料金が発生します（GPT-3.5-turboの場合、比較的安価）
- キーが漏洩した場合は、OpenAIダッシュボードから削除して新しいキーを生成してください
- この機能はオプションです。設定しない場合でもアプリケーションは正常に動作します

### APP_KEYの生成方法

ローカルの`.env`からコピーせず、本番環境用に新しく生成してください。

**ローカルで生成する方法**：

```bash
php artisan key:generate --show
```

生成されたキー（`base64:...`で始まる文字列）をコピーして、Railwayダッシュボードの「Variables」タブで`APP_KEY`環境変数に設定してください。

**注意**: Railwayには直接コンソールアクセスがないため、ローカル環境で生成したキーをコピーして設定します。

### PHPバージョンの指定

Railway（Nixpacks）でPHP 8.2以上を使用するように設定する必要があります。以下のいずれかの方法で設定してください：

**方法1: 環境変数で指定（推奨）**

Railwayダッシュボードの「Variables」タブで以下を追加：

```bash
NIXPACKS_PHP_VERSION=8.2
```

**方法2: nixpacks.tomlファイルを使用**

プロジェクトルートに`nixpacks.toml`ファイルが作成されています：

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer", "nodejs-18_x"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader", "npm install"]

[phases.build]
cmds = ["npm run build"]

[start]
cmd = "bash scripts/start.sh"
```

**重要**: 
- `composer.json`の`require`セクションで`"php": "^8.2"`が指定されていることを確認してください（既に更新済み）
- `composer.lock`も更新されていることを確認してください（`composer update`を実行済み）

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
- Railwayダッシュボードの「Variables」タブで`APP_URL`が正しく設定されているか確認
- ブラウザでアプリケーションにアクセスし、Mixed Contentエラーが発生していないか確認
- ブラウザの開発者ツール（F12）で、Networkタブを開き、リソースがHTTPSで読み込まれているか確認

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

### PostgreSQLのexplode()エラー（search_path関連）

**エラーメッセージ**: `explode(): Argument #2 ($string) must be of type string, array given` in `PostgresBuilder.php`

**原因**: `search_path`が配列として解釈されている（DATABASE_URLのパース処理や環境変数の自動注入が原因）

**解決策**（順番に試してください）:

1. **Railwayの環境変数を確認・削除**
   - `DB_SCHEMA`という環境変数が設定されていないか確認 → **削除**
   - `SEARCH_PATH`という環境変数が設定されていないか確認 → **削除**（Railwayが自動注入する可能性あり）
   - `DATABASE_URL`を削除（個別変数を使用するため）

2. **config/database.phpの設定確認**
   - `'url' => env('DATABASE_URL'),`がコメントアウトされていることを確認
   - `'search_path' => 'public',`が直書き（envを使わない）になっていることを確認

3. **個別のデータベース変数を設定**
   - Railwayの「Variables」タブで以下を設定：
     ```bash
     DB_CONNECTION=pgsql
     DB_HOST=${PGHOST}
     DB_PORT=${PGPORT}
     DB_DATABASE=${PGDATABASE}
     DB_USERNAME=${PGUSER}
     DB_PASSWORD=${PGPASSWORD}
     ```

4. **ビルドキャッシュのクリア**
   - Railwayダッシュボードで「Clear Build Cache」を実行
   - 再デプロイを実行してください

5. **設定キャッシュのクリア**
   - `scripts/start.sh`に`php artisan config:clear`が含まれていることを確認（既に追加済み）

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

