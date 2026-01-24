# データベースドキュメント

このドキュメントは、データベースの確認方法、SQLクエリ、データ不整合の分析についてまとめたものです。

## データベース構造

### テーブル一覧

- `users` - ユーザー情報
- `user_profiles` - ユーザープロフィール
- `guide_profiles` - ガイドプロフィール
- `requests` - 依頼情報
- `guide_acceptances` - ガイド承諾情報
- `matchings` - マッチング情報
- `chat_messages` - チャットメッセージ
- `reports` - 報告書
- `notifications` - 通知
- `admin_settings` - 管理者設定
- `announcements` - お知らせ
- `announcement_reads` - お知らせ既読情報

詳細は`database/schema.sql`を参照してください。

## データ確認方法

### 方法1: MySQLクライアントを使用（推奨）

1. **データベース接続情報を確認**
   ```bash
   # .envファイルを確認
   cat .env | grep DB_
   ```

2. **MySQLに接続**
   ```bash
   mysql -u [ユーザー名] -p [データベース名]
   # または
   php artisan db
   ```

3. **SQLクエリを実行**
   - `database/DATABASE_CHECK_QUERY.sql` - 承諾待ちデータ確認用
   - `database/MATCHINGS_ACTIVE_SQL.sql` - 進行中マッチング確認用

### 方法2: Laravel Tinkerを使用

Laravel Tinkerを使うと、PHPコードでデータを確認できます。

```bash
php artisan tinker
```

Tinker内で以下のコマンドを実行：

```php
// AdminServiceのメソッドを直接実行
$service = app(\App\Services\AdminService::class);
$acceptances = $service->getPendingAcceptances();
print_r($acceptances);

// 直接モデルで確認
\App\Models\GuideAcceptance::where('status', 'pending')
    ->with(['request:id,request_type,masked_address,request_date,request_time', 'guide:id,name', 'request.user:id,name'])
    ->orderBy('created_at', 'desc')
    ->get()
    ->toArray();
```

### 方法3: phpMyAdminやDBeaverなどのGUIツール

データベース管理ツールを使用する場合：

1. データベースに接続
2. 該当テーブルを開く
3. フィルターを設定してデータを確認

## よく使用するSQLクエリ

### 承諾待ちデータの確認

`database/DATABASE_CHECK_QUERY.sql`を参照してください。

主なクエリ：

```sql
-- 承諾待ちデータの基本確認
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.status,
    ga.admin_decision,
    ga.user_selected,
    r.request_type,
    r.masked_address,
    u.name AS user_name,
    g.name AS guide_name,
    ga.created_at
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;
```

### 進行中マッチングの確認

`database/MATCHINGS_ACTIVE_SQL.sql`を参照してください。

主なクエリ：

```sql
-- 進行中のマッチング（status = 'matched' または 'in_progress'）
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status IN ('matched', 'in_progress')
ORDER BY m.matched_at DESC;
```

## データ不整合の分析

### データ不整合の例

**問題**: `matchings`テーブルで`guide_id`が誤っている（ユーザーIDがガイドとして設定されている）

**データベースの状態**:

```
guide_acceptances テーブル:
- guide_id: 5 (role = 'user') ❌ 本来は4 (role = 'guide')であるべき

matchings テーブル（修正後）:
- guide_id: 4 ✅ 修正済み
```

### 不整合が発生した可能性のある箇所

1. **ガイド承諾時のロールチェック不足**
   - `app/Services/MatchingService.php::acceptRequest()`
   - `app/Http/Controllers/Api/MatchingController.php::accept()`

2. **マッチング作成時のデータ不整合**
   - `app/Services/MatchingService.php::createMatching()`
   - `app/Services/AdminService.php::approveMatching()`

### 推奨される修正

1. **ガイド承諾時のロールチェック強化**
   ```php
   // app/Services/MatchingService.php::acceptRequest()
   public function acceptRequest(int $requestId, int $guideId): array
   {
       // ガイドのロールチェックを追加
       $guide = User::findOrFail($guideId);
       if ($guide->role !== 'guide') {
           throw new \Exception('ガイドとして登録されていないユーザーです');
       }
       
       // 既存の処理...
   }
   ```

2. **マッチング作成時のロールチェック追加**
   ```php
   // app/Services/MatchingService.php::createMatching()
   public function createMatching(int $requestId, int $userId, int $guideId): Matching
   {
       // ガイドのロールチェック
       $guide = User::findOrFail($guideId);
       if ($guide->role !== 'guide') {
           throw new \Exception('ガイドとして登録されていないユーザーです');
       }
       
       // ユーザーのロールチェック
       $user = User::findOrFail($userId);
       if ($user->role !== 'user') {
           throw new \Exception('ユーザーとして登録されていないユーザーです');
       }
       
       // 既存の処理...
   }
   ```

## データ修正方法

### guide_acceptances テーブルの修正

```sql
UPDATE guide_acceptances 
SET guide_id = 4 
WHERE id = 1 AND guide_id = 5;
```

### request_type ENUM値の変更

```sql
-- 既存データの変換（日本語 → 英語コード値）
UPDATE requests SET request_type = 'outing' WHERE request_type = '外出';
UPDATE requests SET request_type = 'home' WHERE request_type = '自宅';

-- ENUM定義を英語コード値に変更
ALTER TABLE requests MODIFY COLUMN request_type ENUM('outing', 'home') NOT NULL COMMENT '依頼タイプ';
```

## 今後の対策

1. **ロールチェックの強化**: サービス層でもロールチェックを実装
2. **データ整合性チェック**: マッチング作成時に、guide_idとuser_idのロールを確認
3. **データベース制約**: 可能であれば、データベースレベルでの制約を追加
4. **ログの記録**: マッチング作成時に、ロールチェックの結果をログに記録

## 関連ドキュメント

- `docs/TROUBLESHOOTING.md` - トラブルシューティング
- `database/schema.sql` - データベーススキーマ
- `database/DATABASE_CHECK_QUERY.sql` - 承諾待ちデータ確認用SQL
- `database/MATCHINGS_ACTIVE_SQL.sql` - 進行中マッチング確認用SQL





