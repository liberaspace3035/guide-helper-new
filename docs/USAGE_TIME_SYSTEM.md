# 利用時間計上システムの仕組み

## 概要

本システムでは、ガイドとユーザーの利用時間を報告書の承認時に計上し、月次で集計・管理しています。

---

## 1. 利用時間の計上タイミング

### 1.1 計上されるタイミング

利用時間は**管理者承認時（`adminApproveReport`）**に計上されます。

- **ユーザー承認時**: 計上されない（`user_approved` ステータス）
- **管理者承認時**: 計上される（`admin_approved` ステータス）

### 1.2 計上処理の流れ

```
報告書提出（ガイド）
  ↓
ユーザー承認（第1段階）
  ↓
管理者承認（第2段階）← ここで利用時間を計上
  ↓
月次限度時間に加算
```

---

## 2. 利用時間の計算方法

### 2.1 計算式

```php
// 実施時間を計算（分単位）
// actual_date は date型、actual_start_time/actual_end_time は time型なので組み合わせる必要がある
$actualDate = Carbon::parse($report->actual_date);

// actual_start_time と actual_end_time を文字列として取得（time型）
$startTimeStr = is_string($report->actual_start_time) 
    ? $report->actual_start_time 
    : $report->getRawOriginal('actual_start_time');
$endTimeStr = is_string($report->actual_end_time)
    ? $report->actual_end_time
    : $report->getRawOriginal('actual_end_time');

// actual_date と時刻を組み合わせて datetime を作成
$startDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $startTimeStr);
$endDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $endTimeStr);

// 終了時刻が開始時刻より小さい場合、翌日とみなす
if ($endDateTime->lt($startDateTime)) {
    $endDateTime->addDay();
}

$durationMinutes = $startDateTime->diffInMinutes($endDateTime);
$usedHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
```

### 2.2 計算の詳細

1. **開始時刻と終了時刻の取得**
   - `actual_date`: 実施日（依頼日に固定、変更不可）
   - `actual_start_time`: 開始時刻（HH:MM形式）
   - `actual_end_time`: 終了時刻（HH:MM形式）

2. **時間差の計算**
   - 開始時刻と終了時刻の差分を**分単位**で計算
   - 終了時刻が開始時刻より小さい場合（例：23:00 → 01:00）、翌日とみなす

3. **時間への変換**
   - 分を時間に変換（分 ÷ 60）
   - **小数点第1位まで**に丸める（`round($minutes / 60 * 10) / 10`）
   - 例：90分 → 1.5時間、95分 → 1.6時間

### 2.3 実装箇所

**ファイル**: `app/Services/ReportService.php`
**メソッド**: `adminApproveReport()`

```php
// 利用者の限度時間を更新（報告書確定時に自動更新）
if ($report->actual_date && $report->actual_start_time && $report->actual_end_time) {
    // 実施時間を計算（分単位）
    // actual_date は date型、actual_start_time/actual_end_time は time型なので組み合わせる必要がある
    $actualDate = Carbon::parse($report->actual_date);
    
    // actual_start_time と actual_end_time を文字列として取得（time型）
    $startTimeStr = is_string($report->actual_start_time) 
        ? $report->actual_start_time 
        : $report->getRawOriginal('actual_start_time');
    $endTimeStr = is_string($report->actual_end_time)
        ? $report->actual_end_time
        : $report->getRawOriginal('actual_end_time');
    
    // actual_date と時刻を組み合わせて datetime を作成
    $startDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $startTimeStr);
    $endDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $endTimeStr);
    
    // 終了時刻が開始時刻より小さい場合、翌日とみなす
    if ($endDateTime->lt($startDateTime)) {
        $endDateTime->addDay();
    }
    
    $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
    $usedHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
    
    // 実施日から年月を取得
    $year = $actualDate->year;
    $month = $actualDate->month;
    
    // 使用時間を追加
    $this->limitService->addUsedHours($report->user_id, $usedHours, $year, $month);
}
```

