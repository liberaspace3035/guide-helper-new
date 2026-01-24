# 認証方式とデータベース構文の監査レポート

## 調査日
2026年1月

## 調査内容
1. JWT認証が使用されていないか（セッション認証のみであるべき）
2. MySQL構文が使用されていないか（PostgreSQL構文のみであるべき）

---

## 1. JWT認証の使用状況

### ❌ 問題点：JWT認証関連のコードが残存

#### 1.1 モデル
- **`app/Models/User.php`**
  - `JWTSubject`インターフェースを実装している（8行目、10行目）
  - `getJWTIdentifier()`メソッドが実装されている（48-51行目）
  - `getJWTCustomClaims()`メソッドが実装されている（56-61行目）

#### 1.2 コントローラー
以下のコントローラーでJWTトークンを生成している：

- **`app/Http/Controllers/ProfileController.php`** (29-37行目)
  ```php
  $jwtToken = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
  ```

- **`app/Http/Controllers/ChatController.php`** (23-31行目)
  ```php
  $jwtToken = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
  ```

- **`app/Http/Controllers/RequestController.php`** (23-31行目)
  ```php
  $jwtToken = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
  ```

#### 1.3 設定ファイル
- **`config/auth.php`**
  - ✅ `api`ガードのドライバーは`session`に設定されている（44行目） - **正しい**
  - ✅ `web`ガードのドライバーは`session`に設定されている（40行目） - **正しい**

- **`config/app.php`**
  - ❌ JWTサービスプロバイダーが登録されている（168行目）
    ```php
    Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
    ```

- **`config/jwt.php`**
  - ❌ JWT設定ファイルが存在する（削除すべき）

#### 1.4 依存関係
- **`composer.json`**
  - ❌ `tymon/jwt-auth`パッケージが含まれている（13行目）

#### 1.5 ルート
- **`routes/api.php`**
  - ✅ コメントに「認証ルート（JWT）」とあるが、実際のルートは`auth`ミドルウェアを使用（20行目） - **実装は正しい**

#### 1.6 ビュー
以下のBladeテンプレートでJWTトークンを使用している：
- `resources/views/profile.blade.php` (265-267行目)
- `resources/views/chat/show.blade.php` (208-210行目)
- `resources/views/requests/index.blade.php` (156-158行目)

### ✅ 正しく実装されている点
- `config/auth.php`で`api`ガードと`web`ガードの両方が`session`ドライバーを使用している
- `routes/api.php`のルートは`auth`ミドルウェアを使用しており、JWT専用の`auth:api`ミドルウェアは使用されていない

---

## 2. MySQL構文の使用状況

### ❌ 問題点：MySQL固有の構文が残存

#### 2.1 `app/Services/DashboardService.php`

以下のMySQL固有の関数が使用されている：

1. **`DATE_FORMAT()`** - MySQL固有（PostgreSQLでは`TO_CHAR`を使用）
   - 253行目: `DATE_FORMAT(actual_date, "%Y-%m")`
   - 385行目: `DATE_FORMAT(reports.actual_date, "%Y-%m")`

2. **`CONCAT()`** - MySQL固有（PostgreSQLでは`||`を使用）
   - 255-256行目: `CONCAT(actual_date, " ", actual_start_time)`
   - 308-309行目: `CONCAT(reports.actual_date, " ", reports.actual_start_time)`
   - 322-323行目: `CONCAT(actual_date, " ", actual_start_time)`
   - 387-388行目: `CONCAT(reports.actual_date, " ", reports.actual_start_time)`
   - 426-427行目: `CONCAT(reports.actual_date, " ", reports.actual_start_time)`
   - 484-485行目: `CONCAT(reports.actual_date, " ", reports.actual_start_time)`
   - 498-499行目: `CONCAT(actual_date, " ", actual_start_time)`

3. **`TIMESTAMPDIFF()`** - MySQL固有（PostgreSQLでは`EXTRACT(EPOCH FROM ...)`を使用）
   - 254-257行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 307-310行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 321-324行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 386-389行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 425-428行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 483-486行目: `TIMESTAMPDIFF(MINUTE, ...)`
   - 497-500行目: `TIMESTAMPDIFF(MINUTE, ...)`

4. **`GROUP_CONCAT()`** - MySQL固有（PostgreSQLでは`STRING_AGG`を使用）
   - 390行目: `GROUP_CONCAT(DISTINCT requests.request_type)`

#### 2.2 データベース設定
- **`config/database.php`**
  - ✅ デフォルト接続は`pgsql`に設定されている（19行目） - **正しい**
  - ⚠️ MySQL設定も残っているが、これはLaravelの標準設定なので問題なし

