# ユーザー削除マイグレーション実行ガイド

## 概要

このドキュメントでは、本番環境で特定のユーザー（`sc30kd35ma30@gmail.com`）を削除するマイグレーションの実行方法を説明します。

## ⚠️ 重要な注意事項

1. **この操作は不可逆です** - ユーザーと関連するすべてのデータが完全に削除されます
2. **必ずデータベースのバックアップを取得してください** - 実行前にRailwayのデータベースバックアップ機能を使用することを強く推奨します
3. **実行前の確認** - 削除対象のユーザーと関連データを事前に確認してください

## 実行前の確認手順

### 1. ユーザーの存在確認

Railwayのコンソールから以下のコマンドでユーザーを確認できます：

```bash
php artisan tinker
```

Tinker内で：

```php
$user = \App\Models\User::where('email', 'sc30kd35ma30@gmail.com')->first();
if ($user) {
    echo "ユーザーが見つかりました:\n";
    echo "ID: {$user->id}\n";
    echo "名前: {$user->name}\n";
    echo "ロール: {$user->role}\n";
    
    // 関連データの確認
    echo "\n関連データ:\n";
    echo "Requests: " . $user->requests()->count() . "\n";
    echo "Matchings (User): " . $user->matchingsAsUser()->count() . "\n";
    echo "Matchings (Guide): " . $user->matchingsAsGuide()->count() . "\n";
    echo "Reports (User): " . $user->reportsAsUser()->count() . "\n";
    echo "Reports (Guide): " . $user->reportsAsGuide()->count() . "\n";
} else {
    echo "ユーザーが見つかりませんでした。\n";
}
exit
```

### 2. データベースバックアップの取得

Railwayダッシュボードから：

1. プロジェクトを選択
2. PostgreSQLデータベースサービスを選択
3. 「Backups」タブを開く
4. 「Create Backup」をクリックしてバックアップを作成

または、Railway CLIを使用：

```bash
railway backup create
```

## 本番環境での実行方法

### 方法1: Railwayコンソールから実行（推奨）

1. **Railwayダッシュボードにアクセス**
   - https://railway.app にログイン
   - プロジェクトを選択
   - アプリケーションサービスを選択

2. **コンソールを開く**
   - サービスページで「View Logs」の横にある「Console」タブをクリック
   - または、サービス一覧から「...」メニュー → 「Open Console」

3. **マイグレーションを実行**
   ```bash
   php artisan migrate --force
   ```

4. **実行結果の確認**
   - コンソールに詳細なログが表示されます
   - 削除されるデータの件数が表示されます
   - エラーが発生した場合は、ログを確認してください

### 方法2: Railway CLIから実行

1. **Railway CLIをインストール**（未インストールの場合）
   ```bash
   npm i -g @railway/cli
   ```

2. **ログイン**
   ```bash
   railway login
   ```

3. **プロジェクトにリンク**
   ```bash
   railway link
   ```

4. **マイグレーションを実行**
   ```bash
   railway run php artisan migrate --force
   ```

### 方法3: ローカルから実行（SSH接続経由）

RailwayでSSH接続が有効な場合：

```bash
ssh your-railway-service
php artisan migrate --force
```

## マイグレーションの動作

このマイグレーションは以下の処理を実行します：

1. **ユーザー情報の確認**
   - メールアドレス `sc30kd35ma30@gmail.com` でユーザーを検索
   - ユーザーが見つからない場合は処理をスキップ

2. **関連データの確認とレポート**
   - 削除される関連データの件数をカウント
   - 詳細なレポートを出力

3. **announcementsの処理**
   - このユーザーが作成したannouncementがある場合：
     - 管理者が存在する場合：`created_by`を管理者に変更
     - 管理者が存在しない場合：announcementを削除

4. **requestsの処理**
   - このユーザーが`nominated_guide_id`として指定されているrequestsの`nominated_guide_id`をNULLに設定

5. **ユーザーの削除**
   - ユーザーを削除（CASCADEにより関連データも自動削除）
   - 以下のテーブルが自動的に削除されます：
     - `user_profiles`
     - `guide_profiles`
     - `requests`（作成したもの）
     - `guide_acceptances`
     - `matchings`（user_id, guide_id）
     - `chat_messages`
     - `reports`（guide_id, user_id）
     - `notifications`
     - `user_monthly_limits`
     - `announcement_reads`

## 実行後の確認

マイグレーション実行後、以下のコマンドでユーザーが削除されたことを確認できます：

```bash
php artisan tinker
```

Tinker内で：

```php
$user = \App\Models\User::where('email', 'sc30kd35ma30@gmail.com')->first();
if ($user) {
    echo "⚠️ ユーザーがまだ存在します\n";
} else {
    echo "✅ ユーザーは正常に削除されました\n";
}
exit
```

## トラブルシューティング

### エラー: "ユーザーが見つかりませんでした"

- メールアドレスが正しいか確認してください
- 既に削除されている可能性があります
- マイグレーションは安全にスキップされます

### エラー: "ユーザー削除に失敗しました"

- データベース接続を確認してください
- 外部キー制約エラーが発生していないか確認してください
- Railwayのログを確認してください

### エラー: "announcementsのcreated_by変更に失敗"

- 管理者ユーザーが存在するか確認してください
- データベースの整合性を確認してください

## ロールバック

**注意**: このマイグレーションは不可逆操作のため、`migrate:rollback`では元に戻せません。

データを復元する場合は、事前に取得したバックアップから復元してください。

## 関連ファイル

- `database/migrations/2026_01_24_083006_delete_user_sc30kd35ma30_gmail_com.php` - マイグレーションファイル

## 参考資料

- [Railway Deployment Guide](./RAILWAY_DEPLOYMENT.md)
- [Railway Migration Fix](./RAILWAY_MIGRATION_FIX.md)