---

## 3. 月次限度時間の管理

### 3.1 データベース構造

**テーブル**: `user_monthly_limits`

| カラム | 型 | 説明 |
|--------|-----|------|
| `id` | INT | 主キー |
| `user_id` | INT | ユーザーID |
| `year` | INT | 年 |
| `month` | INT | 月 |
| `limit_hours` | DECIMAL(10,2) | 限度時間（時間） |
| `used_hours` | DECIMAL(10,2) | 使用時間（時間） |
| `created_at` | TIMESTAMP | 作成日時 |
| `updated_at` | TIMESTAMP | 更新日時 |

### 3.2 月次限度時間の更新

**ファイル**: `app/Services/UserMonthlyLimitService.php`

#### 使用時間の追加（報告書確定時）

```php
public function addUsedHours(int $userId, float $hours, int $year = null, int $month = null): UserMonthlyLimit
{
    $limit = $this->getOrCreateLimit($userId, $year, $month);
    $limit->used_hours += $hours;
    $limit->save();
    return $limit;
}
```

#### 残時間の計算

```php
public function getRemainingHours(int $userId, int $year = null, int $month = null): float
{
    $limit = $this->getOrCreateLimit($userId, $year, $month);
    return max(0, $limit->limit_hours - $limit->used_hours);
}
```

### 3.3 依頼作成時の残時間チェック

**ファイル**: `app/Services/RequestService.php`

依頼作成時に、残時間が十分かチェックします。

```php
// 利用者の月次限度時間をチェック
$limitService = app(UserMonthlyLimitService::class);
$now = Carbon::now();
$remainingHours = $limitService->getRemainingHours($userId, $now->year, $now->month);

// 依頼時間を計算
$startMinutes = $this->timeToMinutes($data['start_time']);
$endMinutes = $this->timeToMinutes($data['end_time']);
$durationMinutes = $endMinutes - $startMinutes;
$requestHours = round($durationMinutes / 60 * 10) / 10;

if ($remainingHours < $requestHours) {
    throw new \Exception('残時間が不足しています。残時間: ' . $remainingHours . '時間');
}
```

---

## 4. 実績時間の表示

### 4.1 ユーザーの利用時間統計

**APIエンドポイント**: `GET /api/reports/usage-stats`

**ファイル**: `app/Services/DashboardService.php`
**メソッド**: `getUserUsageStats()`

#### 月別集計（過去12ヶ月）

```php
// 月ごとの利用時間（過去12ヶ月）
$monthlyStats = Report::where('user_id', $userId)
    ->whereIn('status', ['admin_approved', 'approved'])
    ->whereNotNull('actual_date')
    ->whereNotNull('actual_start_time')
    ->whereNotNull('actual_end_time')
    ->selectRaw('
        DATE_FORMAT(actual_date, "%Y-%m") as month,
        SUM(TIMESTAMPDIFF(MINUTE, 
            CONCAT(actual_date, " ", actual_start_time), 
            CONCAT(actual_date, " ", actual_end_time)
        )) as total_minutes
    ')
    ->whereRaw('DATE_FORMAT(actual_date, "%Y-%m") >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), "%Y-%m")')
    ->groupBy('month')
    ->orderBy('month', 'desc')
    ->limit(12)
    ->get();
```

#### 当月の利用時間（外出/自宅別）

```php
// 今月の外出/自宅利用時間
$currentMonthStats = Report::where('user_id', $userId)
    ->whereIn('status', ['admin_approved', 'approved'])
    ->whereNotNull('actual_date')
    ->whereNotNull('actual_start_time')
    ->whereNotNull('actual_end_time')
    ->whereYear('actual_date', $targetYear)
    ->whereMonth('actual_date', $targetMonth)
    ->join('requests', 'reports.request_id', '=', 'requests.id')
    ->selectRaw('
        requests.request_type,
        SUM(TIMESTAMPDIFF(MINUTE, 
            CONCAT(reports.actual_date, " ", reports.actual_start_time), 
            CONCAT(reports.actual_date, " ", reports.actual_end_time)
        )) as total_minutes
    ')
    ->groupBy('requests.request_type')
    ->get();
```