#### 2.3 マイグレーションファイル
- ✅ マイグレーションファイルはLaravelのスキーマビルダーを使用しており、データベース固有の構文は使用されていない

#### 2.4 データベーススキーマ
- ✅ `database/schema.sql`はPostgreSQL構文で記述されている

---

## 3. 推奨される修正

### 3.1 JWT認証の完全削除

1. **`app/Models/User.php`**
   - `JWTSubject`インターフェースの実装を削除
   - `getJWTIdentifier()`メソッドを削除
   - `getJWTCustomClaims()`メソッドを削除

2. **コントローラー**
   - `app/Http/Controllers/ProfileController.php`からJWTトークン生成コードを削除
   - `app/Http/Controllers/ChatController.php`からJWTトークン生成コードを削除
   - `app/Http/Controllers/RequestController.php`からJWTトークン生成コードを削除

3. **ビュー**
   - JWTトークンを`localStorage`に保存するコードを削除

4. **設定ファイル**
   - `config/app.php`からJWTサービスプロバイダーを削除
   - `config/jwt.php`を削除

5. **依存関係**
   - `composer.json`から`tymon/jwt-auth`を削除
   - `composer update`を実行

6. **ルート**
   - `routes/api.php`のコメントを修正（「認証ルート（JWT）」→「認証ルート」）

### 3.2 MySQL構文の完全削除

1. **`app/Services/DashboardService.php`**
   - `$isPostgres`による分岐で、MySQL構文の分岐（`else`節）を削除
   - PostgreSQL構文のみを使用するように修正

---

## 4. まとめ

### JWT認証
- ❌ **問題あり**: JWT関連のコードが多数残存している
- ✅ 認証設定自体はセッション認証に正しく設定されている

### MySQL構文
- ❌ **問題あり**: `DashboardService.php`でMySQL固有の構文が使用されている
- ✅ データベース設定はPostgreSQLに正しく設定されている

### 優先度
1. **高**: `DashboardService.php`のMySQL構文を削除（PostgreSQLで動作しない可能性がある）
2. **中**: JWT関連コードの削除（動作には影響しないが、不要な依存関係を残している）

---

## 5. 補足：Node.jsバックエンドについて

`backend/`ディレクトリには別のNode.js/Expressバックエンドが存在し、こちらではJWT認証が使用されています。これはLaravelアプリケーションとは別のシステムの可能性があるため、この監査の対象外としました。必要に応じて別途確認してください。

---

## 6. 修正完了状況

### ✅ 修正完了（2026年1月）

#### 6.1 JWT認証関連コードの削除
- ✅ `app/Models/User.php`から`JWTSubject`インターフェースとメソッドを削除
- ✅ `app/Http/Controllers/ProfileController.php`からJWTトークン生成コードを削除
- ✅ `app/Http/Controllers/ChatController.php`からJWTトークン生成コードを削除
- ✅ `app/Http/Controllers/RequestController.php`からJWTトークン生成コードを削除
- ✅ `resources/views/chat/show.blade.php`からJWTトークン使用コードを削除し、セッション認証に切り替え
- ✅ `resources/views/profile.blade.php`からJWTトークン使用コードを削除
- ✅ `resources/views/requests/index.blade.php`からJWTトークン使用コードを削除し、セッション認証に切り替え
- ✅ `config/app.php`からJWTサービスプロバイダーを削除
- ✅ `config/jwt.php`を削除
- ✅ `composer.json`から`tymon/jwt-auth`パッケージを削除
- ✅ `routes/api.php`のコメントを修正

#### 6.2 MySQL構文の削除
- ✅ `app/Services/DashboardService.php`からMySQL構文の分岐（`else`節）をすべて削除
- ✅ PostgreSQL構文のみを使用するように修正
- ✅ `$isPostgres`変数と条件分岐を削除

### 📝 次のステップ

1. **依存関係の更新**
   ```bash
   composer update
   ```
   これにより、`tymon/jwt-auth`パッケージが削除されます。

2. **動作確認**
   - セッション認証が正常に動作することを確認
   - ダッシュボードの統計情報が正しく表示されることを確認（PostgreSQL構文で動作）

3. **その他のビューファイル**
   以下のファイルでもJWTトークンを使用している可能性がありますが、これらは別の機能（React SPAなど）で使用されている可能性があるため、必要に応じて個別に確認してください：
   - `resources/views/requests/create.blade.php`
   - `resources/views/guide/requests/index.blade.php`
   - `resources/views/matchings/show.blade.php`

