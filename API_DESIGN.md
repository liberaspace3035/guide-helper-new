# API設計ドキュメント

## ガイドマッチングアプリケーション API仕様

**ベースURL**: `http://localhost:3001/api`

**認証方式**: JWT Bearer Token  
**認証ヘッダー**: `Authorization: Bearer <token>`

---

## 目次

1. [認証 (Authentication)](#認証-authentication)
2. [ユーザー (Users)](#ユーザー-users)
3. [依頼 (Requests)](#依頼-requests)
4. [マッチング (Matchings)](#マッチング-matchings)
5. [チャット (Chat)](#チャット-chat)
6. [報告書 (Reports)](#報告書-reports)
7. [通知 (Notifications)](#通知-notifications)
8. [管理者 (Admin)](#管理者-admin)

---

## 認証 (Authentication)

### POST `/auth/register`
ユーザー登録

**認証**: 不要

**リクエストボディ**:
```json
{
  "email": "user@example.com",
  "password": "password123",
  "name": "山田太郎",
  "phone": "090-1234-5678",
  "role": "user" // "user" または "guide"
}
```

**レスポンス** (201 Created):
```json
{
  "message": "ユーザー登録が完了しました",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田太郎",
    "role": "user"
  }
}
```

**エラー**:
- `400`: バリデーションエラー、メールアドレス重複
- `500`: サーバーエラー

---

### POST `/auth/login`
ログイン

**認証**: 不要

**リクエストボディ**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**レスポンス** (200 OK):
```json
{
  "message": "ログインに成功しました",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田太郎",
    "role": "user"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: メールアドレスまたはパスワードが正しくありません
- `500`: サーバーエラー

---

### GET `/auth/me`
現在のユーザー情報取得

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田太郎",
    "phone": "090-1234-5678",
    "role": "user",
    "created_at": "2024-01-01T00:00:00.000Z",
    "profile": {
      "contact_method": "電話",
      "notes": "備考"
    }
  }
}
```

**エラー**:
- `401`: 認証トークンが無効
- `404`: ユーザーが見つかりません
- `500`: サーバーエラー

---

## ユーザー (Users)

### PUT `/users/profile`
プロフィール更新（ユーザー）

**認証**: 必須

**リクエストボディ**:
```json
{
  "name": "山田太郎",
  "phone": "090-1234-5678",
  "contact_method": "電話",
  "notes": "備考"
}
```

**レスポンス** (200 OK):
```json
{
  "message": "プロフィールが更新されました"
}
```

---

### PUT `/users/guide-profile`
ガイドプロフィール更新

**認証**: 必須（ガイドのみ）

**リクエストボディ**:
```json
{
  "introduction": "ガイドの自己紹介",
  "available_areas": ["東京都", "大阪府"],
  "available_days": ["平日", "土日"],
  "available_times": ["午前", "午後", "夜間"]
}
```

**レスポンス** (200 OK):
```json
{
  "message": "ガイドプロフィールが更新されました"
}
```

---

## 依頼 (Requests)

### POST `/requests`
依頼作成

**認証**: 必須（ユーザーのみ）

**リクエストボディ**:
```json
{
  "request_type": "外出", // "外出" または "自宅"
  "destination_address": "東京都渋谷区青山１－１－１",
  "service_content": "買い物のサポートをお願いします",
  "request_date": "2024-01-15",
  "request_time": "14:00",
  "duration": 60,
  "notes": "音声入力のメモ",
  "is_voice_input": false
}
```

**レスポンス** (201 Created):
```json
{
  "message": "依頼が作成されました",
  "request": {
    "id": 1,
    "request_type": "外出",
    "destination_address": "東京都渋谷区青山１－１－１",
    "masked_address": "東京都渋谷区周辺",
    "service_content": "買い物のサポートをお願いします",
    "request_date": "2024-01-15",
    "request_time": "14:00",
    "status": "pending"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `403`: 承認待ちの報告書がある場合
- `500`: サーバーエラー

---

### GET `/requests/my-requests`
依頼一覧取得（ユーザー）

**認証**: 必須（ユーザーのみ）

**レスポンス** (200 OK):
```json
{
  "requests": [
    {
      "id": 1,
      "request_type": "外出",
      "masked_address": "東京都渋谷区周辺",
      "service_content": "買い物のサポート",
      "request_date": "2024-01-15",
      "request_time": "14:00",
      "duration": 60,
      "status": "matched",
      "matching_id": 5,
      "created_at": "2024-01-01T00:00:00.000Z"
    }
  ]
}
```

---

### GET `/requests/:id`
依頼詳細取得

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "request": {
    "id": 1,
    "request_type": "外出",
    "destination_address": "東京都渋谷区青山１－１－１", // ユーザーは完全な住所、ガイドはマスキング済み
    "service_content": "買い物のサポート",
    "request_date": "2024-01-15",
    "request_time": "14:00",
    "status": "matched"
  }
}
```

---

### GET `/requests/guide/available`
利用可能な依頼一覧（ガイド）

**認証**: 必須（ガイドのみ）

**レスポンス** (200 OK):
```json
{
  "requests": [
    {
      "id": 1,
      "request_type": "外出",
      "masked_address": "東京都渋谷区周辺",
      "service_content": "買い物のサポート",
      "request_date": "2024-01-15",
      "request_time": "14:00",
      "status": "pending"
    }
  ]
}
```

---

## マッチング (Matchings)

### POST `/matchings/accept`
依頼承諾（ガイド）

**認証**: 必須（ガイドのみ）

**リクエストボディ**:
```json
{
  "request_id": 1
}
```

**レスポンス** (200 OK):
```json
{
  "message": "依頼を承諾しました。自動マッチングによりマッチングが成立しました。",
  "auto_matched": true
}
```

または

```json
{
  "message": "依頼を承諾しました。管理者の承認を待っています。",
  "auto_matched": false
}
```

**エラー**:
- `400`: 依頼IDが指定されていない、既に承諾済み
- `403`: 未提出の報告書がある
- `404`: 依頼が見つかりません
- `500`: サーバーエラー

---

### POST `/matchings/decline`
依頼辞退（ガイド）

**認証**: 必須（ガイドのみ）

**リクエストボディ**:
```json
{
  "request_id": 1
}
```

**レスポンス** (200 OK):
```json
{
  "message": "依頼を辞退しました"
}
```

---

### GET `/matchings/my-matchings`
マッチング一覧取得

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "matchings": [
    {
      "id": 1,
      "request_id": 1,
      "user_id": 1,
      "guide_id": 2,
      "status": "matched",
      "matched_at": "2024-01-01T00:00:00.000Z",
      "guide_name": "ガイド太郎", // ユーザーの場合
      "user_name": "ユーザー花子", // ガイドの場合
      "request_type": "外出",
      "masked_address": "東京都渋谷区周辺",
      "request_date": "2024-01-15",
      "request_time": "14:00"
    }
  ]
}
```

---

## チャット (Chat)

### POST `/chat/messages`
メッセージ送信

**認証**: 必須

**リクエストボディ**:
```json
{
  "matching_id": 1,
  "message": "こんにちは、よろしくお願いします"
}
```

**レスポンス** (201 Created):
```json
{
  "message": "メッセージが送信されました",
  "chat_message": {
    "id": 1,
    "matching_id": 1,
    "sender_id": 1,
    "message": "こんにちは、よろしくお願いします",
    "created_at": "2024-01-01T00:00:00.000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `403`: マッチングにアクセスする権限がありません
- `404`: マッチングが見つかりません
- `500`: サーバーエラー

---

### GET `/chat/messages/:matching_id`
メッセージ一覧取得

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "messages": [
    {
      "id": 1,
      "matching_id": 1,
      "sender_id": 1,
      "sender_name": "山田太郎",
      "sender_role": "user",
      "message": "こんにちは",
      "created_at": "2024-01-01T00:00:00.000Z"
    }
  ]
}
```

**エラー**:
- `403`: マッチングにアクセスする権限がありません
- `404`: マッチングが見つかりません
- `500`: サーバーエラー

---

## 報告書 (Reports)

### POST `/reports`
報告書作成・更新（ガイド）

**認証**: 必須（ガイドのみ）

**リクエストボディ**:
```json
{
  "matching_id": 1,
  "service_content": "買い物のサポートを行いました",
  "report_content": "無事に完了しました",
  "actual_date": "2024-01-15",
  "actual_start_time": "14:00",
  "actual_end_time": "15:30"
}
```

**レスポンス** (201 Created / 200 OK):
```json
{
  "message": "報告書が作成されました",
  "report": {
    "id": 1,
    "matching_id": 1,
    "status": "draft"
  }
}
```

---

### GET `/reports/my-reports`
報告書一覧取得（ガイド）

**認証**: 必須（ガイドのみ）

**レスポンス** (200 OK):
```json
{
  "reports": [
    {
      "id": 1,
      "matching_id": 1,
      "request_id": 1,
      "user_id": 1,
      "guide_id": 2,
      "status": "submitted",
      "user_name": "山田太郎",
      "request_type": "外出",
      "request_date": "2024-01-15"
    }
  ]
}
```

---

### GET `/reports/:id`
報告書詳細取得（ガイド）

**認証**: 必須（ガイドのみ）

**レスポンス** (200 OK):
```json
{
  "report": {
    "id": 1,
    "matching_id": 1,
    "service_content": "買い物のサポート",
    "report_content": "無事に完了",
    "actual_date": "2024-01-15",
    "actual_start_time": "14:00",
    "actual_end_time": "15:30",
    "status": "submitted"
  }
}
```

---

### POST `/reports/:id/submit`
報告書提出（ガイド）

**認証**: 必須（ガイドのみ）

**レスポンス** (200 OK):
```json
{
  "message": "報告書が提出されました"
}
```

---

### GET `/reports/user/:matching_id`
報告書取得（ユーザー）

**認証**: 必須（ユーザーのみ）

**レスポンス** (200 OK):
```json
{
  "report": {
    "id": 1,
    "service_content": "買い物のサポート",
    "report_content": "無事に完了",
    "actual_date": "2024-01-15",
    "status": "submitted"
  }
}
```

---

### POST `/reports/:id/approve`
報告書承認（ユーザー）

**認証**: 必須（ユーザーのみ）

**レスポンス** (200 OK):
```json
{
  "message": "報告書が承認されました"
}
```

---

### POST `/reports/:id/request-revision`
報告書修正依頼（ユーザー）

**認証**: 必須（ユーザーのみ）

**リクエストボディ**:
```json
{
  "revision_notes": "詳細を追加してください"
}
```

**レスポンス** (200 OK):
```json
{
  "message": "修正依頼が送信されました"
}
```

---

## 通知 (Notifications)

### GET `/notifications`
通知一覧取得

**認証**: 必須

**クエリパラメータ**:
- `unread_only` (boolean): 未読のみ取得

**レスポンス** (200 OK):
```json
{
  "notifications": [
    {
      "id": 1,
      "user_id": 1,
      "type": "matching",
      "title": "マッチングが成立しました",
      "message": "マッチングが成立しました。チャットで詳細を確認してください。",
      "related_id": 1,
      "read_at": null,
      "created_at": "2024-01-01T00:00:00.000Z"
    }
  ]
}
```

---

### PUT `/notifications/:id/read`
通知を既読にする

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "message": "通知を既読にしました"
}
```

---

### GET `/notifications/unread-count`
未読通知数取得

**認証**: 必須

**レスポンス** (200 OK):
```json
{
  "unread_count": 3
}
```

---

## 管理者 (Admin)

**注意**: すべての管理者エンドポイントは認証と管理者権限が必要です。

### GET `/admin/requests`
依頼一覧取得

**レスポンス** (200 OK):
```json
{
  "requests": [
    {
      "id": 1,
      "request_type": "外出",
      "destination_address": "東京都渋谷区青山１－１－１",
      "status": "guide_accepted",
      "user_name": "山田太郎",
      "user_email": "user@example.com"
    }
  ]
}
```

---

### GET `/admin/acceptances`
承諾一覧取得

**レスポンス** (200 OK):
```json
{
  "acceptances": [
    {
      "id": 1,
      "request_id": 1,
      "guide_id": 2,
      "status": "pending",
      "admin_decision": "pending",
      "request_type": "外出",
      "masked_address": "東京都渋谷区周辺",
      "user_name": "山田太郎",
      "guide_name": "ガイド太郎"
    }
  ]
}
```

---

### POST `/admin/matchings/approve`
マッチング手動承認

**リクエストボディ**:
```json
{
  "request_id": 1,
  "guide_id": 2
}
```

**レスポンス** (200 OK):
```json
{
  "message": "マッチングが承認されました",
  "matching_id": 1
}
```

---

### POST `/admin/matchings/reject`
マッチング手動却下

**リクエストボディ**:
```json
{
  "request_id": 1,
  "guide_id": 2
}
```

**レスポンス** (200 OK):
```json
{
  "message": "マッチングが却下されました"
}
```

---

### GET `/admin/settings/auto-matching`
自動マッチング設定取得

**レスポンス** (200 OK):
```json
{
  "auto_matching": false
}
```

---

### PUT `/admin/settings/auto-matching`
自動マッチング設定更新

**リクエストボディ**:
```json
{
  "auto_matching": true
}
```

**レスポンス** (200 OK):
```json
{
  "message": "自動マッチング設定が更新されました",
  "auto_matching": true
}
```

---

### GET `/admin/reports`
報告書一覧取得

**レスポンス** (200 OK):
```json
{
  "reports": [
    {
      "id": 1,
      "matching_id": 1,
      "user_name": "山田太郎",
      "guide_name": "ガイド太郎",
      "request_type": "外出",
      "status": "approved"
    }
  ]
}
```

---

### GET `/admin/reports/csv`
報告書CSV出力

**レスポンス**: CSVファイル（Content-Type: text/csv）

---

### GET `/admin/usage/csv`
利用実績CSV出力

**クエリパラメータ**:
- `start_date` (optional): 開始日 (YYYY-MM-DD)
- `end_date` (optional): 終了日 (YYYY-MM-DD)

**レスポンス**: CSVファイル（Content-Type: text/csv）

---

## ステータスコード

- `200 OK`: リクエスト成功
- `201 Created`: リソース作成成功
- `400 Bad Request`: バリデーションエラー、不正なリクエスト
- `401 Unauthorized`: 認証が必要、認証トークンが無効
- `403 Forbidden`: 権限が不足
- `404 Not Found`: リソースが見つからない
- `500 Internal Server Error`: サーバーエラー

---

## エラーレスポンス形式

```json
{
  "error": "エラーメッセージ",
  "code": "ERROR_CODE" // 開発環境のみ
}
```

バリデーションエラーの場合:
```json
{
  "errors": [
    {
      "msg": "エラーメッセージ",
      "param": "field_name",
      "location": "body"
    }
  ]
}
```

---

## 認証フロー

1. ユーザー登録またはログインでJWTトークンを取得
2. 以降のリクエストで `Authorization: Bearer <token>` ヘッダーを付与
3. トークンは7日間有効（デフォルト）

---

## データモデル

### 依頼ステータス
- `pending`: 承諾待ち
- `guide_accepted`: ガイド承諾済み
- `matched`: マッチング成立
- `in_progress`: 進行中
- `completed`: 完了
- `cancelled`: キャンセル

### マッチングステータス
- `matched`: マッチング成立
- `in_progress`: 進行中
- `completed`: 完了
- `cancelled`: キャンセル

### 報告書ステータス
- `draft`: 下書き
- `submitted`: 提出済み
- `approved`: 承認済み
- `revision_requested`: 修正依頼

---

## 注意事項

1. すべての日時はISO 8601形式（UTC）
2. 時刻は `HH:mm` 形式（24時間表記）
3. 日付は `YYYY-MM-DD` 形式
4. 個人情報（住所）はガイドにはマスキングされて表示される
5. 自動マッチングがONの場合、ガイドの承諾と同時にマッチングが成立する
6. 自動マッチングがOFFの場合、管理者の承認が必要

