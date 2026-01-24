# セットアップガイド

このプロジェクトはLaravelベースのガイドヘルパーマッチングアプリケーションです。

## 前提条件

- PHP 8.1以上
- Composer
- MySQL 5.7以上
- Node.js（フロントエンド開発用、オプション）

## セットアップ手順

### 1. Laravelプロジェクトの初期化

```bash
# Composer依存関係のインストール
composer install

# .envファイルの作成（.env.exampleからコピー）
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate

# JWT秘密鍵の生成
php artisan jwt:secret
```

### 2. データベース設定

`.env`ファイルを編集してデータベース接続情報を設定：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=guide_helper
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. データベースマイグレーション実行

```bash
# マイグレーション実行
php artisan migrate

# 既存データがある場合は、既存のschema.sqlをインポートしてから
# マイグレーションを実行（既存テーブルがある場合はスキップされる）
```

### 4. ストレージリンクの作成

```bash
php artisan storage:link
```

### 5. サーバー起動

```bash
# 開発サーバー起動
php artisan serve

# または特定のポートで起動
php artisan serve --port=8000
```

## 動作確認

### 基本動作確認

1. **ホームページ**
   - `http://localhost:8000` にアクセス
   - ホームページが表示されることを確認

2. **ログイン**
   - `http://localhost:8000/login` にアクセス
   - ログインフォームが表示されることを確認

3. **登録**
   - `http://localhost:8000/register` にアクセス
   - 登録フォームが表示されることを確認

### API動作確認

APIエンドポイントは `/api` プレフィックスでアクセス可能です。

例：
- `POST /api/auth/register` - ユーザー登録
- `POST /api/auth/login` - ログイン
- `GET /api/auth/user` - 現在のユーザー情報取得

## 主要なエンドポイント

### Webルート
- `/` - ホーム
- `/login` - ログイン
- `/register` - 登録
- `/dashboard` - ダッシュボード（認証必要）
- `/requests` - 依頼一覧（ユーザー、認証必要）
- `/guide/requests` - ガイド向け依頼一覧（ガイド、認証必要）
- `/admin` - 管理者ダッシュボード（管理者、認証必要）

### APIルート
- `POST /api/auth/register` - ユーザー登録
- `POST /api/auth/login` - ログイン
- `GET /api/auth/user` - 現在のユーザー情報（認証必要）
- `GET /api/requests/my-requests` - 依頼一覧（ユーザー、認証必要）
- `GET /api/matchings/my-matchings` - マッチング一覧（認証必要）

詳細は`API_DESIGN.md`を参照してください。

## トラブルシューティング

### よくある問題

1. **マイグレーションエラー**
   - 既存のテーブルがある場合は、マイグレーションファイルを確認
   - 必要に応じて、既存のスキーマとマイグレーションを整合させる

2. **JWT認証エラー**
   - `php artisan jwt:secret` を実行して秘密鍵を生成
   - `config/auth.php` の設定を確認

3. **ルートが見つからない**
   - `php artisan route:list` でルート一覧を確認
   - `routes/web.php` と `routes/api.php` を確認

4. **ミドルウェアエラー**
   - `bootstrap/app.php` でミドルウェアが正しく登録されているか確認
   - `RoleMiddleware` が正しく動作しているか確認

5. **SQLSTATE[HY000] [2002] Connection refused**
   - データベース接続設定を確認
   - MySQLサーバーが起動しているか確認

## 次のステップ

1. `docs/TESTING_CHECKLIST.md` を参照して動作確認
2. UI差分チェック
3. パフォーマンステスト

## 外部アクセス（ngrok）

ngrokを使用して外部からアクセスする場合は、`docs/NGROK_SETUP.md`を参照してください。