### 4.2 ガイドのガイド時間統計

**APIエンドポイント**: `GET /api/reports/guide-stats`

**ファイル**: `app/Services/DashboardService.php`
**メソッド**: `getGuideUsageStats()`

ガイドの場合は、`guide_id` でフィルタリングします。

```php
Report::where('guide_id', $guideId)
    ->whereIn('status', ['admin_approved', 'approved'])
    // ... 以下同様
```

### 4.3 表示画面

- **ダッシュボード**: 月別利用時間カード（過去12ヶ月）
- **プロフィール画面**: 実績時間（報告書確定ベース・月別積算）
- **ユーザーダッシュボード**: 月次限度時間カード（限度時間・使用時間・残時間を表示）

#### ユーザーダッシュボードの月次限度時間カード（2026-01-17 追加）

ユーザーダッシュボードに、当月の月次限度時間を表示するカードが追加されました。

**表示内容**:
- **限度時間**: 管理者が設定した月次限度時間（`limit_hours`）
- **使用時間**: 管理者承認済み報告書の使用時間の合計（`used_hours`）
- **残時間**: 限度時間から使用時間を引いた残り時間（`remaining_hours`）
- **進捗バー**: 使用時間の割合を視覚的に表示

**データ取得**:
- ページ読み込み時に自動的に取得
- 更新ボタンをクリックすることで手動更新も可能
- APIエンドポイント: `GET /api/users/me/monthly-limit?year={year}&month={month}`

**実装ファイル**:
- `resources/views/dashboard.blade.php`: ユーザーダッシュボードの表示
- `app/Http/Controllers/Api/UserController.php`: APIエンドポイント
- `routes/web.php`: セッション認証用のルート
- `routes/api.php`: JWT認証用のルート

---

## 5. CSV出力での利用時間

### 5.1 利用実績CSV

**APIエンドポイント**: `GET /api/admin/usage/csv`

**ファイル**: `app/Services/AdminService.php`
**メソッド**: `getUsageForCsv()`

```php
'duration_minutes' => $start->diffInMinutes($end),
```

CSVには**分単位**で出力されます。

**CSV項目**:
- ID
- 利用日
- 開始時刻
- 終了時刻
- **利用時間(分)**
- ユーザー名
- 受給者証番号
- ガイド名
- 従業員番号
- 依頼タイプ

---

## 6. 重要なポイント

### 6.1 計上される条件

利用時間が計上されるには、以下の条件を満たす必要があります：

1. 報告書のステータスが `admin_approved` または `approved`
2. `actual_date`（実施日）が設定されている
3. `actual_start_time`（開始時刻）が設定されている
4. `actual_end_time`（終了時刻）が設定されている

### 6.2 計上されないケース

以下の場合は利用時間が計上されません：

- 報告書が提出されていない（`draft` ステータス）
- ユーザー承認のみで管理者承認されていない（`user_approved` ステータス）
- 修正依頼中（`revision_requested` ステータス）
- 実施日、開始時刻、終了時刻のいずれかが未設定

### 6.3 時間の精度

- **計算**: 分単位で計算
- **保存**: 時間単位で保存（小数点第1位まで）
- **表示**: 時間単位で表示（小数点第1位まで）
- **CSV出力**: 分単位で出力

### 6.4 月次集計の基準

- **集計基準**: `actual_date`（実施日）の年月で集計
- **集計対象**: `admin_approved` または `approved` ステータスの報告書のみ

---

## 7. 関連ファイル

### 7.1 サービス層

