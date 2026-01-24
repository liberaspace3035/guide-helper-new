# Railwayで502 Bad Gatewayエラーが発生する問題の解決方法

## 🔴 問題

Railwayの本番環境で502 Bad Gatewayエラーが発生する。

```
GET https://guide-helper-production.up.railway.app/
ステータスコード: 502 Bad Gateway
```

## 🎯 原因

502エラーは、Railwayのエッジサーバーからアプリケーションサーバーへの接続に失敗した場合に発生します。主な原因：

1. **アプリケーションサーバーが起動していない**: 起動スクリプトがエラーで終了している
2. **ポートが正しく設定されていない**: `$PORT`環境変数が設定されていない、または正しく使用されていない
3. **起動スクリプトのエラー**: `set -e`により、エラーでスクリプトが停止している
4. **データベース接続チェックの失敗**: 存在しないコマンド（`db:show`など）を使用している

## ✅ 解決方法

### 方法1: 起動スクリプトの修正（推奨）

起動スクリプト`scripts/start.sh`で以下を確認：

1. **`set -e`を削除**: エラーが発生してもスクリプトが続行されるようにする
2. **データベース接続チェックの修正**: `php artisan db:show`は存在しないコマンドのため、`php artisan migrate:status`を使用

### 方法2: Railwayのログを確認

1. Railwayダッシュボードでサービスを選択
2. 「Deployments」タブを開く
3. 最新のデプロイを選択
4. 「Logs」タブでエラーメッセージを確認

### 方法3: 環境変数の確認

以下の環境変数が正しく設定されているか確認：

```env
APP_KEY=base64:...
APP_ENV=production
APP_DEBUG=false
APP_URL=https://guide-helper-production.up.railway.app

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

JWT_SECRET=...
```

### 方法4: ポート環境変数の確認

Railwayでは`$PORT`環境変数が自動的に設定されますが、確認してください。

---

## 🔧 トラブルシューティング

### 問題: 起動スクリプトが途中で停止する

**確認事項**:
1. `set -e`が削除されているか
2. 各コマンドに`|| true`が付いているか（必要に応じて）

**解決策**:
起動スクリプトを確認し、エラーが発生しても続行されるようにします。

### 問題: データベース接続チェックでエラー

**確認事項**:
1. `php artisan db:show`を使用していないか（存在しないコマンド）
2. `php artisan migrate:status`を使用しているか

**解決策**:
起動スクリプトを修正して、存在するコマンドを使用します。

### 問題: ポートエラー

**確認事項**:
1. `$PORT`環境変数が設定されているか
2. `php artisan serve --port=$PORT`が正しく実行されているか

**解決策**:
Railwayの環境変数を確認し、ポートが正しく設定されているか確認します。

---

## 📋 修正済みの起動スクリプト

`scripts/start.sh`は以下のように修正されています：

- `set -e`を削除（エラーでも続行）
- `php artisan db:show`を`php artisan migrate:status`に変更
- 各コマンドにエラーハンドリングを追加

---

## 🚀 次のステップ

1. 修正した起動スクリプトをコミット・プッシュ
2. Railwayで再デプロイ
3. ログを確認してアプリケーションが正常に起動しているか確認

---

## 📚 関連ドキュメント

- `docs/RAILWAY_MIGRATION_FIX.md` - マイグレーション実行の問題解決
- `RAILWAY_ENV_VARIABLES.md` - 環境変数の設定方法

