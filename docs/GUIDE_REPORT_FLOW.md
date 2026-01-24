# ガイドの報告書作成UIとマッチング終了後のフロー

## 概要

本ドキュメントでは、ガイドが報告書を作成するUIと、マッチング終了後の一連のフローについて説明します。

---

## 1. ガイドの報告書作成UI

### 1.1 報告書作成へのアクセス方法

#### ダッシュボードからのアクセス

**ファイル**: `resources/views/dashboard.blade.php`

ガイドのダッシュボードには、以下の2つのクイックアクションボタンが表示されます：

1. **依頼一覧ボタン**
   - リンク: `/guide/requests`
   - 利用可能な依頼数をバッジで表示

2. **報告書を作成ボタン**（進行中のマッチングがある場合のみ表示）
   - リンク: `/guide/reports/new/{matchingId}`
   - 最初の進行中マッチング（`activeMatchings[0]`）のIDを使用
   - 進行中のマッチングがない場合は非表示

```blade
<template x-if="activeMatchings.length > 0">
    <a :href="`{{ url('/guide/reports/new') }}/${activeMatchings[0].id}`" class="quick-action-btn primary">
        <svg>...</svg>
        <span>報告書を作成</span>
    </a>
</template>
```

#### ルーティング

**ファイル**: `routes/web.php`

```php
Route::get('/guide/reports/new/{matchingId}', [\App\Http\Controllers\Guide\ReportController::class, 'create'])
    ->name('guide.reports.create');
```

### 1.2 報告書作成フォーム

**ファイル**: `resources/views/guide/reports/create.blade.php`

#### フォーム項目

1. **サービス内容**（テキストエリア）
   - 必須: いいえ
   - 初期値: 依頼時のサービス内容（自動入力）
   - プレースホルダー: "実施したサービス内容を記入してください"

2. **実施日**（日付入力）
   - 必須: いいえ（自動設定）
   - **変更不可**: `readonly` と `disabled` 属性が設定されている
   - 初期値: 依頼日（`request_date`）から自動設定
   - 説明文: "実施日は依頼日に基づいて自動設定され、変更できません"

3. **開始時刻**（時刻入力）
   - 必須: いいえ
   - 形式: `HH:MM`

4. **終了時刻**（時刻入力）
   - 必須: いいえ
   - 形式: `HH:MM`

5. **報告欄（自由記入）**（テキストエリア）
   - 必須: いいえ
   - プレースホルダー: "実施内容の詳細、気づいた点、改善点などを自由に記入してください"

#### 操作ボタン

1. **下書き保存ボタン**
   - アクション: 報告書を下書きとして保存（`status: 'draft'`）
   - 処理後: ダッシュボードにリダイレクト

2. **報告書を提出ボタン**
   - アクション: 報告書を提出（`status: 'submitted'`）
   - 確認ダイアログ: "報告書を提出しますか？提出後はユーザーの承認が必要です。"
   - 処理後: ダッシュボードにリダイレクト

#### 既存報告書の編集

既存の報告書がある場合（`existingReport` が存在する場合）：
- フォームタイトル: "報告書編集"
- 既存のデータが自動入力される
- 承認済み（`admin_approved` または `approved`）の場合は編集不可

### 1.3 コントローラー処理

**ファイル**: `app/Http/Controllers/Guide/ReportController.php`

#### `create()` メソッド

```php
public function create($matchingId)
{
    $guide = Auth::user();
    $matchings = $this->matchingService->getUserMatchings($guide->id);
    $matching = $matchings->firstWhere('id', $matchingId);
    
    if (!$matching) {
        return redirect()->route('dashboard')
            ->with('error', 'マッチングが見つかりません');
    }

    // 既存の報告書があるか確認
    $existingReport = \App\Models\Report::where('matching_id', $matchingId)->first();
    
    return view('guide.reports.create', [
        'matchingId' => $matchingId,
        'matching' => $matching,
        'existingReport' => $existingReport,
    ]);
}
```

#### `store()` メソッド（下書き保存）

```php
public function store(Request $request)
{
    $request->validate([
        'matching_id' => 'required|integer|exists:matchings,id',
        'service_content' => 'nullable|string',
        'report_content' => 'nullable|string',
        'actual_date' => 'nullable|date',
        'actual_start_time' => 'nullable|date_format:H:i',
        'actual_end_time' => 'nullable|date_format:H:i',
    ]);

    try {
        $this->reportService->createOrUpdateReport($request->all(), Auth::id());
        return redirect()->route('dashboard')
            ->with('success', '報告書が保存されました');
    } catch (\Exception $e) {
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput();
    }
}
```