- `app/Services/ReportService.php` - 報告書承認時の利用時間計算
- `app/Services/UserMonthlyLimitService.php` - 月次限度時間の管理
- `app/Services/DashboardService.php` - 利用時間統計の取得
- `app/Services/AdminService.php` - CSV出力用の利用時間計算

### 7.2 コントローラー

- `app/Http/Controllers/Api/ReportController.php` - 利用時間統計API
- `app/Http/Controllers/Api/AdminController.php` - CSV出力API、管理者向け月次限度時間管理API
- `app/Http/Controllers/Api/UserController.php` - ユーザー自身の月次限度時間取得API（2026-01-17 追加）

### 7.3 モデル

- `app/Models/Report.php` - 報告書モデル
- `app/Models/UserMonthlyLimit.php` - 月次限度時間モデル

---

## 8. データフロー図

```
報告書作成（ガイド）
  ↓
actual_date, actual_start_time, actual_end_time を入力
  ↓
報告書提出（status: 'submitted'）
  ↓
ユーザー承認（status: 'user_approved'）
  ↓
管理者承認（status: 'admin_approved'）
  ↓
利用時間を計算（分単位）
  ↓
時間に変換（小数点第1位まで）
  ↓
user_monthly_limits.used_hours に加算
  ↓
ダッシュボード・プロフィール画面で表示
```

---

## 9. 注意事項

1. **実施日の固定**: 報告書の実施日（`actual_date`）は依頼日（`request_date`）から固定され、変更できません。

2. **管理者承認が必要**: 利用時間が計上されるには、管理者承認が必要です。ユーザー承認のみでは計上されません。

3. **月次集計の基準**: 実施日（`actual_date`）の年月で集計されます。報告書の作成日や承認日ではありません。

4. **時間の丸め**: 時間は小数点第1位までに丸められます（例：1.55時間 → 1.6時間）。

5. **翌日跨ぎの処理**: 終了時刻が開始時刻より小さい場合（例：23:00 → 01:00）、自動的に翌日とみなされます。

---

## 10. トラブルシューティング

### 10.1 使用時間が連携されない問題（2026-01-15 修正）

#### 問題の症状
- 管理者承認済みの報告書があるにもかかわらず、管理画面の「使用時間」が 0.0 のまま表示される
- `user_monthly_limits` テーブルの `used_hours` が更新されない

#### 原因
`reports` テーブルのデータ型と、`ReportService::adminApproveReport()` の処理に不整合がありました：

1. **データベースの構造**:
   - `actual_date`: `date` 型（日付のみ）
   - `actual_start_time`: `time` 型（時刻のみ、例: `10:00:00`）
   - `actual_end_time`: `time` 型（時刻のみ、例: `12:00:00`）

2. **問題のあったコード**:
   - `actual_start_time` と `actual_end_time` を直接 `Carbon::parse()` でパースしようとしていた
   - `time` 型のため日付情報が含まれておらず、正しく datetime オブジェクトを作成できていなかった

#### 修正内容

**ファイル**: `app/Services/ReportService.php`
**メソッド**: `adminApproveReport()`

修正前（問題のあったコード）:
```php
$startDateTime = $report->actual_start_time instanceof Carbon
    ? $report->actual_start_time->copy()
    : Carbon::parse($report->actual_start_time);
$endDateTime = $report->actual_end_time instanceof Carbon
    ? $report->actual_end_time->copy()
    : Carbon::parse($report->actual_end_time);
```

修正後:
```php
// actual_date は date型、actual_start_time/actual_end_time は time型なので組み合わせる必要がある
$actualDate = Carbon::parse($report->actual_date);

// actual_start_time と actual_end_time を文字列として取得（time型）
$startTimeStr = is_string($report->actual_start_time) 
    ? $report->actual_start_time 
    : $report->getRawOriginal('actual_start_time');
$endTimeStr = is_string($report->actual_end_time)
    ? $report->actual_end_time
    : $report->getRawOriginal('actual_end_time');

// actual_date と時刻を組み合わせて datetime を作成
$startDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $startTimeStr);
$endDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $endTimeStr);
```

