# Railwayでマイグレーションがpredeployで失敗する問題の解決方法

## 🔴 問題

Railwayのpredeployコマンドで`php artisan migrate --force`を実行すると、以下のような理由で失敗することがあります：

1. **環境変数が読み込まれていない**: predeploy時点では環境変数がまだ完全に読み込まれていない可能性
2. **データベース接続が確立されていない**: データベースサービスがまだ起動していない、または接続情報が利用できない
3. **APP_KEYが設定されていない**: Laravelが起動するために必要な`APP_KEY`が設定されていない
4. **権限の問題**: `storage/`や`bootstrap/cache/`ディレクトリに書き込み権限がない

## ✅ 解決方法

### 方法1: 起動スクリプトを使用する（推奨）

`scripts/start.sh`を作成し、サーバー起動前にマイグレーションを実行する方法です。

**メリット**:
- データベース接続が確立された後に実行される
- 環境変数が完全に読み込まれた後に実行される
- エラーハンドリングが容易

**設定方法**:

1. `railway.json`で`startCommand`を変更：

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "bash scripts/start.sh",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

2. `scripts/start.sh`が含まれていることを確認（Gitにコミット済み）

### 方法2: Railwayのコンソールから手動実行

predeployコマンドを削除し、デプロイ後にRailwayのコンソールから手動で実行：

```bash
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder
```

**メリット**:
- シンプル
- エラーが発生した場合の対処が容易

**デメリット**:
- 毎回手動で実行する必要がある
- 自動化されていない

### 方法3: RailwayのDeploy Hooksを使用（将来の機能）

RailwayにDeploy Hooks機能が追加された場合は、それを活用できます。

---

## 📋 `scripts/start.sh`の動作

起動スクリプトは以下の順序で実行されます：

1. **データベース接続の待機**: 最大30秒間、データベース接続を確認
2. **マイグレーション実行**: `php artisan migrate --force`
3. **ストレージリンク作成**: `php artisan storage:link`
4. **キャッシュのクリアと再生成**: 必要に応じて
5. **サーバー起動**: `php artisan serve`

---

## 🔧 トラブルシューティング

### 問題: スクリプトが実行されない

**解決策**:
1. `chmod +x scripts/start.sh`を実行して実行権限を付与（ローカルで）
2. Gitにコミット・プッシュされているか確認

### 問題: データベース接続エラー

**確認事項**:
1. Railwayの環境変数に`DB_HOST`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`が設定されているか
2. MySQLサービスが起動しているか
3. 環境変数が**アプリケーションサービス**に設定されているか（MySQLサービスではない）

### 問題: APP_KEYが設定されていない

**解決策**:
```bash
php artisan key:generate
```
生成されたキーをRailwayの環境変数`APP_KEY`に設定してください。

---

## 📚 関連ドキュメント

- `RAILWAY_ENV_VARIABLES.md` - 環境変数の設定方法
- `docs/ADMIN_USER_SEEDING.md` - 管理者アカウントの作成方法