#### `submit()` メソッド（提出）

```php
public function submit($id)
{
    try {
        $this->reportService->submitReport($id, Auth::id());
        return redirect()->route('dashboard')
            ->with('success', '報告書が提出されました');
    } catch (\Exception $e) {
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()]);
    }
}
```

---

## 2. マッチング終了後のフロー

### 2.1 フロー全体図

```
マッチング成立（matched）
  ↓
チャット利用可能（マッチング成立～報告書完了まで）
  ↓
ガイドが報告書を作成・提出（submitted）
  ↓
ユーザー承認（user_approved）
  ↓
管理者承認（admin_approved）
  ↓
利用時間計上
  ↓
マッチングのreport_completed_at更新
  ↓
チャット利用不可
```

### 2.2 各ステップの詳細

#### ステップ1: マッチング成立

**ファイル**: `app/Services/MatchingService.php`
**メソッド**: `createMatching()`

- マッチングステータス: `matched`
- 依頼ステータス: `matched`
- ユーザーとガイドに通知を送信
- メール通知を送信

```php
$matching = Matching::create([
    'request_id' => $requestId,
    'user_id' => $userId,
    'guide_id' => $guideId,
    'status' => 'matched',
]);
```

#### ステップ2: チャット利用可能期間

**ファイル**: `app/Services/ChatService.php`
**メソッド**: `checkChatAvailability()`

チャットは以下の期間のみ利用可能：
- **開始**: マッチング成立時（`matched` ステータス）
- **終了**: 報告書完了時（`report_completed_at` が設定されている、または `admin_approved` ステータス）

```php
// マッチングが成立していない場合はチャット不可
if ($matching->status === 'cancelled') {
    throw new \Exception('このマッチングはキャンセルされています。チャットは利用できません。');
}

// 報告書が完了（管理者承認済み）している場合はチャット不可
$report = Report::where('matching_id', $matching->id)
    ->whereIn('status', ['admin_approved', 'approved'])
    ->first();

if ($report) {
    throw new \Exception('報告書が承認済みのため、チャットは利用できません。');
}
```

#### ステップ3: ガイドが報告書を作成・提出

**ファイル**: `app/Services/ReportService.php`
**メソッド**: `createOrUpdateReport()`, `submitReport()`

- 報告書ステータス: `draft` → `submitted`
- 実施日は依頼日から自動設定（変更不可）
- ユーザーに通知を送信
- メール通知を送信

```php
// 報告書を提出状態に更新
$report->update([
    'status' => 'submitted',
    'submitted_at' => now(),
]);

// ユーザーに通知
Notification::create([
    'user_id' => $report->user_id,
    'type' => 'report',
    'title' => '報告書が提出されました',
    'message' => 'ガイドから報告書が提出されました。承認または修正依頼を行ってください。',
    'related_id' => $reportId,
]);
```

#### ステップ4: ユーザー承認（第1段階）

**ファイル**: `app/Services/ReportService.php`
**メソッド**: `approveReport()`

- 報告書ステータス: `submitted` → `user_approved`
- ガイドに通知を送信
- 管理者に通知を送信
- メール通知を送信

```php
// ユーザー承認（第1段階）
$report->update([
    'status' => 'user_approved',
    'user_approved_at' => now(),
]);
```

#### ステップ5: 管理者承認（第2段階）

**ファイル**: `app/Services/ReportService.php`
**メソッド**: `adminApproveReport()`

- 報告書ステータス: `user_approved` → `admin_approved`
- **利用時間を計上**（`UserMonthlyLimitService::addUsedHours()`）
- マッチングの`report_completed_at`を更新
- ユーザーとガイドに通知を送信
- メール通知を送信

