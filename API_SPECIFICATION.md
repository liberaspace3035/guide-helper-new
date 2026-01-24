# API仕様書

このドキュメントは、ガイドヘルパーアプリケーションのすべてのAPIエンドポイントの仕様をまとめています。

**ベースURL**: `/api`

**認証方式**: JWT Bearer Token  
**認証ヘッダー**: `Authorization: Bearer <token>`

---

## 目次

1. [認証 (Authentication)](#認証-authentication)
2. [依頼 (Requests)](#依頼-requests)
3. [マッチング (Matchings)](#マッチング-matchings)
4. [チャット (Chat)](#チャット-chat)
5. [お知らせ (Announcements)](#お知らせ-announcements)
6. [管理者 (Admin)](#管理者-admin)
7. [メールテンプレート (Email Templates)](#メールテンプレート-email-templates)

---

## 認証 (Authentication)

### POST `/api/auth/register`

ユーザー登録

**認証**: 不要

**リクエストボディ**:

共通パラメータ:
- `email` (string, required): メールアドレス（メール形式、ユニーク）
- `email_confirmation` (string, required): メールアドレス確認（emailと同じ値）
- `password` (string, required): パスワード（最小6文字）
- `confirmPassword` (string, required): パスワード確認（passwordと同じ値）
- `last_name` (string, required): 姓（最大50文字）
- `first_name` (string, required): 名（最大50文字）
- `last_name_kana` (string, required): 姓（カナ）（最大50文字、カタカナのみ）
- `first_name_kana` (string, required): 名（カナ）（最大50文字、カタカナのみ）
- `postal_code` (string, required): 郵便番号（形式: `\d{3}-\d{4}`）
- `address` (string, required): 住所
- `phone` (string, required): 電話番号（形式: `[\d\-\+\(\)\s]+`）
- `gender` (string, required): 性別（`male`, `female`, `other`, `prefer_not_to_say`）
- `birth_date` (date, required): 生年月日（日付形式）
- `role` (string, required): ロール（`user`, `guide`）

ユーザー（role: user）の場合の追加パラメータ:
- `interview_date_1` (date, required): 面接希望日1
- `interview_date_2` (date, optional): 面接希望日2
- `interview_date_3` (date, optional): 面接希望日3
- `application_reason` (string, required): 応募理由
- `visual_disability_status` (string, required): 視覚障害の状態
- `disability_support_level` (string, required): 障害者支援レベル（最大10文字）
- `daily_life_situation` (string, required): 日常生活の状況

ガイド（role: guide）の場合の追加パラメータ:
- `application_reason` (string, required): 応募理由
- `goal` (string, required): 目標
- `qualifications` (array, required, 最大3件): 資格情報
  - `qualifications[].name` (string, required): 資格名
  - `qualifications[].obtained_date` (date, required): 取得日
- `preferred_work_hours` (string, required): 希望勤務時間

**レスポンス** (201 Created):
```json
{
  "message": "ユーザー登録が完了しました。審査完了後、運営からご連絡いたします。",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田 太郎",
    "role": "user"
  }
}
```

**エラー**:
- `400`: バリデーションエラー（`errors`オブジェクトを含む）
- `500`: サーバーエラー

---

### POST `/api/auth/login`

ログイン

**認証**: 不要

**リクエストボディ**:
- `email` (string, required): メールアドレス（メール形式）
- `password` (string, required): パスワード

**レスポンス** (200 OK):
```json
{
  "message": "ログインに成功しました",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田 太郎",
    "role": "user"
  }
}
```

**エラー**:
- `400`: バリデーションエラー（`errors`オブジェクトを含む）
- `401`: メールアドレスまたはパスワードが正しくありません / ユーザーは承認されていません

---

### GET `/api/auth/user`

現在のユーザー情報取得

**認証**: 必須（JWT）

**レスポンス** (200 OK):
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "山田 太郎",
    "last_name": "山田",
    "first_name": "太郎",
    "last_name_kana": "ヤマダ",
    "first_name_kana": "タロウ",
    "birth_date": "1990-01-01",
    "age": 34,
    "gender": "male",
    "address": "東京都渋谷区...",
    "postal_code": "150-0001",
    "phone": "090-1234-5678",
    "role": "user",
    "is_allowed": true,
    "email_confirmed": true,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "profile": {
      // ユーザーの場合: UserProfile
      // ガイドの場合: GuideProfile（available_areas, available_days, available_timesを含む）
    }
  }
}
```

**エラー**:
- `401`: 認証エラー（認証が必要 / 認証トークンが無効）

---

### POST `/api/auth/logout`

ログアウト

**認証**: 必須（JWT）

**レスポンス** (200 OK):
```json
{
  "message": "ログアウトに成功しました"
}
```

**エラー**:
- `500`: ログアウト中にエラーが発生しました

---

## 依頼 (Requests)

### GET `/api/requests/my-requests`

依頼一覧取得（ユーザー）

**認証**: 必須（JWT、ユーザー）

**レスポンス** (200 OK):
```json
{
  "requests": [
    {
      "id": 1,
      "user_id": 1,
      "request_type": "外出", // "外出" または "自宅"
      "destination_address": "東京都渋谷区...",
      "masked_address": "東京都渋谷区周辺",
      "service_content": "買い物のサポート",
      "request_date": "2024-01-15",
      "request_time": "14:00",
      "start_time": "14:00",
      "end_time": "16:00",
      "duration": 120,
      "status": "pending",
      "matching_id": 5,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

### GET `/api/requests/guide/available`

利用可能な依頼一覧取得（ガイド）

**認証**: 必須（JWT、ガイド）

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
      "start_time": "14:00",
      "end_time": "16:00",
      "duration": 120,
      "status": "pending",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

### GET `/api/requests/{id}/applicants`

依頼の応募者一覧取得

**認証**: 必須（JWT、ユーザー、依頼の所有者のみ）

**パラメータ**:
- `id` (path, integer, required): 依頼ID

**レスポンス** (200 OK):
```json
{
  "guides": [
    {
      "guide_id": 2,
      "name": "ガイド 太郎",
      "gender": "male",
      "age": 30,
      "introduction": "ガイドの自己紹介",
      "status": "pending",
      "admin_decision": "pending",
      "user_selected": false,
      "matching_id": null
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: この依頼の応募者を見る権限がありません
- `404`: 依頼が見つかりません

---

### GET `/api/requests/matched-guides/all`

マッチング成立済みガイド一覧取得

**認証**: 必須（JWT、ユーザー）

**レスポンス** (200 OK):
```json
{
  "matched": [
    {
      "request_id": 1,
      "guide_id": 2,
      "matching_id": 5
    }
  ],
  "selected": [
    {
      "request_id": 1,
      "guide_id": 2,
      "matching_id": 5
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

### POST `/api/requests/{id}/select-guide`

ガイド選択

**認証**: 必須（JWT、ユーザー、依頼の所有者のみ）

**パラメータ**:
- `id` (path, integer, required): 依頼ID

**リクエストボディ**:
- `guide_id` (integer, required): ガイドID

**レスポンス** (200 OK):

自動マッチングがONの場合:
```json
{
  "message": "ガイドが選択されました。自動マッチングによりマッチングが成立しました。",
  "auto_matching": true,
  "matching_id": 5
}
```

自動マッチングがOFFの場合:
```json
{
  "message": "ガイドが選択されました。管理者の承認を待っています。",
  "auto_matching": false
}
```

**エラー**:
- `400`: guide_idを指定してください / バリデーションエラー
- `401`: 認証エラー
- `403`: この依頼を選択する権限がありません
- `404`: 依頼またはガイド承諾が見つかりません

---

### GET `/api/guides/available`

指名用の利用可能なガイド一覧取得

**認証**: 必須（JWT）

**レスポンス** (200 OK):
```json
{
  "guides": [
    {
      "id": 2,
      "name": "ガイド 太郎",
      "gender": "male",
      "age": 30,
      "introduction": "ガイドの自己紹介"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

## マッチング (Matchings)

### POST `/api/matchings/accept`

依頼承諾（ガイド）

**認証**: 必須（JWT、ガイド）

**リクエストボディ**:
- `request_id` (integer, required): 依頼ID（requestsテーブルに存在）

**レスポンス** (200 OK):
```json
{
  "message": "依頼を承諾しました",
  "auto_matched": true // または false
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー

---

### POST `/api/matchings/decline`

依頼辞退（ガイド）

**認証**: 必須（JWT、ガイド）

**リクエストボディ**:
- `request_id` (integer, required): 依頼ID（requestsテーブルに存在）

**レスポンス** (200 OK):
```json
{
  "message": "依頼を辞退しました"
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー

---

### GET `/api/matchings/my-matchings`

マッチング一覧取得

**認証**: 必須（JWT）

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
      "matched_at": "2024-01-01T00:00:00.000000Z",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

## チャット (Chat)

### POST `/api/chat/messages`

メッセージ送信

**認証**: 必須（JWT）

**リクエストボディ**:
- `matching_id` (integer, required): マッチングID（matchingsテーブルに存在）
- `message` (string, required): メッセージ内容（最大1000文字）

**レスポンス** (201 Created):
```json
{
  "message": "メッセージが送信されました",
  "chat_message": {
    "id": 1,
    "matching_id": 1,
    "sender_id": 1,
    "message": "こんにちは、よろしくお願いします",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 例外メッセージ（マッチングにアクセスする権限がない場合など）

---

### GET `/api/chat/messages/{matchingId}`

メッセージ一覧取得

**認証**: 必須（JWT）

**パラメータ**:
- `matchingId` (path, integer, required): マッチングID

**レスポンス** (200 OK):
```json
{
  "messages": [
    {
      "id": 1,
      "matching_id": 1,
      "sender_id": 1,
      "sender_name": "山田 太郎",
      "sender_role": "user",
      "message": "こんにちは",
      "read_at": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 例外メッセージ（マッチングにアクセスする権限がない場合など）

---

### GET `/api/chat/unread-count`

未読メッセージ数取得

**認証**: 必須（JWT）

**レスポンス** (200 OK):
```json
{
  "unread_count": 3
}
```

**エラー**:
- `401`: 認証エラー

---

## お知らせ (Announcements)

### GET `/api/announcements`

お知らせ一覧取得

**認証**: 必須（JWT）

**レスポンス** (200 OK):
```json
{
  "announcements": [
    {
      "id": 1,
      "title": "お知らせタイトル",
      "content": "お知らせ内容",
      "target_audience": "all", // "user", "guide", "all"
      "created_at": "2024-01-01T00:00:00.000000Z",
      "is_read": false
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー

---

### POST `/api/announcements/{id}/read`

お知らせを既読にする

**認証**: 必須（JWT）

**パラメータ**:
- `id` (path, integer, required): お知らせID

**レスポンス** (200 OK):
```json
{
  "message": "お知らせを既読にしました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー

---

### GET `/api/announcements/admin/all`

お知らせ一覧取得（管理者）

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "announcements": [
    {
      "id": 1,
      "title": "お知らせタイトル",
      "content": "お知らせ内容",
      "target_audience": "all",
      "created_by": 1,
      "created_by_name": "管理者 太郎",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### POST `/api/announcements/admin`

お知らせ作成（管理者）

**認証**: 必須（JWT、管理者）

**リクエストボディ**:
- `title` (string, required): タイトル（最大255文字）
- `content` (string, required): 内容
- `target_audience` (string, required): 対象者（`user`, `guide`, `all`）

**レスポンス** (201 Created):
```json
{
  "announcement": {
    "id": 1,
    "title": "お知らせタイトル",
    "content": "お知らせ内容",
    "target_audience": "all",
    "created_by": 1,
    "created_by_name": "管理者 太郎",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/announcements/admin/{id}`

お知らせ更新（管理者）

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): お知らせID

**リクエストボディ**:
- `title` (string, required): タイトル（最大255文字）
- `content` (string, required): 内容
- `target_audience` (string, required): 対象者（`user`, `guide`, `all`）

**レスポンス** (200 OK):
```json
{
  "announcement": {
    "id": 1,
    "title": "お知らせタイトル（更新）",
    "content": "お知らせ内容（更新）",
    "target_audience": "all",
    "created_by": 1,
    "created_by_name": "管理者 太郎",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### DELETE `/api/announcements/admin/{id}`

お知らせ削除（管理者）

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): お知らせID

**レスポンス** (200 OK):
```json
{
  "message": "お知らせを削除しました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

## 管理者 (Admin)

すべての管理者エンドポイントは認証と管理者権限（`role:admin`）が必要です。

### GET `/api/admin/requests`

依頼一覧取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "requests": [
    {
      "id": 1,
      "user_id": 1,
      "request_type": "外出",
      "destination_address": "東京都渋谷区...",
      "status": "pending",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "user_name": "山田 太郎",
      "user_email": "user@example.com"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 依頼一覧の取得中にエラーが発生しました

---

### GET `/api/admin/acceptances`

承諾一覧取得

**認証**: 必須（JWT、管理者）

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
      "user_selected": false,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "request_type": "外出",
      "masked_address": "東京都渋谷区周辺",
      "user_name": "山田 太郎",
      "guide_name": "ガイド 太郎"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 承諾一覧の取得中にエラーが発生しました

---

### GET `/api/admin/reports`

報告書一覧取得

**認証**: 必須（JWT、管理者）

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
      "service_content": "買い物のサポート",
      "report_content": "無事に完了しました",
      "actual_date": "2024-01-15",
      "actual_start_time": "14:00",
      "actual_end_time": "15:30",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "user_name": "山田 太郎",
      "guide_name": "ガイド 太郎",
      "request_type": "外出"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 報告書一覧の取得中にエラーが発生しました

---

### GET `/api/admin/reports/csv`

報告書CSV出力

**認証**: 必須（JWT、管理者）

**レスポンス**: CSVファイル（Content-Type: text/csv; charset=utf-8）

CSVヘッダー:
```
ID,利用日,開始時刻,終了時刻,ユーザー名,ユーザーメール,受給者証番号,ガイド名,ガイドメール,従業員番号,依頼タイプ,依頼日,承認日時
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### GET `/api/admin/usage/csv`

利用実績CSV出力

**認証**: 必須（JWT、管理者）

**クエリパラメータ**:
- `start_date` (string, optional): 開始日（YYYY-MM-DD形式）
- `end_date` (string, optional): 終了日（YYYY-MM-DD形式）

**レスポンス**: CSVファイル（Content-Type: text/csv; charset=utf-8）

CSVヘッダー:
```
ID,利用日,開始時刻,終了時刻,利用時間(分),ユーザー名,受給者証番号,ガイド名,従業員番号,依頼タイプ
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### GET `/api/admin/stats`

統計情報取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "total_users": 100,
  "total_guides": 50,
  "total_requests": 200,
  "total_matchings": 150,
  "pending_approvals": 5
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 統計情報の取得中にエラーが発生しました

---

### GET `/api/users/stats`

ユーザー統計情報取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "user_stats": {
    "total": 100,
    "active": 80,
    "pending": 10,
    "rejected": 10
  }
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: ユーザー統計情報の取得中にエラーが発生しました

---

### GET `/api/admin/settings/auto-matching`

自動マッチング設定取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "auto_matching": false
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 自動マッチング設定の取得中にエラーが発生しました

---

### PUT `/api/admin/settings/auto-matching`

自動マッチング設定更新

**認証**: 必須（JWT、管理者）

**リクエストボディ**:
- `auto_matching` (boolean, required): 自動マッチング設定（true/false）

**レスポンス** (200 OK):
```json
{
  "message": "自動マッチング設定が更新されました",
  "auto_matching": true
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### POST `/api/admin/matchings/approve`

マッチング手動承認

**認証**: 必須（JWT、管理者）

**リクエストボディ**:
- `request_id` (integer, required): 依頼ID（requestsテーブルに存在）
- `guide_id` (integer, required): ガイドID（usersテーブルに存在）

**レスポンス** (200 OK):
```json
{
  "message": "マッチングが承認されました",
  "matching_id": 5
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### POST `/api/admin/matchings/reject`

マッチング手動却下

**認証**: 必須（JWT、管理者）

**リクエストボディ**:
- `request_id` (integer, required): 依頼ID（requestsテーブルに存在）
- `guide_id` (integer, required): ガイドID（usersテーブルに存在）

**レスポンス** (200 OK):
```json
{
  "message": "マッチングが却下されました"
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### GET `/api/admin/users`

ユーザー一覧取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "users": [
    {
      "id": 1,
      "email": "user@example.com",
      "name": "山田 太郎",
      "role": "user",
      "is_allowed": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "userProfile": {
        "recipient_number": "1234567890",
        "admin_comment": "管理者コメント"
      }
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### GET `/api/admin/guides`

ガイド一覧取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "guides": [
    {
      "id": 2,
      "email": "guide@example.com",
      "name": "ガイド 太郎",
      "role": "guide",
      "is_allowed": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "guideProfile": {
        "employee_number": "EMP001",
        "introduction": "ガイドの自己紹介"
      }
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/users/{id}/profile-extra`

ユーザープロフィール追加情報更新

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ユーザーID

**リクエストボディ**:
- `recipient_number` (string, optional): 受給者証番号
- `admin_comment` (string, optional): 管理者コメント

**レスポンス** (200 OK):
```json
{
  "message": "ユーザー情報を更新しました"
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/guides/{id}/profile-extra`

ガイドプロフィール追加情報更新

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ガイドID

**リクエストボディ**:
- `employee_number` (string, optional): 従業員番号

**レスポンス** (200 OK):
```json
{
  "message": "ガイド情報を更新しました"
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/users/{id}/approve`

ユーザー承認

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ユーザーID

**レスポンス** (200 OK):
```json
{
  "message": "ユーザーを承認しました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/users/{id}/reject`

ユーザー拒否

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ユーザーID

**レスポンス** (200 OK):
```json
{
  "message": "ユーザーを拒否しました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/guides/{id}/approve`

ガイド承認

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ガイドID

**レスポンス** (200 OK):
```json
{
  "message": "ガイドを承認しました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/guides/{id}/reject`

ガイド拒否

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ガイドID

**レスポンス** (200 OK):
```json
{
  "message": "ガイドを拒否しました"
}
```

**エラー**:
- `400`: 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/users/{id}/monthly-limit`

ユーザーの月次限度時間を設定

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ユーザーID

**リクエストボディ**:
- `limit_hours` (numeric, required): 限度時間（時間、0以上）
- `year` (integer, required): 年（2000-2100）
- `month` (integer, required): 月（1-12）

**レスポンス** (200 OK):
```json
{
  "message": "限度時間を設定しました",
  "limit": {
    "id": 1,
    "user_id": 1,
    "year": 2024,
    "month": 1,
    "limit_hours": 20.0,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー / 例外メッセージ
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### GET `/api/admin/users/{id}/monthly-limits`

ユーザーの月次限度時間一覧取得

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): ユーザーID

**レスポンス** (200 OK):
```json
{
  "limits": [
    {
      "id": 1,
      "user_id": 1,
      "year": 2024,
      "month": 1,
      "limit_hours": 20.0,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 例外メッセージ

---

### GET `/api/admin/operation-logs`

管理操作ログ一覧取得

**認証**: 必須（JWT、管理者）

**クエリパラメータ**:
- `limit` (integer, optional, デフォルト: 100): 取得件数
- `operation_type` (string, optional): 操作タイプでフィルタ
- `target_type` (string, optional): 対象タイプでフィルタ

**レスポンス** (200 OK):
```json
{
  "logs": [
    {
      "id": 1,
      "admin_id": 1,
      "admin_name": "管理者 太郎",
      "operation_type": "user_approval",
      "target_type": "user",
      "target_id": 1,
      "details": {},
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `500`: 例外メッセージ

---

## メールテンプレート (Email Templates)

### GET `/api/admin/email-templates`

メールテンプレート一覧取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK, Content-Type: application/json; charset=utf-8):
```json
{
  "templates": [
    {
      "id": 1,
      "template_key": "user_registration",
      "subject": "ユーザー登録完了",
      "body": "テンプレート本文...",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/email-templates/{id}`

メールテンプレート更新

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): テンプレートID

**リクエストボディ**:
- `subject` (string, required): 件名（最大255文字）
- `body` (string, required): 本文
- `is_active` (boolean, optional): 有効フラグ（デフォルト: true）

**レスポンス** (200 OK):
```json
{
  "message": "テンプレートを更新しました",
  "template": {
    "id": 1,
    "template_key": "user_registration",
    "subject": "ユーザー登録完了（更新）",
    "body": "テンプレート本文（更新）...",
    "is_active": true,
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `404`: テンプレートが見つかりません

---

### GET `/api/admin/email-settings`

メール通知設定一覧取得

**認証**: 必須（JWT、管理者）

**レスポンス** (200 OK):
```json
{
  "settings": [
    {
      "id": 1,
      "notification_type": "user_registration",
      "is_enabled": true,
      "reminder_days": 7,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

**エラー**:
- `401`: 認証エラー
- `403`: 管理者権限が必要

---

### PUT `/api/admin/email-settings/{id}`

メール通知設定更新

**認証**: 必須（JWT、管理者）

**パラメータ**:
- `id` (path, integer, required): 設定ID

**リクエストボディ**:
- `is_enabled` (boolean, required): 有効フラグ
- `reminder_days` (integer, optional, 最小: 1): リマインダー日数

**レスポンス** (200 OK):
```json
{
  "message": "通知設定を更新しました",
  "setting": {
    "id": 1,
    "notification_type": "user_registration",
    "is_enabled": true,
    "reminder_days": 7,
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**エラー**:
- `400`: バリデーションエラー
- `401`: 認証エラー
- `403`: 管理者権限が必要
- `404`: 設定が見つかりません

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

一般的なエラー:
```json
{
  "error": "エラーメッセージ"
}
```

バリデーションエラー:
```json
{
  "errors": {
    "field_name": [
      "エラーメッセージ1",
      "エラーメッセージ2"
    ]
  }
}
```

---

## 認証フロー

1. ユーザー登録またはログインでJWTトークンを取得
2. 以降のリクエストで `Authorization: Bearer <token>` ヘッダーを付与
3. トークンの有効期限はJWT設定に依存

---

## データ形式

- **日時**: ISO 8601形式（例: `2024-01-01T00:00:00.000000Z`）
- **時刻**: `HH:mm` 形式（24時間表記、例: `14:00`）
- **日付**: `YYYY-MM-DD` 形式（例: `2024-01-15`）
- **文字エンコーディング**: UTF-8

---

## 注意事項

1. すべての日時はUTCで返されます
2. 個人情報（住所）はガイドにはマスキングされて表示されます
3. 自動マッチングがONの場合、ガイドの承諾またはユーザーの選択と同時にマッチングが成立します
4. 自動マッチングがOFFの場合、管理者の承認が必要です
5. 管理者エンドポイントはすべて `role:admin` ミドルウェアによる認可が必要です

