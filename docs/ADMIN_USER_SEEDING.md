# 管理者アカウントの事前作成

このドキュメントでは、データベースシーダーを使用して管理者アカウントを事前に作成する方法を説明します。

## 📋 概要

`AdminUserSeeder`を使用することで、マイグレーション実行時に自動的に管理者アカウントを作成できます。環境変数で管理者のメールアドレスとパスワードを設定できます。

---

## 🚀 使用方法

### 方法1: 環境変数を使用する（推奨）

`.env`ファイルまたはRailwayの環境変数で、以下の変数を設定します：

```env
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=your-secure-password
ADMIN_NAME=管理者
```

**デフォルト値**:
- `ADMIN_EMAIL`: `admin@example.com`
- `ADMIN_PASSWORD`: `admin123456`
- `ADMIN_NAME`: `管理者`

### 方法2: デフォルト値を使用する

環境変数を設定しない場合、デフォルト値が使用されます。**本番環境では必ず環境変数を設定してください。**

---

## 🎯 実行方法

### ローカル環境での実行

```bash
# データベースをリセットして、すべてのシーダーを実行
php artisan migrate:fresh --seed

# または、既存のデータベースに管理者を追加する場合（既存の管理者をチェック）
php artisan db:seed --class=AdminUserSeeder
```

### Railwayでの実行

#### ステップ1: 環境変数の設定（重要）

**⚠️ 重要**: これらの環境変数は**Laravelアプリケーションサービス**に設定します（MySQLサービスではありません）。

Railwayダッシュボードで：
1. **Laravelアプリケーションサービス**を選択
2. 「Variables」タブを開く
3. 以下の環境変数を追加：

```env
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=your-secure-password
ADMIN_NAME=管理者
```

**注意**: MySQLサービスではなく、**アプリケーションサービス**の環境変数に設定してください。

#### ステップ2: シーダーの実行

デプロイ後の初回のみ、Railwayのコンソール（**アプリケーションサービス**のコンソール）から手動で実行：

```bash
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder
```

または、`DatabaseSeeder`を実行してすべてのシーダーを実行：

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## ⚙️ シーダーの動作

### 重複チェック

`AdminUserSeeder`は、指定したメールアドレスのユーザーが既に存在する場合、新規作成をスキップします。

### 管理者アカウントの設定

作成される管理者アカウントには、以下の設定が適用されます：

- **role**: `admin`
- **is_allowed**: `true` (承認不要)
- **email_confirmed**: `true` (メール確認済み)

---

## 🔒 セキュリティの注意事項

### 1. パスワードの管理

- **本番環境では強力なパスワードを使用してください**
- 環境変数`ADMIN_PASSWORD`には、推測されにくいパスワードを設定してください
- パスワードはハッシュ化されて保存されます（`Hash::make()`を使用）

### 2. メールアドレスの設定

- 本番環境では、実際に使用可能なメールアドレスを設定してください
- 管理者メールアドレスは、重要な通知などで使用される可能性があります

### 3. 環境変数の保護

- `.env`ファイルはGitにコミットしないでください（`.gitignore`に含まれています）
- Railwayの環境変数は、機密情報として適切に管理してください

---

## 📝 実行例

### 例1: 環境変数を設定して実行

```bash
# .envファイルに設定
ADMIN_EMAIL=admin@guide-helper.com
ADMIN_PASSWORD=SecurePassword123!

# シーダーを実行
php artisan db:seed --class=AdminUserSeeder
```

**出力**:
```
管理者アカウントを作成しました:
  メールアドレス: admin@guide-helper.com
  パスワード: SecurePassword123!
  名前: 管理者
```

### 例2: 既存の管理者が存在する場合

既に管理者が存在する場合は、以下のメッセージが表示されます：

```
管理者アカウント（admin@example.com）は既に存在しています。
```

---

## 🔧 トラブルシューティング

### 問題: シーダーが実行されない

**解決策**:
1. `DatabaseSeeder`が`AdminUserSeeder`を呼び出しているか確認
2. `php artisan db:seed`の代わりに、`php artisan db:seed --class=AdminUserSeeder`を実行

### 問題: パスワードが正しくない

**解決策**:
1. 環境変数`ADMIN_PASSWORD`が正しく設定されているか確認
2. シーダーを再実行する前に、既存の管理者アカウントを削除するか、別のメールアドレスを使用

### 問題: 管理者でログインできない

**確認事項**:
1. `is_allowed`が`true`に設定されているか
2. `email_confirmed`が`true`に設定されているか
3. パスワードが正しく入力されているか
4. メールアドレスが正しく入力されているか

---

## 📚 関連ファイル

- `database/seeders/AdminUserSeeder.php` - 管理者シーダー
- `database/seeders/DatabaseSeeder.php` - メインシーダー
- `app/Models/User.php` - ユーザーモデル

---

## 🎯 次のステップ

1. Railwayの環境変数に`ADMIN_EMAIL`と`ADMIN_PASSWORD`を設定
2. デプロイ後にシーダーを実行
3. 管理者アカウントでログインできることを確認

