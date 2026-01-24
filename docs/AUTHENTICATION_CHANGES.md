# 認証方法の変更: JWT → セッション認証

## 変更概要

認証方法をJWTからLaravel標準のセッション認証に変更しました。

## 変更内容

### Laravel側の変更

1. **`config/auth.php`**
   - APIガードのドライバーを`jwt`から`session`に変更

2. **`config/cors.php`**
   - `supports_credentials`を`true`に変更（セッションCookieを送信するため）

3. **`app/Http/Kernel.php`**
   - APIミドルウェアグループにセッション関連のミドルウェアを追加：
     - `EnsureFrontendRequestsAreStateful`（Sanctum）
     - `AddQueuedCookiesToResponse`
     - `StartSession`

4. **`routes/api.php`**
   - ミドルウェアを`auth:api`から`auth`に変更

5. **`app/Http/Controllers/AuthController.php`**
   - JWTトークン生成を削除
   - APIリクエストでもセッション認証を使用
   - `user()`メソッドで`auth('api')`から`auth()`に変更

### フロントエンド側の変更

1. **`frontend/src/contexts/AuthContext.jsx`**
   - JWTトークンのstateを削除
   - `Authorization`ヘッダーの設定を削除
   - `axios.defaults.withCredentials = true`を追加（セッションCookieを送信）
   - `login()`と`register()`でトークンを設定しない
   - 初期ロード時に直接`fetchUser()`を呼び出す

2. **`frontend/vite.config.js`**
   - プロキシ設定に`credentials: true`を追加

### ドキュメントの更新

1. **`README.md`**
   - 認証方法を「Session（Laravel標準認証）」に更新
   - セキュリティセクションにCSRF保護を追加
   - セットアップ手順から`php artisan jwt:secret`を削除

## 動作確認

### セッション認証の動作

1. **ログイン**
   - `/api/auth/login`にPOSTリクエストを送信
   - セッションCookieが自動的に設定される
   - レスポンスにユーザー情報が含まれる（トークンは含まれない）

2. **認証が必要なAPIリクエスト**
   - セッションCookieが自動的に送信される
   - `auth()`ミドルウェアで認証状態を確認

3. **ログアウト**
   - `/api/auth/logout`にPOSTリクエストを送信
   - セッションが無効化される

## 注意事項

### CORS設定

- セッションCookieを送信するため、`config/cors.php`で`supports_credentials`を`true`に設定
- フロントエンドとバックエンドが異なるドメインの場合、適切なCORS設定が必要

### CSRF保護

- Sanctumの`EnsureFrontendRequestsAreStateful`ミドルウェアがCSRF保護を自動的に処理
- 同一ドメインからのリクエスト（Viteプロキシ経由）は自動的にCSRF保護が有効になる

### セッションストレージ

- デフォルトでデータベースセッションを使用（`config/session.php`で設定）
- セッションテーブルが存在することを確認：`php artisan session:table` → `php artisan migrate`

## 移行後の確認事項

- [x] PostgreSQLデータベースがインストール・起動されているか確認
- [ ] セッションテーブルが作成されているか確認（`php artisan session:table` → `php artisan migrate`）
- [ ] `.env`ファイルのデータベース設定をPostgreSQLに変更
- [ ] ログインが正常に動作するか確認
- [ ] 認証が必要なAPIリクエストが正常に動作するか確認
- [ ] ログアウトが正常に動作するか確認
- [ ] CORS設定が正しく動作しているか確認

## トラブルシューティング

### PostgreSQL接続エラー

PostgreSQLがインストールされていない、または起動していない場合：

1. **PostgreSQLのインストール確認**
   ```bash
   psql --version
   ```

2. **PostgreSQLの起動**
   ```bash
   # macOS (Homebrew)
   brew services start postgresql@14
   
   # または
   pg_ctl -D /usr/local/var/postgres start
   ```

3. **データベースの作成**
   ```bash
   createdb guide_helper
   ```

4. **接続確認**
   ```bash
   psql -d guide_helper -U postgres
   ```

### セッション認証が機能しない場合

1. **セッションテーブルの作成**
   ```bash
   php artisan session:table
   php artisan migrate
   ```

2. **設定の確認**
   - `.env`で`SESSION_DRIVER=database`が設定されているか
   - `config/session.php`で`driver`が`database`になっているか

3. **ブラウザのCookie設定を確認**
   - 開発者ツールでセッションCookieが送信されているか確認
   - `credentials: 'include'`が設定されているか確認

## 参考

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Laravel Session Documentation](https://laravel.com/docs/session)

