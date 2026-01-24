# トラブルシューティングガイド

## 目次
1. [認証エラー](#認証エラー)
2. [チャットメッセージの配置問題](#チャットメッセージの配置問題)
3. [報告書提出時の401エラー](#報告書提出時の401エラー)
4. [admin_operation_logsテーブルのupdated_atカラムエラー](#admin_operation_logsテーブルのupdated_atカラムエラー)
5. [マッチング詳細ページで「マッチングが見つかりません」エラー](#マッチング詳細ページでマッチングが見つかりませんエラー)
6. [プロフィール編集画面での統計API 404エラー](#プロフィール編集画面での統計api-404エラー)

---

## 認証エラー

### 問題: JWTトークンの不一致によるsender_idの誤認

#### 症状
- チャットで送信したメッセージが全て相手側（左側）に表示される
- `sender_id` が常に同じ値（例：5）になる
- `isOwnMessage` が常に `false` を返す

#### 原因
`ChatController::show()` で現在ログイン中のユーザーのJWTトークンを生成・渡していなかったため、BladeテンプレートのAlpine.jsが古いトークン（`localStorage`に残っていたもの）を使用していた。

#### 解決方法

**ファイル**: `app/Http/Controllers/ChatController.php`

```php
public function show($matchingId)
{
    $user = Auth::user();
    $matching = Matching::where('id', $matchingId)
        ->where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('guide_id', $user->id);
        })
        ->firstOrFail();

    // JWTトークンを生成してBladeテンプレートに渡す
    $jwt = JWTAuth::fromUser($user);
    
    return view('chat.show', [
        'matching' => $matching,
        'jwt_token' => $jwt, // 追加
    ]);
}
```

**ファイル**: `resources/views/chat/show.blade.php`

```javascript
init() {
    // JWTトークンをlocalStorageに保存
    const jwtToken = @json($jwt_token);
    if (jwtToken) {
        localStorage.setItem('token', jwtToken);
    }
    
    this.userId = {{ auth()->id() }}; // parseIntは不要（既に数値）
    // ...
}
```

#### 確認方法
1. ブラウザの開発者ツールで `localStorage.getItem('token')` を確認
2. トークンをデコードして、現在ログイン中のユーザーIDと一致するか確認
3. チャット画面でメッセージを送信し、正しく自分のメッセージが右側に表示されるか確認

---

## チャットメッセージの配置問題

### 問題: メッセージが全て左側（または右側）に表示される

#### 症状
- 自分のメッセージも相手のメッセージも同じ側に表示される
- `align-self: flex-end` が効かない

#### 原因
Alpine.jsの `x-for` ディレクティブが生成する `<div>` ラッパーが、`.message-wrapper` と `.chat-messages` の間に存在していたため、`align-self` が正しく機能しなかった。

#### 解決方法

**ファイル**: `resources/views/chat/show.blade.php`

```blade
<template x-for="message in messages" :key="message.id">
    <div style="display: contents;">  <!-- 追加: display: contents; -->
        <div class="message-wrapper" 
             :data-own-message="isOwnMessage(message)"
             :data-sender-id="message.sender_id"
             :class="isOwnMessage(message) ? 'own-message' : 'other-message'">
            <!-- メッセージ内容 -->
        </div>
    </div>
</template>
```

**ファイル**: `resources/css/Chat.scss`

```scss
.chat-messages {
    display: flex;
    flex-direction: column;
    
    // x-forで生成されるラッパーを無視
    > div {
        display: contents;
    }
    
    .message-wrapper {
        width: 100%;
        
        &[data-own-message="true"] {
            align-self: flex-end !important;
        }
        
        &[data-own-message="false"] {
            align-self: flex-start !important;
        }
    }
}
```

---

## 報告書提出時の401エラー

### 問題: `POST /guide/reports 401 (Unauthorized)` または `POST /guide/reports/{id}/submit 401 (Unauthorized)`

#### 症状
- ガイドが報告書を保存・提出しようとすると401エラーが発生
- ブラウザのコンソールに `401 (Unauthorized)` が表示される

#### 原因
fetch APIを使用する際、セッションクッキーが自動的に送信されないため、Laravelのセッション認証が機能していない。

#### 解決方法

**ファイル**: `resources/views/guide/reports/create.blade.php`

fetch APIの呼び出しに `credentials: 'same-origin'` を追加：

```javascript
const response = await fetch('{{ route("guide.reports.store") }}', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin', // セッションクッキーを送信
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});
```

**同様に提出時も修正**：

```javascript
const response = await fetch(`/guide/reports/${reportId}/submit`, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin', // セッションクッキーを送信
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});
```

#### 追加の修正: 報告書IDの取得

新規作成の場合、保存後に報告書IDを取得してから提出する必要があります：

```javascript
async handleSubmit() {
    // まず保存（新規作成の場合は報告書IDを取得）
    const saveResult = await this.handleSave();
    
    // 報告書IDを取得
    let reportId;
    if (this.existingReport && this.existingReport.id) {
        reportId = this.existingReport.id;
    } else if (saveResult && saveResult.report_id) {
        reportId = saveResult.report_id;
    } else {
        throw new Error('報告書IDが取得できませんでした');
    }
    
    // その後提出
    // ...
}
```

**コントローラー側の修正**：

**ファイル**: `app/Http/Controllers/Guide/ReportController.php`

```php
public function store(Request $request)
{
    // ... バリデーション ...
    
    try {
        $report = $this->reportService->createOrUpdateReport($request->all(), Auth::id());
        
        // AJAXリクエストの場合はJSONを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => '報告書が保存されました',
                'report_id' => $report->id  // 報告書IDを返す
            ]);
        }
        
        return redirect()->route('dashboard')
            ->with('success', '報告書が保存されました');
    } catch (\Exception $e) {
        // AJAXリクエストの場合はJSONを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
        
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()])
            ->withInput();
    }
}

public function submit(Request $request, $id)
{
    try {
        $this->reportService->submitReport($id, Auth::id());
        
        // AJAXリクエストの場合はJSONを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => '報告書が提出されました'
            ]);
        }
        
        return redirect()->route('dashboard')
            ->with('success', '報告書が提出されました');
    } catch (\Exception $e) {
        // AJAXリクエストの場合はJSONを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
        
        return redirect()->back()
            ->withErrors(['error' => $e->getMessage()]);
    }
}
```

#### 確認方法
1. ブラウザの開発者ツールのNetworkタブで、リクエストヘッダーに `Cookie` が含まれているか確認
2. レスポンスが200 OKになるか確認
3. 報告書が正しく保存・提出されるか確認

---

## admin_operation_logsテーブルのupdated_atカラムエラー

### 問題: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'updated_at' in 'field list'`

#### 症状
- ガイドの承認/拒否時にSQLエラーが発生
- `admin_operation_logs` テーブルに `updated_at` カラムが存在しない

#### 原因
`admin_operation_logs` テーブルの作成時に `updated_at` カラムが定義されていなかったが、LaravelのEloquent ORMはデフォルトで `updated_at` を管理しようとする。

#### 解決方法

**オプション1: テーブルにカラムを追加（推奨）**

```sql
ALTER TABLE admin_operation_logs 
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL 
ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;
```

**オプション2: モデルでupdated_atを無効化**

**ファイル**: `app/Models/AdminOperationLog.php`

```php
public $timestamps = true;

const UPDATED_AT = null; // updated_atを無効化
```

**推奨**: オプション1を採用し、テーブルにカラムを追加する方が、Laravelの標準的な動作と一致します。

#### 確認方法
1. データベースで `DESCRIBE admin_operation_logs;` を実行し、`updated_at` カラムが存在するか確認
2. ガイドの承認/拒否操作を実行し、エラーが発生しないか確認
3. `admin_operation_logs` テーブルにレコードが正しく挿入されるか確認

---

## その他のよくある問題

### CSRFトークンの問題

#### 症状
- 419エラーが発生する
- フォーム送信が失敗する

#### 解決方法
1. Bladeテンプレートで `{{ csrf_token() }}` が正しく出力されているか確認
2. フォームに `<input type="hidden" name="_token" value="{{ csrf_token() }}">` が含まれているか確認
3. fetch APIを使用する場合、`FormData` に `_token` を追加：

```javascript
formData.append('_token', '{{ csrf_token() }}');
```

### セッションの有効期限切れ

#### 症状
- ログイン後、しばらく操作すると認証エラーが発生する

#### 解決方法
1. `config/session.php` で `lifetime` を確認・調整
2. 必要に応じて、セッションの有効期限を延長

---

-」--

## ガイドの依頼承諾時の400エラー

### 問題: `POST /api/matchings/accept 400 (Bad Request)`

#### 症状
- ガイドが依頼に応募しようとすると400エラーが発生
- ブラウザのコンソールに `400 (Bad Request)` が表示される
- エラーメッセージが表示されない場合がある

#### 原因
`routes/api.php` の `/api/matchings/accept` ルートが `auth:api`（JWT認証のみ）ミドルウェアを使用していたため、セッション認証を使用しているBladeテンプレートからの呼び出しで認証が失敗していた。

また、`MatchingController::accept()` メソッドが `Auth::id()` のみを使用しており、セッション認証を考慮していなかった。

#### 解決方法

**1. コントローラーの修正**

**ファイル**: `app/Http/Controllers/Api/MatchingController.php`

セッション認証とJWT認証の両方をサポートするように修正：

```php
public function accept(Request $request)
{
    $request->validate([
        'request_id' => 'required|integer|exists:requests,id',
    ]);

    try {
        // セッション認証（web）とJWT認証（api）の両方をサポート
        $guideId = Auth::guard('web')->id() ?? Auth::guard('api')->id();
        
        if (!$guideId) {
            \Log::warning('MatchingController::accept - 認証ユーザーが見つかりません');
            return response()->json(['error' => '認証が必要です'], 401);
        }
        
        $result = $this->matchingService->acceptRequest(
            $request->input('request_id'),
            $guideId
        );
        
        return response()->json($result);
    } catch (\Exception $e) {
        \Log::error('MatchingController::accept - エラー', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

**2. Webルートに追加**

**ファイル**: `routes/web.php`

セッション認証用のルートを追加：

```php
Route::middleware(['auth'])->group(function () {
    // ... 既存のルート ...
    
    // マッチング関連（セッション認証用）
    Route::post('/api/matchings/accept', [\App\Http\Controllers\Api\MatchingController::class, 'accept']);
    Route::post('/api/matchings/decline', [\App\Http\Controllers\Api\MatchingController::class, 'decline']);
});
```

**3. フロントエンドのエラーハンドリング改善**

**ファイル**: `resources/views/guide/requests/index.blade.php`

エラーレスポンスの詳細を表示するように改善：

```javascript
const response = await fetch('/api/matchings/accept', {
    method: 'POST',
    headers: headers,
    credentials: 'same-origin',
    body: JSON.stringify({ request_id: requestId })
});

const responseData = await response.json().catch(() => ({}));

if (response.ok) {
    alert(responseData.message || '依頼に応募しました');
    this.fetchRequests();
} else {
    console.error('応募エラー詳細:', {
        status: response.status,
        statusText: response.statusText,
        error: responseData
    });
    alert(responseData.error || responseData.message || '応募に失敗しました');
}
```

#### 確認方法
1. ブラウザの開発者ツールのNetworkタブで、リクエストヘッダーに `Cookie` が含まれているか確認
2. レスポンスが200 OKになるか確認
3. ガイドが依頼に応募できるか確認
4. ログファイル（`storage/logs/laravel.log`）でエラーメッセージを確認

#### 補足
- `routes/api.php` のルートはJWT認証用として残しておく（SPAからの呼び出しなど）
- `routes/web.php` のルートはセッション認証用として追加（Bladeテンプレートからの呼び出し）
- 両方のルートが同じコントローラーメソッドを使用することで、コードの重複を避ける

---

### 10.4 ガイドの依頼応募後のボタン無効化が機能しない (2026-01-17 修正)

#### 問題の症状
- ガイドが依頼に応募（承諾）した後、依頼一覧画面で「承諾」ボタンが「応募済み」に変わらず、無効化されない。
- ブラウザのコンソールで `has_applied` が `false` のままになっている。
- ステータスバッジは「応募済み」または「承認待ち」と表示されるが、ボタンが無効化されない。

#### 原因
1. **APIルートの認証問題**: `/api/requests/guide/available` エンドポイントが `routes/api.php` の `auth:api` ミドルウェア（JWT認証）で保護されており、BladeテンプレートからのAjaxリクエストがセッション認証でアクセスできない。
2. **データのシリアライズ問題**: `RequestService::getAvailableRequestsForGuide()` で Eloquentモデルに動的に追加したプロパティ（`has_applied`, `acceptance_status`, `display_status`）が、JSONレスポンスに正しく含まれない場合があった。
3. **フロントエンドのバインディング問題**: Alpine.jsの `x-bind:disabled` や条件付き `@click` が、動的に追加されたプロパティに対して正しく動作しない場合があった。

#### 解決方法

1. **`routes/web.php` にセッション認証用のルートを追加**
   - BladeテンプレートからのAjaxリクエストがセッション認証で処理されるように、`auth` ミドルウェアグループ内に `/api/requests/guide/available` ルートを追加しました。

   **ファイル**: `routes/web.php`

   ```php
   Route::middleware(['auth'])->group(function () {
       // ... 既存のルート ...

       // 依頼関連（セッション認証用）
       Route::get('/api/requests/guide/available', [\App\Http\Controllers\Api\RequestController::class, 'availableForGuide']);
   });
   ```

2. **`RequestController::availableForGuide()` で両方の認証ガードをサポート**
   - `Auth::guard('web')->user() ?? Auth::guard('api')->user()` を使用して、セッション認証とJWT認証のどちらかでユーザーが認証されているかを確認するように変更しました。

   **ファイル**: `app/Http/Controllers/Api/RequestController.php`

   ```php
   public function availableForGuide()
   {
       // セッション認証（web）とJWT認証（api）の両方をサポート
       $guide = Auth::guard('web')->user() ?? Auth::guard('api')->user();
       
       if (!$guide) {
           return response()->json(['error' => '認証が必要です'], 401);
       }
       
       $requests = $this->requestService->getAvailableRequestsForGuide($guide->id);
       
       // request_typeを日本語に変換（stdClassオブジェクトのプロパティを更新）
       $requests = $requests->map(function ($request) {
           $requestTypeMap = [
               'outing' => '外出',
               'home' => '自宅',
           ];
           // stdClassオブジェクトのプロパティを更新
           if (isset($request->request_type)) {
               $request->request_type = $requestTypeMap[$request->request_type] ?? $request->request_type;
           }
           return $request;
       });
       
       return response()->json(['requests' => $requests]);
   }
   ```

3. **`RequestService::getAvailableRequestsForGuide()` でプロパティの追加方法を改善**
   - Eloquentモデルを `toArray()` で配列に変換してから動的プロパティを追加し、`stdClass` オブジェクトに変換して返すように変更しました。これにより、JSONシリアライズ時にプロパティが確実に含まれるようになります。

   **ファイル**: `app/Services/RequestService.php`

   ```php
   // 各依頼に応募済み情報を追加
   return $requests->map(function ($request) use ($guideId, $acceptances, $autoMatching) {
       $hasApplied = isset($acceptances[$request->id]);
       $acceptanceStatus = $acceptances[$request->id] ?? null;
       
       // 応募済みで自動マッチングが無効の場合、ステータス表示を「承認待ち」に
       if ($hasApplied && $acceptanceStatus === 'pending' && !$autoMatching) {
           $displayStatus = 'approval_pending'; // 承認待ち
       } else {
           $displayStatus = $request->status;
       }
       
       // 配列に変換して動的プロパティを追加（JSONシリアライズ時に含まれるようにする）
       $requestArray = $request->toArray();
       $requestArray['has_applied'] = $hasApplied;
       $requestArray['acceptance_status'] = $acceptanceStatus;
       $requestArray['display_status'] = $displayStatus;
       
       // オブジェクトとして返すために stdClass に変換
       return (object) $requestArray;
   });
   ```

4. **フロントエンドで `template x-if` を使用してボタンを切り替え**
   - Alpine.jsの `x-bind:disabled` や条件付き `@click` の代わりに、`template x-if` を使用して応募済み/未応募で完全に異なるボタンを表示するように変更しました。これにより、`disabled` 属性のバインディング問題を回避できます。

   **ファイル**: `resources/views/guide/requests/index.blade.php`

   ```html
   <template x-if="!request.has_applied">
       <button
           @click="handleAccept(request.id)"
           class="btn-primary"
           aria-label="依頼を承諾"
       >
           承諾
       </button>
   </template>
   <template x-if="request.has_applied">
       <button
           class="btn-primary btn-disabled"
           disabled
           aria-label="応募済み"
       >
           応募済み
       </button>
   </template>
   ```

#### 確認方法
1. ガイドアカウントでログインし、依頼一覧ページにアクセスします。
2. 未応募の依頼に対して「承諾」ボタンをクリックします。
3. 成功メッセージが表示され、ボタンが「応募済み」に変わり、無効化されることを確認します。
4. ブラウザのコンソールで `has_applied: true` が表示されることを確認します。
5. 自動マッチングが無効の場合、ステータスバッジが「承認待ち」と表示されることを確認します。
6. 開発者ツールのNetworkタブで、`GET /api/requests/guide/available` リクエストのレスポンスに `has_applied: true` が含まれていることを確認します。

#### 修正日時
2026年1月17日

#### 影響範囲
- `app/Http/Controllers/Api/RequestController.php`
- `app/Services/RequestService.php`
- `routes/api.php` (既存のJWTルート)
- `routes/web.php` (新規追加のセッションルート)
- `resources/views/guide/requests/index.blade.php` (フロントエンドのUIロジック)

#### 今後の対策
- APIエンドポイントの認証要件を明確にし、フロントエンドからの呼び出し方法と整合性を保つ。
- Eloquentモデルに動的に追加するプロパティは、JSONシリアライズ時に確実に含まれるように配列に変換してから追加する。
- Alpine.jsのバインディングが期待通りに動作しない場合は、`template x-if` を使って条件分岐する方法を検討する。

---

## マッチング詳細ページで「マッチングが見つかりません」エラー

### 問題: ガイドダッシュボードからマッチング詳細ページにアクセスするとマッチングが見つからない

#### 症状
- ガイドダッシュボードのマッチング一覧から「詳細」ボタンをクリックすると、「マッチングが見つかりません」エラーが表示される
- `dump($matchings)` などのデバッグコードがある場合のみ正常に表示される
- `firstWhere()` メソッドでマッチングを検索できない

#### 原因
1. **Eloquentの遅延評価（Lazy Evaluation）の問題**:
   - `getUserMatchings()` メソッドで `get()` を呼び出した後、`map()` 内でリレーション（`$matching->request`、`$matching->user`、`$matching->guide`）にアクセスしている
   - `with()` でリレーションを指定していても、実際にアクセスされるまでロードされない
   - `dump()` がコレクションを評価するため、リレーションがロードされていた

2. **配列とコレクションの混在**:
   - `getUserMatchings()` が配列を返すように修正したが、コレクションを評価する前に `map()` を呼び出していた
   - リレーションがロードされる前に `map()` 内でアクセスしようとしていた

3. **IDの型不一致**:
   - ルートパラメータの `$id` が文字列型で、配列内の `id` が整数型のため、`firstWhere()` で一致しない場合があった

#### 解決方法

**ファイル**: `app/Services/MatchingService.php`

```php
public function getUserMatchings(int $userId)
{
    $matchings = Matching::where('user_id', $userId)
        ->orWhere('guide_id', $userId)
        ->with(['request', 'user', 'guide'])
        ->orderBy('matched_at', 'desc')
        ->get();
    
    // コレクションを強制的に評価してリレーションをロード
    $matchings->each(function ($matching) {
        $matching->request;
        $matching->user;
        $matching->guide;
    });
    
    return $matchings->map(function ($matching) use ($userId) {
        // ... マッピング処理 ...
    })
    ->values()
    ->toArray();
}
```

**ファイル**: `app/Http/Controllers/MatchingController.php`

```php
public function show($id)
{
    try {
        $matchings = $this->matchingService->getUserMatchings(Auth::id());
        $matchingId = (int) $id; // 型を統一
        $matching = collect($matchings)->firstWhere('id', $matchingId);
        
        if (!$matching) {
            // エラーハンドリング
        }
        
        return view('matchings.show', [
            'id' => $id,
            'matching' => $matching,
        ]);
    } catch (\Exception $e) {
        // エラーハンドリング
    }
}
```

**ファイル**: `app/Http/Controllers/Guide/ReportController.php`

同様に、`$matchingId` を整数にキャストしてから `firstWhere()` を使用する。

#### 修正箇所
- `app/Services/MatchingService.php`: `getUserMatchings()` メソッド
- `app/Http/Controllers/MatchingController.php`: `show()` メソッド
- `app/Http/Controllers/Guide/ReportController.php`: `create()` メソッド

#### 学んだこと
- Eloquentの遅延評価を理解し、リレーションにアクセスする前にコレクションを評価する必要がある
- `dump()` や `dd()` などのデバッグコードが評価を強制するため、動作が変わる可能性がある
- 型の一致に注意し、ルートパラメータは必要に応じて型キャストする
- `with()` でリレーションを指定しても、実際にアクセスされるまでロードされない場合がある

#### 今後の対策
- リレーションをロードした後、`each()` などで明示的にコレクションを評価する
- `map()` 内でリレーションにアクセスする前に、リレーションがロードされていることを確認する
- `firstWhere()` などの検索メソッドを使用する際は、IDの型を統一する
- デバッグコードが評価を強制する可能性を考慮し、本番環境でも動作するか確認する

---

---

## プロフィール編集画面での統計API 404エラー

### 問題: `GET /api/reports/usage-stats 404 (Not Found)`

#### 症状
- プロフィール編集画面（`/profile`）にアクセスすると、ブラウザのコンソールに404エラーが表示される
- `GET http://127.0.0.1:8000/api/reports/usage-stats 404 (Not Found)` が発生する
- エラーは表示されるが、プロフィール編集機能自体は正常に動作する

#### 原因
プロフィール編集画面で、ユーザー/ガイドの実績時間統計情報を取得するために`fetchUsageStats()`が呼び出されていましたが、**管理者の場合でもこの関数が呼び出されていた**ことが原因です。

統計情報の表示は非管理者のユーザー/ガイドのみで、管理者のプロフィール編集画面には統計情報が表示されません。しかし、コードでは管理者の場合でも`fetchUsageStats()`が実行され、存在しないエンドポイント（管理者用の統計APIは存在しない）にアクセスしようとして404エラーが発生していました。

#### 解決方法

**1. React版（`Profile.jsx`）の修正**

**ファイル**: `frontend/src/pages/Profile.jsx`

管理者の場合は`fetchUsageStats()`を呼び出さないように条件を追加：

```javascript
useEffect(() => {
  if (!user || isAdmin) return; // 管理者の場合は統計情報を取得しない
  fetchUsageStats();
}, [user, isAdmin]);
```

**2. Blade版（`profile.blade.php`）の修正**

**ファイル**: `resources/views/profile.blade.php`

管理者の場合は`fetchUsageStats()`を呼び出さないようにBladeの条件分岐を追加：

```javascript
init() {
    // サーバーから渡されたJWTトークンをlocalStorageに保存
    @if(isset($jwt_token) && $jwt_token)
        localStorage.setItem('token', '{{ $jwt_token }}');
    @endif
    // 実績時間を取得（管理者の場合は不要）
    @if(!$user->isAdmin())
        this.fetchUsageStats();
    @endif
},
```

#### 確認方法
1. 管理者アカウントでログインし、プロフィール編集画面（`/profile`）にアクセス
2. ブラウザの開発者ツールのConsoleタブを確認し、404エラーが表示されないことを確認
3. Networkタブで `/api/reports/usage-stats` へのリクエストが送信されないことを確認
4. 非管理者のユーザー/ガイドでログインし、統計情報が正しく表示されることを確認

#### 修正日時
2026年1月

#### 影響範囲
- `frontend/src/pages/Profile.jsx` (React版のプロフィール編集画面)
- `resources/views/profile.blade.php` (Blade版のプロフィール編集画面)

#### 学んだこと
- API呼び出しは、実際にそのデータが必要な場合にのみ実行する
- ユーザーのロールに応じて、不要なAPI呼び出しを避ける
- フロントエンドで条件分岐を行う際は、バックエンドのAPIエンドポイントの存在も考慮する

---

## メール通知が届かない

### 問題: マッチング成立時にメールが届かない

メール通知の確認方法とトラブルシューティングについては、[EMAIL_TESTING.md](./EMAIL_TESTING.md)を参照してください。

#### 確認項目
1. メール設定（`.env`ファイル）を確認
2. メールテンプレートが存在するか確認
3. メール通知設定が有効か確認
4. ログファイルでエラーを確認

#### 簡単なテスト方法

```bash
# テストコマンドでメール送信を確認
php artisan test:email matching your@email.com
```

詳細は`docs/EMAIL_TESTING.md`を参照してください。

---

## 更新履歴
- 2024年12月: 初版作成
- 2024年12月: JWTトークンの不一致問題を追加
- 2024年12月: チャットメッセージの配置問題を追加
- 2024年12月: admin_operation_logsテーブルのupdated_atカラムエラーを追加
- 2024年12月: 報告書提出時の401エラーを追加
- 2026年1月17日: ガイドの依頼承諾時の400エラーを追加
- 2026年1月17日: ガイドの依頼応募後のボタン無効化が機能しない問題を追加
- 2026年1月17日: マッチング詳細ページで「マッチングが見つかりません」エラーを追加
- 2026年1月: メール通知の問題解決への参照を追加
- 2026年1月: プロフィール編集画面での統計API 404エラーを追加
