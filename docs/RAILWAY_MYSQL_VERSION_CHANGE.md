# Railway MySQLバージョン変更方法

## 🎯 目的

MySQL 9.4では`mysql_native_password`が使用できないため、MySQL 5.7に変更します。

## 📋 手順

### ステップ1: MySQLサービスの設定を開く

1. Railwayダッシュボードで**MySQLサービス**を選択
2. 「**Settings**」タブを開く

### ステップ2: ソースイメージを変更

1. 「**Source**」セクションを探す
2. 「**Source Image**」を確認
3. 現在: `mysql:9.4` または `mysql:latest`
4. 変更: `mysql:5.7`

**注意**: Railwayの設定画面で直接変更できない場合は、`railway.json`やサービス設定ファイルで指定する必要があるかもしれません。

### ステップ3: サービスを再デプロイ

1. MySQLサービスを再起動または再デプロイ
2. データベースが再作成されるため、**既存のデータは失われます**

### ステップ4: 環境変数を確認

MySQL 5.7に変更後、環境変数が正しく設定されているか確認：

```env
DB_CONNECTION=mysql
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=[新しいパスワード]
```

---

## ⚠️ 注意事項

- **データのバックアップ**: MySQL 5.7に変更すると、既存のデータは失われます
- **ボリュームの削除**: MySQL 9.4のデータファイルが残っていると、初期化に失敗します。必ずボリュームを削除するか、サービスを再作成してください
- **マイグレーションの再実行**: データベースが再作成された後、マイグレーションを再実行する必要があります
- **接続情報の更新**: 環境変数が自動的に更新されるか確認してください

## 🔄 MySQLサービスを削除して再作成する方法

もしボリュームが削除できない、または`--initialize specified but the data directory has files in it`エラーが続く場合：

1. **MySQLサービスを削除**:
   - MySQLサービスを選択
   - 「Settings」→「Delete Service」
   - 確認して削除

2. **新しいMySQL 5.7サービスを作成**:
   - 「+ New」→「Database」→「Add MySQL」
   - ソースイメージを`mysql:5.7`に設定
   - 作成

3. **環境変数を更新**:
   - 新しいMySQLサービスの接続情報を確認
   - Laravelアプリケーションサービスの環境変数を更新：
     - `DB_HOST`
     - `DB_PORT`
     - `DB_DATABASE`
     - `DB_USERNAME`
     - `DB_PASSWORD`

---

## 🔄 代替案

MySQL 5.7が利用できない場合、またはデータを保持したい場合は、**解決策2**（接続設定の修正）を試してください。

