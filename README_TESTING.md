# テスト手順ガイド

## 概要

このドキュメントは、実装した機能をテストするための手順を説明します。

## 前提条件

- Laravel 11がインストールされていること
- データベースが設定されていること
- `.env`ファイルが正しく設定されていること

## テスト手順

### 1. データベースマイグレーション実行

#### 方法1: SQLファイルを直接実行（推奨）

```bash
mysql -u [username] -p [database_name] < database/migrations/add_requirements_fields.sql
```

#### 方法2: Laravelのマイグレーションとして実行

```bash
php artisan migrate
```

ただし、`add_requirements_fields.sql`はLaravelのマイグレーションファイル形式ではないため、直接実行することを推奨します。

#### 方法3: カスタムスクリプトを使用

```bash
php run_migration.php
```

### 2. 登録フォームのテスト

#### 2.1 ユーザー（利用者）登録テスト

1. ブラウザで `/register` にアクセス
2. ロールを「ユーザー（視覚障害者）」に選択
3. **必須項目を入力せずに送信**
   - エラーメッセージが表示されることを確認
   - 各必須項目にエラーが表示されることを確認
4. **全ての必須項目を入力**
   - 氏名、カナ、郵便番号、住所、メール（確認欄含む）、電話番号、性別、生年月日
   - 面談希望日時（第1希望必須）
   - 応募のきっかけ
   - 視覚障害の状況
   - 障害支援区分
   - 普段の生活状況
5. **送信して登録成功を確認**
6. **データベースで確認**
   ```sql
   SELECT * FROM users WHERE email = '[登録したメールアドレス]';
   SELECT * FROM user_profiles WHERE user_id = [ユーザーID];
   ```

#### 2.2 ガイド登録テスト

1. ブラウザで `/register` にアクセス
2. ロールを「ガイドヘルパー」に選択
3. **必須項目を入力せずに送信**
   - エラーメッセージが表示されることを確認
4. **全ての必須項目を入力**
   - 共通項目（氏名、カナ、郵便番号、住所、メール、電話番号、性別、生年月日）
   - 応募理由
   - 実現したいこと
   - 保有資格（1件以上、最大3件）
   - 希望勤務時間
5. **送信して登録成功を確認**
6. **データベースで確認**
   ```sql
   SELECT * FROM users WHERE email = '[登録したメールアドレス]';
   SELECT * FROM guide_profiles WHERE user_id = [ガイドID];
   SELECT qualifications FROM guide_profiles WHERE user_id = [ガイドID];
   ```

### 3. 依頼投稿の指名機能テスト

1. **ガイドを承認済みにする**（管理者として）
   ```sql
   UPDATE users SET is_allowed = 1 WHERE role = 'guide' AND id = [ガイドID];
   ```

2. **ユーザーとしてログイン**

3. **依頼作成ページにアクセス** (`/requests/create`)

4. **指名ガイド選択ドロップダウンを確認**
   - ドロップダウンが表示されることを確認
   - 「指名しない」オプションがあることを確認
   - 承認済みのガイド一覧が表示されることを確認

5. **ガイドを選択して依頼を作成**

6. **データベースで確認**
   ```sql
   SELECT id, user_id, nominated_guide_id FROM requests ORDER BY id DESC LIMIT 1;
   ```

### 4. 利用者の限度時間管理テスト

#### 4.1 限度時間の設定（管理者）

**API経由で設定**（Postmanやcurlを使用）:

```bash
curl -X POST http://localhost/api/admin/user-limits \
  -H "Authorization: Bearer [管理者のJWTトークン]" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": [ユーザーID],
    "year": 2024,
    "month": 12,
    "limit_hours": 10.0
  }'
```

または、データベースで直接設定:

```sql
INSERT INTO user_monthly_limits (user_id, year, month, limit_hours, used_hours)
VALUES ([ユーザーID], 2024, 12, 10.0, 0.0)
ON DUPLICATE KEY UPDATE limit_hours = 10.0;
```

#### 4.2 依頼作成時の残時間チェック

1. **ユーザーとしてログイン**

2. **限度時間内で依頼を作成**
   - 依頼時間が残時間以内の場合、依頼作成が成功することを確認

3. **限度時間を超える依頼を作成**
   - 依頼時間が残時間を超える場合、エラーメッセージが表示されることを確認
   - エラーメッセージに残時間と必要時間が表示されることを確認

#### 4.3 報告書確定時の使用時間更新

1. **マッチングを作成**（管理者として）

2. **ガイドとしてログイン**

3. **報告書を作成・提出**

4. **ユーザーとしてログイン**

5. **報告書を承認**

6. **データベースで確認**
   ```sql
   SELECT * FROM user_monthly_limits WHERE user_id = [ユーザーID] AND year = 2024 AND month = 12;
   ```
   - `used_hours`が更新されていることを確認

## トラブルシューティング

### マイグレーションエラー

**問題**: `IF NOT EXISTS`構文がサポートされていない

**解決策**: SQLファイルを直接実行するか、カラム/テーブルの存在確認を手動で行う

### バリデーションエラー

**問題**: 必須項目を入力してもエラーが表示される

**解決策**: 
- ブラウザの開発者ツールでコンソールエラーを確認
- フォームの`required`属性が正しく設定されているか確認
- サーバーサイドのバリデーションルールを確認

### APIエラー

**問題**: ガイド一覧が取得できない

**解決策**:
- JWTトークンが正しく設定されているか確認
- `/api/guides/available`エンドポイントが正しく登録されているか確認
- ガイドが承認済み（`is_allowed = 1`）であることを確認

## テスト結果の記録

テスト結果は `TESTING_CHECKLIST.md` に記録してください。

## 参考資料

- `TESTING_CHECKLIST.md`: 詳細なテストチェックリスト
- `IMPLEMENTATION_SUMMARY.md`: 実装サマリー
- `REQUIREMENTS_GAP_ANALYSIS.md`: 要件定義書との比較分析




