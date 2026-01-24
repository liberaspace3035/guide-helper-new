# `approveGuide` API の流れ

## エンドポイント

```
PUT /api/admin/guides/{id}/approve
```

## 全体の流れ

```
フロントエンド (Blade/Vue.js)
    ↓
1. ボタンクリック
    ↓
2. HTTPリクエスト (PUT /api/admin/guides/{id}/approve)
    ↓
3. Laravel ルーティング (routes/api.php)
    ↓
4. 認証ミドルウェア (auth:api)
    ↓
5. 権限チェックミドルウェア (role:admin)
    ↓
6. AdminController::approveGuide()
    ↓
7. AdminService::approveGuide()
    ↓
8. データベース更新 (users テーブル)
    ↓
9. 通知作成 (notifications テーブル)
    ↓
10. JSONレスポンス
    ↓
11. フロントエンドで画面更新
```

## 詳細な流れ

### 1. フロントエンド（Blade/Vue.js）

**ファイル**: `resources/views/admin/dashboard.blade.php`

```javascript
// ボタンクリック時の処理
async approveGuide(guideId) {
    try {
        const response = await fetch(`/api/admin/guides/${guideId}/approve`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('ガイド承認に失敗しました');
        }
        
        const data = await response.json();
        alert(data.message); // "ガイドを承認しました"
        
        // ガイド一覧を再取得
        this.fetchGuides();
    } catch (error) {
        console.error('ガイド承認エラー:', error);
        alert('ガイド承認に失敗しました');
    }
}
```

### 2. ルーティング

**ファイル**: `routes/api.php`

```php
Route::middleware(['auth:api'])->group(function () {
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::put('/guides/{id}/approve', [
            \App\Http\Controllers\Api\AdminController::class, 
            'approveGuide'
        ]);
    });
});
```

**ポイント**:
- `auth:api`: JWT認証が必要
- `role:admin`: 管理者権限が必要
- `PUT`メソッド
- `{id}`: URLパラメータ（ガイドのID）

### 3. 認証・権限チェック

**ミドルウェア**:
1. **認証ミドルウェア (`auth:api`)**
   - JWTトークンを検証
   - トークンが無効な場合、401エラーを返す

2. **権限チェックミドルウェア (`role:admin`)**
   - ユーザーの`role`が`admin`か確認
   - 管理者でない場合、403エラーを返す

### 4. コントローラー

**ファイル**: `app/Http/Controllers/Api/AdminController.php`

```php
public function approveGuide(int $id)
{
    try {
        // AdminServiceのメソッドを呼び出し
        $this->adminService->approveGuide($id);
        
        // 成功レスポンス
        return response()->json(['message' => 'ガイドを承認しました']);
    } catch (\Exception $e) {
        // エラーレスポンス
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

**ポイント**:
- コントローラーは薄く保ち、ビジネスロジックはServiceに委譲
- 例外をキャッチして、適切なHTTPステータスコードで返す

### 5. サービス層

**ファイル**: `app/Services/AdminService.php`

```php
public function approveGuide(int $guideId): void
{
    // 1. ガイドの存在確認（role='guide'であることも確認）
    $guide = User::where('id', $guideId)
                 ->where('role', 'guide')
                 ->firstOrFail(); // 見つからない場合、404エラー
    
    // 2. ガイドを承認（is_allowed = true に更新）
    $guide->update(['is_allowed' => true]);
    
    // 3. 通知を作成
    Notification::create([
        'user_id' => $guideId,
        'type' => 'approval',
        'title' => 'アカウントが承認されました',
        'message' => 'あなたのアカウントが承認されました。ログインできるようになりました。',
        'related_id' => $guideId,
    ]);
}
```

**処理内容**:
1. **ガイドの存在確認**: IDとroleが'guide'であることを確認
2. **承認状態の更新**: `users`テーブルの`is_allowed`を`true`に更新
3. **通知の作成**: `notifications`テーブルに承認通知を追加

### 6. データベース操作

#### 6-1. users テーブルの更新

```sql
UPDATE users 
SET is_allowed = true, 
    updated_at = NOW() 
WHERE id = ? AND role = 'guide'
```

#### 6-2. notifications テーブルへの挿入

```sql
INSERT INTO notifications 
    (user_id, type, title, message, related_id, created_at, updated_at)
VALUES 
    (?, 'approval', 'アカウントが承認されました', 
     'あなたのアカウントが承認されました。ログインできるようになりました。', 
     ?, NOW(), NOW())
```

### 7. レスポンス

**成功時 (200 OK)**:
```json
{
    "message": "ガイドを承認しました"
}
```

**エラー時 (400 Bad Request)**:
```json
{
    "error": "エラーメッセージ"
}
```

**エラー例**:
- ガイドが見つからない: `404 Not Found` (firstOrFailが例外を投げる)
- その他のエラー: `400 Bad Request`

### 8. フロントエンドでの処理

1. レスポンスを受け取る
2. 成功メッセージを表示
3. ガイド一覧を再取得（`this.fetchGuides()`）
4. 画面を更新

## データフロー図

```
┌─────────────────┐
│  フロントエンド  │
│  (Vue.js)       │
│                 │
│  [承認ボタン]   │
└────────┬────────┘
         │ PUT /api/admin/guides/{id}/approve
         │ Authorization: Bearer {token}
         ↓
┌─────────────────┐
│   ルーティング   │
│  routes/api.php │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ 認証ミドルウェア │
│   auth:api      │
│  (JWT検証)      │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ 権限チェック     │
│  role:admin     │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  コントローラー  │
│ AdminController │
│ approveGuide()  │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│   サービス層     │
│  AdminService   │
│ approveGuide()  │
└────────┬────────┘
         │
         ├─────────────────┐
         ↓                 ↓
┌─────────────┐   ┌─────────────┐
│ users テーブル │   │notifications│
│ 更新         │   │テーブル      │
│ is_allowed=1│   │通知作成      │
└─────────────┘   └─────────────┘
         │
         ↓
┌─────────────────┐
│  JSONレスポンス  │
│ {"message":...} │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  フロントエンド  │
│  画面更新        │
└─────────────────┘
```

## 関連ファイル

| ファイル | 役割 |
|---------|------|
| `resources/views/admin/dashboard.blade.php` | フロントエンド（Vue.js） |
| `routes/api.php` | ルーティング定義 |
| `app/Http/Controllers/Api/AdminController.php` | コントローラー |
| `app/Services/AdminService.php` | ビジネスロジック |
| `app/Models/User.php` | ユーザーモデル |
| `app/Models/Notification.php` | 通知モデル |

## ポイント

1. **レイヤードアーキテクチャ**: コントローラー → サービス → モデル の順で処理
2. **認証・認可**: ミドルウェアでJWT認証と管理者権限をチェック
3. **エラーハンドリング**: try-catchで例外を適切に処理
4. **通知機能**: 承認時に自動的に通知を作成
5. **RESTful設計**: PUTメソッドでリソースの状態を更新