#### 既存データの修正

管理者承認済みの報告書で使用時間が計上されていない場合、手動で修正が必要です。

**修正SQL例**:
```sql
-- 報告書の実際の使用時間を計算（例: 10:00-12:00 = 2時間）
UPDATE user_monthly_limits 
SET used_hours = 2.0 
WHERE user_id = 5 AND year = 2026 AND month = 1;
```

**修正確認**:
```sql
SELECT user_id, year, month, limit_hours, used_hours, 
       (limit_hours - used_hours) as remaining_hours 
FROM user_monthly_limits 
WHERE user_id = 5 
ORDER BY year DESC, month DESC;
```

#### 修正日時
2026年1月15日

#### 影響範囲
- `app/Services/ReportService.php` の `adminApproveReport()` メソッド
- 既存の管理者承認済み報告書で使用時間が未計上の場合、手動修正が必要

#### 今後の対策
- データベーススキーマとコードの整合性を確認する
- `time` 型カラムと `date` 型カラムを組み合わせる際は、明示的に結合する

---

## 11. ユーザー向けAPIエンドポイント（2026-01-17 追加）

### 11.1 ユーザー自身の月次限度時間を取得

**エンドポイント**: `GET /api/users/me/monthly-limit`

**認証**: セッション認証（`web`）またはJWT認証（`api`）の両方をサポート

**パラメータ**:
- `year` (オプション): 年（デフォルト: 当年）
- `month` (オプション): 月（デフォルト: 当月）

**レスポンス例**:
```json
{
  "limit": {
    "id": 1,
    "user_id": 5,
    "year": 2026,
    "month": 1,
    "limit_hours": 10.0,
    "used_hours": 2.0,
    "remaining_hours": 8.0,
    "created_at": "2026-01-15T12:00:00.000000Z",
    "updated_at": "2026-01-17T14:00:00.000000Z"
  }
}
```

### 11.2 ユーザー自身の月次限度時間一覧を取得

**エンドポイント**: `GET /api/users/me/monthly-limits`

**認証**: セッション認証（`web`）またはJWT認証（`api`）の両方をサポート

**パラメータ**:
- `year` (オプション): 年でフィルタリング
- `month` (オプション): 月でフィルタリング（`year`と一緒に使用）

**レスポンス例**:
```json
{
  "limits": [
    {
      "id": 1,
      "user_id": 5,
      "year": 2026,
      "month": 1,
      "limit_hours": 10.0,
      "used_hours": 2.0,
      "remaining_hours": 8.0,
      "created_at": "2026-01-15T12:00:00.000000Z",
      "updated_at": "2026-01-17T14:00:00.000000Z"
    }
  ]
}
```

### 11.3 実装の詳細

**コントローラー**: `app/Http/Controllers/Api/UserController.php`

両方のエンドポイントは、セッション認証（`web`ガード）とJWT認証（`api`ガード）の両方をサポートしています：

```php
// セッション認証（web）とJWT認証（api）の両方をサポート
$user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
```

これにより、Bladeテンプレートからの呼び出し（セッション認証）と、SPAからの呼び出し（JWT認証）の両方で動作します。

**ルート**:
- `routes/web.php`: セッション認証用のルート（`auth`ミドルウェア）
- `routes/api.php`: JWT認証用のルート（`auth:api`ミドルウェア）

---

## 12. 今後の改善案

1. **報告書削除時の処理**: 報告書が削除された場合、使用時間を減算する処理を追加
2. **時間の修正**: 報告書の時間を修正した場合、差分を反映する処理を追加
3. **集計期間の拡張**: 過去12ヶ月以外の期間でも集計可能にする
4. **詳細ログ**: 利用時間の計上履歴をログとして記録
5. **リアルタイム更新**: 管理者が限度時間を更新した際に、ユーザー側の表示を自動更新（WebSocketやServer-Sent Eventsの活用）