```php
// 管理者承認（第2段階）
$report->update([
    'status' => 'admin_approved',
    'admin_approved_at' => now(),
    'approved_at' => now(), // 後方互換性のため
]);

// マッチングのreport_completed_atを更新（チャット利用終了日）
$matching = \App\Models\Matching::find($report->matching_id);
if ($matching && !$matching->report_completed_at) {
    $matching->update(['report_completed_at' => now()]);
}

// 利用者の限度時間を更新（報告書確定時に自動更新）
if ($report->actual_date && $report->actual_start_time && $report->actual_end_time) {
    // 実施時間を計算（分単位）
    $startDateTime = Carbon::parse($report->actual_date . ' ' . $report->actual_start_time);
    $endDateTime = Carbon::parse($report->actual_date . ' ' . $report->actual_end_time);
    
    // 終了時刻が開始時刻より小さい場合、翌日とみなす
    if ($endDateTime->lt($startDateTime)) {
        $endDateTime->addDay();
    }
    
    $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
    $usedHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
    
    // 実施日から年月を取得
    $actualDate = Carbon::parse($report->actual_date);
    $year = $actualDate->year;
    $month = $actualDate->month;
    
    // 使用時間を追加
    $this->limitService->addUsedHours($report->user_id, $usedHours, $year, $month);
}
```

#### ステップ6: チャット利用不可

管理者承認後、`report_completed_at`が設定されると、チャットは利用不可になります。

---

## 3. ダッシュボードでの表示

### 3.1 ガイドダッシュボード

**ファイル**: `resources/views/dashboard.blade.php`

#### 進行中のマッチング一覧

- ステータス: `matched` または `in_progress`
- 各マッチングに「チャット」と「詳細」ボタンが表示
- 報告書未作成の場合は「報告書を作成」ボタンが表示

#### 統計情報

- **要報告書**: 下書きまたは修正依頼中の報告書数（`stats.pendingReports`）

### 3.2 ユーザーダッシュボード

#### 承認待ち報告書

- ステータス: `submitted`（ユーザー承認待ち）
- 各報告書に「承認」または「修正依頼」ボタンが表示

---

## 4. 重要なポイント

### 4.1 報告書の作成タイミング

- マッチング成立後、いつでも報告書を作成可能
- ただし、報告書が提出済み（`submitted`）または承認済み（`admin_approved`）の場合は編集不可

### 4.2 実施日の固定

- 実施日（`actual_date`）は依頼日（`request_date`）から自動設定され、変更不可
- これは要件定義書で「日付変更不可」と定められているため

### 4.3 2段階承認フロー

- **第1段階（ユーザー承認）**: 報告書が提出された後、ユーザーが承認
- **第2段階（管理者承認）**: ユーザー承認後、管理者が承認
- **利用時間の計上**: 管理者承認時のみ計上される

### 4.4 チャット利用期間

- **開始**: マッチング成立時
- **終了**: 管理者承認時（`report_completed_at` が設定される）
- この期間外はチャット不可（過去のメッセージは閲覧可能）

---

## 5. エラーハンドリング

### 5.1 マッチングが見つからない場合

```php
if (!$matching) {
    return redirect()->route('dashboard')
        ->with('error', 'マッチングが見つかりません');
}
```

### 5.2 既に承認済みの報告書の場合

```php
if ($existingReport->status === 'admin_approved' || $existingReport->status === 'approved') {
    throw new \Exception('既に承認済みの報告書です');
}
```

### 5.3 報告書提出時のエラー

- バリデーションエラー: フォームにエラーメッセージを表示
- サーバーエラー: エラーメッセージを表示し、フォームに戻る

---

## 6. 関連ファイル

### 6.1 ビュー

- `resources/views/guide/reports/create.blade.php` - 報告書作成フォーム
- `resources/views/dashboard.blade.php` - ダッシュボード（クイックアクション）

### 6.2 コントローラー

- `app/Http/Controllers/Guide/ReportController.php` - 報告書作成・提出処理

### 6.3 サービス

- `app/Services/ReportService.php` - 報告書のビジネスロジック
- `app/Services/MatchingService.php` - マッチング管理
- `app/Services/ChatService.php` - チャット利用期間チェック
- `app/Services/UserMonthlyLimitService.php` - 利用時間計上

### 6.4 ルーティング

- `routes/web.php` - Webルート定義

---

## 7. 今後の改善案

1. **報告書作成の促し**: マッチング成立後、一定期間経過したら通知を送信
2. **下書きの自動保存**: 一定時間ごとに自動保存機能を追加
3. **報告書テンプレート**: よく使う内容をテンプレートとして保存
4. **画像添付**: 報告書に画像を添付できる機能
5. **報告書一覧画面**: ガイドが過去の報告書を一覧で確認できる画面

