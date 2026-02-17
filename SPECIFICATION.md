# ガイドヘルパーマッチングアプリケーション 仕様書

## 目次

1. [プロジェクト概要](#プロジェクト概要)
2. [システムアーキテクチャ](#システムアーキテクチャ)
3. [技術スタック](#技術スタック)
4. [データベース設計](#データベース設計)
5. [認証・認可システム](#認証認可システム)
6. [機能一覧](#機能一覧)
7. [マッチングフロー](#マッチングフロー)
8. [報告書フロー](#報告書フロー)
9. [利用時間管理システム](#利用時間管理システム)
10. [API仕様](#api仕様)
11. [セキュリティ](#セキュリティ)
12. [アクセシビリティ](#アクセシビリティ)
13. [メール通知システム](#メール通知システム)
14. [管理者機能](#管理者機能)
15. [デプロイメント](#デプロイメント)

---

## プロジェクト概要

### 目的

視覚障害者（ユーザー）とガイドヘルパー（ガイド）をマッチングし、外出支援や自宅支援サービスを提供するためのWebアプリケーションです。

### 主要な特徴

- **マッチング機能**: ユーザーとガイドを自動または手動でマッチング
- **依頼管理**: ユーザーが依頼を作成し、ガイドが承諾
- **チャット機能**: マッチング成立後、ユーザーとガイドがコミュニケーション
- **報告書管理**: ガイドが報告書を作成し、ユーザーと管理者が承認
- **利用時間管理**: 月次限度時間の設定と使用時間の追跡
- **管理者機能**: ユーザー・ガイド管理、マッチング承認、統計情報の確認

### 対象ユーザー

1. **ユーザー（視覚障害者）**: サービスを利用する視覚障害者
2. **ガイド**: ガイドヘルパーとしてサービスを提供する人
3. **管理者**: システム全体を管理する運営者

---

## システムアーキテクチャ

### アーキテクチャ概要

```
┌─────────────────┐
│   Web Browser   │
│  (React/Blade)  │
└────────┬────────┘
         │
         │ HTTP/HTTPS
         │
┌────────▼────────┐
│  Laravel App    │
│  (PHP 8.1+)     │
│  - Web Routes   │
│  - API Routes   │
│  - Controllers  │
│  - Services     │
└────────┬────────┘
         │
         │ PDO/Query Builder
         │
┌────────▼────────┐
│   PostgreSQL    │
│   Database      │
└─────────────────┘
```

### フロントエンド構成

- **Bladeテンプレート**: Laravel標準のBladeテンプレート（サーバーサイドレンダリング）
- **Alpine.js**: インタラクティブなUI要素の実装
- **React（オプション）**: 既存のReactアプリケーションも利用可能（Viteビルド）

### バックエンド構成

- **Laravel 11**: PHPフレームワーク
- **サービス層**: ビジネスロジックの分離
- **Eloquent ORM**: データベースアクセス
- **認証**: セッション認証（Web）とJWT認証（API）

---

## 技術スタック

### バックエンド

- **PHP**: 8.1以上
- **Laravel**: 11.x
- **データベース**: PostgreSQL
- **認証**: Laravel Sanctum（JWT）、Session認証
- **メール**: Laravel Mail（SMTP）

### フロントエンド

- **Blade**: Laravel標準テンプレートエンジン
- **Alpine.js**: 軽量JavaScriptフレームワーク
- **React**: 18.x（オプション、既存アプリ用）
- **Vite**: ビルドツール
- **CSS**: SCSS

### 開発ツール

- **Composer**: PHP依存関係管理
- **npm**: Node.js依存関係管理
- **Git**: バージョン管理

---

## データベース設計

### 主要テーブル

#### users（ユーザー）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| email | VARCHAR(255) | メールアドレス（ユニーク） |
| password_hash | VARCHAR(255) | パスワードハッシュ |
| name | VARCHAR(255) | 氏名 |
| last_name | VARCHAR(50) | 姓 |
| first_name | VARCHAR(50) | 名 |
| last_name_kana | VARCHAR(50) | 姓（カナ） |
| first_name_kana | VARCHAR(50) | 名（カナ） |
| age | INT | 年齢 |
| gender | ENUM | 性別（male, female, other, prefer_not_to_say） |
| address | TEXT | 住所 |
| postal_code | VARCHAR(10) | 郵便番号 |
| phone | VARCHAR(20) | 電話番号 |
| birth_date | DATE | 生年月日 |
| role | ENUM | ロール（user, guide, admin） |
| is_allowed | BOOLEAN | 承認フラグ |
| email_confirmed | BOOLEAN | メール確認フラグ |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### requests（依頼）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| user_id | INT | ユーザーID（外部キー） |
| nominated_guide_id | INT | 指名ガイドID（オプション） |
| request_type | ENUM | 依頼タイプ（外出, 自宅） |
| destination_address | TEXT | 目的地住所 |
| masked_address | VARCHAR(255) | マスキング済み住所 |
| meeting_place | VARCHAR(255) | 待ち合わせ場所 |
| service_content | TEXT | サービス内容 |
| request_date | DATE | 依頼日 |
| request_time | TIME | 依頼時刻 |
| start_time | TIME | 開始時刻 |
| end_time | TIME | 終了時刻 |
| duration | INT | 所要時間（分） |
| notes | TEXT | 備考 |
| formatted_notes | TEXT | フォーマット済み備考 |
| guide_gender | ENUM | 希望ガイド性別（オプション） |
| guide_age | VARCHAR(50) | 希望ガイド年齢（オプション） |
| status | ENUM | ステータス（pending, matched, completed, cancelled） |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### matchings（マッチング）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| request_id | INT | 依頼ID（外部キー） |
| user_id | INT | ユーザーID（外部キー） |
| guide_id | INT | ガイドID（外部キー） |
| matched_at | TIMESTAMP | マッチング成立日時 |
| completed_at | TIMESTAMP | 完了日時 |
| report_completed_at | TIMESTAMP | 報告書完了日時 |
| status | ENUM | ステータス（matched, in_progress, completed, cancelled） |

#### reports（報告書）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| matching_id | INT | マッチングID（外部キー） |
| request_id | INT | 依頼ID（外部キー） |
| guide_id | INT | ガイドID（外部キー） |
| user_id | INT | ユーザーID（外部キー） |
| service_content | TEXT | サービス内容 |
| report_content | TEXT | 報告内容 |
| actual_date | DATE | 実施日 |
| actual_start_time | TIME | 実際の開始時刻 |
| actual_end_time | TIME | 実際の終了時刻 |
| status | ENUM | ステータス（draft, submitted, user_approved, admin_approved, approved, revision_requested） |
| revision_notes | TEXT | 修正依頼コメント |
| submitted_at | TIMESTAMP | 提出日時 |
| user_approved_at | TIMESTAMP | ユーザー承認日時 |
| admin_approved_at | TIMESTAMP | 管理者承認日時 |
| approved_at | TIMESTAMP | 承認日時 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### user_monthly_limits（月次限度時間）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| user_id | INT | ユーザーID（外部キー） |
| year | INT | 年 |
| month | INT | 月 |
| limit_hours | DECIMAL(10,2) | 限度時間（時間） |
| used_hours | DECIMAL(10,2) | 使用時間（時間） |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### guide_acceptances（ガイド承諾）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| request_id | INT | 依頼ID（外部キー） |
| guide_id | INT | ガイドID（外部キー） |
| status | ENUM | ステータス（pending, accepted, declined） |
| admin_decision | ENUM | 管理者判定（pending, approved, rejected） |
| user_selected | BOOLEAN | ユーザー選択フラグ |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### chat_messages（チャットメッセージ）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| matching_id | INT | マッチングID（外部キー） |
| sender_id | INT | 送信者ID（外部キー） |
| message | TEXT | メッセージ内容 |
| read_at | TIMESTAMP | 既読日時 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### announcements（お知らせ）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| title | VARCHAR(255) | タイトル |
| content | TEXT | 内容 |
| target_audience | ENUM | 対象者（user, guide, all） |
| created_by | INT | 作成者ID（外部キー） |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### user_profiles（ユーザープロフィール）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| user_id | INT | ユーザーID（外部キー） |
| recipient_number | VARCHAR(50) | 受給者証番号 |
| interview_date_1 | DATE | 面接希望日1 |
| interview_date_2 | DATE | 面接希望日2（オプション） |
| interview_date_3 | DATE | 面接希望日3（オプション） |
| application_reason | TEXT | 応募理由 |
| visual_disability_status | TEXT | 視覚障害の状態 |
| disability_support_level | VARCHAR(10) | 障害者支援レベル |
| daily_life_situation | TEXT | 日常生活の状況 |
| admin_comment | TEXT | 管理者コメント |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

#### guide_profiles（ガイドプロフィール）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | INT | 主キー |
| guide_id | INT | ガイドID（外部キー） |
| employee_number | VARCHAR(50) | 従業員番号 |
| introduction | TEXT | 自己紹介 |
| application_reason | TEXT | 応募理由 |
| goal | TEXT | 目標 |
| preferred_work_hours | TEXT | 希望勤務時間 |
| available_areas | JSON | 対応可能エリア |
| available_days | JSON | 対応可能曜日 |
| available_times | JSON | 対応可能時間帯 |
| qualifications | JSON | 資格情報 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

### リレーションシップ

```
users
  ├── user_profiles (1:1)
  ├── guide_profiles (1:1)
  ├── requests (1:N)
  ├── guide_acceptances (1:N, guide_id)
  ├── matchingsAsUser (1:N, user_id)
  ├── matchingsAsGuide (1:N, guide_id)
  ├── chat_messages (1:N, sender_id)
  ├── reportsAsUser (1:N, user_id)
  └── reportsAsGuide (1:N, guide_id)

requests
  ├── user (N:1)
  ├── guide_acceptances (1:N)
  ├── matching (1:1)
  └── report (1:1)

matchings
  ├── request (N:1)
  ├── user (N:1)
  ├── guide (N:1)
  ├── chat_messages (1:N)
  └── report (1:1)

reports
  ├── matching (N:1)
  ├── request (N:1)
  ├── user (N:1)
  └── guide (N:1)
```

---

## 認証・認可システム

### 認証方式

#### 1. セッション認証（Web）

- **用途**: Bladeテンプレートを使用するWebページ
- **実装**: Laravel標準のセッション認証
- **ミドルウェア**: `auth`
- **ルート**: `routes/web.php`

#### 2. JWT認証（API）

- **用途**: APIエンドポイント、SPA（Reactアプリ）
- **実装**: Laravel Sanctum
- **ミドルウェア**: `auth:api`
- **ルート**: `routes/api.php`
- **ヘッダー**: `Authorization: Bearer <token>`

### ロールベースアクセス制御（RBAC）

#### ロール

1. **user**: 視覚障害者（サービス利用者）
2. **guide**: ガイドヘルパー（サービス提供者）
3. **admin**: 管理者（システム管理者）

#### ミドルウェア

- **`role:user`**: ユーザーのみアクセス可能
- **`role:guide`**: ガイドのみアクセス可能
- **`role:admin`**: 管理者のみアクセス可能

### 認証フロー

#### ユーザー登録

1. ユーザーが登録フォームに入力
2. バリデーション実行
3. パスワードをハッシュ化して保存
4. `is_allowed = false`（管理者承認待ち）
5. メール確認（`email_confirmed = false`）
6. JWTトークン発行（登録成功時）

#### ログイン

1. メールアドレスとパスワードで認証
2. `is_allowed = true` かつ `email_confirmed = true` の場合のみログイン可能
3. セッション作成（Web）またはJWTトークン発行（API）

#### 管理者承認

1. 管理者がユーザー/ガイドを承認
2. `is_allowed = true` に更新
3. 承認メール送信

---

## 機能一覧

### ユーザー（視覚障害者）機能

#### 1. 認証・登録

- ユーザー登録（面接希望日、応募理由、視覚障害の状態などを入力）
- ログイン/ログアウト
- プロフィール編集

#### 2. 依頼管理

- **依頼作成**
  - 依頼タイプ選択（外出/自宅）
  - 目的地住所入力
  - サービス内容入力
  - 依頼日時設定
  - 希望ガイド条件設定（性別、年齢）
  - 音声入力対応（備考欄）
- **依頼一覧**: 自分の依頼一覧を確認
- **応募者確認**: 依頼に応募したガイド一覧を確認
- **ガイド選択**: 応募者の中からガイドを選択

#### 3. マッチング管理

- マッチング成立済みの一覧を確認
- マッチング詳細を確認

#### 4. チャット機能

- マッチング成立後、ガイドとチャット
- 未読メッセージ数の表示

#### 5. 報告書管理

- 報告書の確認
- 報告書の承認/修正依頼

#### 6. お知らせ

- お知らせ一覧の確認
- お知らせの既読管理

#### 7. 月次限度時間

- 当月の限度時間・使用時間・残時間を確認
- 過去の月次限度時間一覧を確認

### ガイド機能

#### 1. 認証・登録

- ガイド登録（応募理由、目標、資格情報などを入力）
- ログイン/ログアウト
- プロフィール編集（対応可能エリア・日時設定）

#### 2. 依頼管理

- **利用可能な依頼一覧**: 自分の対応可能エリア・日時に合致する依頼を確認
- **依頼承諾**: 依頼を承諾
- **依頼辞退**: 依頼を辞退

#### 3. マッチング管理

- マッチング成立済みの一覧を確認
- マッチング詳細を確認

#### 4. チャット機能

- マッチング成立後、ユーザーとチャット
- 未読メッセージ数の表示

#### 5. 報告書管理

- **報告書作成**: マッチング完了後、報告書を作成
- **報告書提出**: 報告書を提出（ユーザー承認待ち）
- **報告書修正**: 修正依頼があった場合、報告書を修正

### 管理者機能

#### 1. 認証

- 管理者ログイン/ログアウト

#### 2. ユーザー管理

- ユーザー一覧の確認
- ユーザーの承認/拒否
- ユーザープロフィール追加情報の編集（受給者証番号、管理者コメント）
- ユーザーの月次限度時間設定

#### 3. ガイド管理

- ガイド一覧の確認
- ガイドの承認/拒否
- ガイドプロフィール追加情報の編集（従業員番号）

#### 4. 依頼管理

- 依頼一覧の確認
- 依頼の詳細確認

#### 5. マッチング管理

- **自動マッチング設定**: 自動マッチングのON/OFF切り替え
- **手動承認**: 自動マッチングOFF時、マッチングを手動で承認/却下
- **承諾一覧**: ガイド承諾一覧の確認

#### 6. 報告書管理

- 報告書一覧の確認
- 報告書の承認
- 報告書CSV出力
- 利用実績CSV出力

#### 7. 統計情報

- 全体統計（ユーザー数、ガイド数、依頼数、マッチング数、承認待ち数）
- ユーザー統計（総数、アクティブ、承認待ち、拒否）

#### 8. お知らせ管理

- お知らせの作成/編集/削除
- お知らせ一覧の確認

#### 9. メール通知設定

- メールテンプレートの編集
- メール通知設定の有効/無効切り替え

#### 10. 操作ログ

- 管理操作ログの確認

---

## マッチングフロー

### 自動マッチング（ON時）

```
1. ユーザーが依頼を作成
   ↓
2. ガイドが依頼を承諾
   ↓
3. 自動的にマッチング成立（status: 'matched'）
   ↓
4. ユーザーとガイドに通知
```

### 自動マッチング（OFF時）

```
1. ユーザーが依頼を作成
   ↓
2. ガイドが依頼を承諾
   ↓
3. 管理者の承認待ち（admin_decision: 'pending'）
   ↓
4. 管理者が承認/却下
   ↓
5. 承認時: マッチング成立（status: 'matched'）
   却下時: マッチング不成立
```

### ユーザー選択によるマッチング

```
1. ユーザーが依頼を作成
   ↓
2. 複数のガイドが依頼を承諾
   ↓
3. ユーザーが応募者一覧からガイドを選択
   ↓
4. 自動マッチングON: 即座にマッチング成立
   自動マッチングOFF: 管理者の承認待ち
```

### 指名依頼

```
1. ユーザーが依頼を作成時にガイドを指名
   ↓
2. 指名されたガイドに通知
   ↓
3. ガイドが承諾
   ↓
4. 自動マッチングON: 即座にマッチング成立
   自動マッチングOFF: 管理者の承認待ち
```

---

## 報告書フロー

```
1. ガイドが報告書を作成（status: 'draft'）
   ↓
2. ガイドが報告書を提出（status: 'submitted'）
   ↓
3. ユーザーが報告書を確認
   ↓
4. ユーザーが承認（status: 'user_approved'）
   または修正依頼（status: 'revision_requested'）
   ↓
5. 修正依頼の場合、ガイドが修正して再提出
   ↓
6. 管理者が報告書を承認（status: 'admin_approved'）
   ↓
7. 利用時間が計上される（user_monthly_limits.used_hours に加算）
```

### 報告書ステータス

- **draft**: 下書き
- **submitted**: 提出済み（ユーザー承認待ち）
- **user_approved**: ユーザー承認済み（管理者承認待ち）
- **admin_approved**: 管理者承認済み（確定）
- **approved**: 承認済み（確定、互換性のため）
- **revision_requested**: 修正依頼中

---

## 利用時間管理システム

### 概要

ユーザーの月次限度時間を設定し、使用時間を追跡するシステムです。

### 利用時間の計上タイミング

- **計上タイミング**: 管理者承認時（`adminApproveReport`）
- **計上されない**: ユーザー承認のみでは計上されない

### 利用時間の計算方法

1. **開始時刻と終了時刻の取得**
   - `actual_date`: 実施日（依頼日に固定）
   - `actual_start_time`: 開始時刻（HH:MM形式）
   - `actual_end_time`: 終了時刻（HH:MM形式）

2. **時間差の計算**
   - 開始時刻と終了時刻の差分を**分単位**で計算
   - 終了時刻が開始時刻より小さい場合（例：23:00 → 01:00）、翌日とみなす

3. **時間への変換**
   - 分を時間に変換（分 ÷ 60）
   - **小数点第1位まで**に丸める

### 月次限度時間の管理

#### データ構造

- **テーブル**: `user_monthly_limits`
- **カラム**:
  - `limit_hours`: 限度時間（管理者が設定）
  - `used_hours`: 使用時間（報告書確定時に自動加算）
  - `remaining_hours`: 残時間（`limit_hours - used_hours`）

#### 依頼作成時の残時間チェック

- 依頼作成時に、残時間が十分かチェック
- 残時間が不足している場合、依頼作成を拒否

#### 表示

- **ユーザーダッシュボード**: 当月の限度時間・使用時間・残時間を表示
- **プロフィール画面**: 実績時間（報告書確定ベース・月別積算）を表示

### 関連API

- `GET /api/users/me/monthly-limit`: ユーザー自身の月次限度時間を取得
- `GET /api/users/me/monthly-limits`: ユーザー自身の月次限度時間一覧を取得
- `PUT /api/admin/users/{id}/monthly-limit`: 管理者がユーザーの月次限度時間を設定
- `GET /api/admin/users/{id}/monthly-limits`: 管理者がユーザーの月次限度時間一覧を取得

---

## API仕様

### ベースURL

- **Web**: `/`
- **API**: `/api`

### 認証方式

- **Web**: セッション認証（`auth`ミドルウェア）
- **API**: JWT認証（`Authorization: Bearer <token>`）

### 主要エンドポイント

詳細なAPI仕様は `API_SPECIFICATION.md` を参照してください。

#### 認証

- `POST /api/auth/register`: ユーザー登録
- `POST /api/auth/login`: ログイン
- `GET /api/auth/user`: 現在のユーザー情報取得
- `POST /api/auth/logout`: ログアウト

#### 依頼

- `GET /api/requests/my-requests`: 依頼一覧取得（ユーザー）
- `GET /api/requests/guide/available`: 利用可能な依頼一覧取得（ガイド）
- `GET /api/requests/{id}/applicants`: 依頼の応募者一覧取得
- `POST /api/requests/{id}/select-guide`: ガイド選択

#### マッチング

- `POST /api/matchings/accept`: 依頼承諾（ガイド）
- `POST /api/matchings/decline`: 依頼辞退（ガイド）
- `GET /api/matchings/my-matchings`: マッチング一覧取得

#### チャット

- `POST /api/chat/messages`: メッセージ送信
- `GET /api/chat/messages/{matchingId}`: メッセージ一覧取得
- `GET /api/chat/unread-count`: 未読メッセージ数取得

#### お知らせ

- `GET /api/announcements`: お知らせ一覧取得
- `POST /api/announcements/{id}/read`: お知らせを既読にする

#### 管理者

- `GET /api/admin/requests`: 依頼一覧取得
- `GET /api/admin/acceptances`: 承諾一覧取得
- `GET /api/admin/reports`: 報告書一覧取得
- `POST /api/admin/matchings/approve`: マッチング手動承認
- `POST /api/admin/matchings/reject`: マッチング手動却下
- `GET /api/admin/users`: ユーザー一覧取得
- `GET /api/admin/guides`: ガイド一覧取得
- `PUT /api/admin/users/{id}/approve`: ユーザー承認
- `PUT /api/admin/guides/{id}/approve`: ガイド承認

### レスポンス形式

#### 成功レスポンス

```json
{
  "message": "処理が成功しました",
  "data": { ... }
}
```

#### エラーレスポンス

```json
{
  "error": "エラーメッセージ"
}
```

#### バリデーションエラー

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

### ステータスコード

- `200 OK`: リクエスト成功
- `201 Created`: リソース作成成功
- `400 Bad Request`: バリデーションエラー、不正なリクエスト
- `401 Unauthorized`: 認証が必要、認証トークンが無効
- `403 Forbidden`: 権限が不足
- `404 Not Found`: リソースが見つからない
- `500 Internal Server Error`: サーバーエラー

---

## セキュリティ

### 認証・認可

- **パスワードハッシュ化**: bcryptを使用
- **CSRF保護**: Laravel標準のCSRF保護
- **ロールベースアクセス制御**: ミドルウェアによる権限チェック
- **JWT認証**: トークンベースの認証

### データ保護

- **住所マスキング**: ガイドにはマスキング済み住所を表示
- **SQLインジェクション対策**: Eloquent ORMによるパラメータバインディング
- **XSS対策**: Bladeテンプレートの自動エスケープ

### セキュリティ設定

- **セッション設定**: セキュアなセッション設定
- **CORS設定**: API用のCORS設定
- **レート制限**: 管理者APIにレート制限を適用

---

## アクセシビリティ

### 対応内容

- **ARIA属性**: 適切なARIA属性の適用
- **スクリーンリーダー対応**: スクリーンリーダーでの読み上げに対応
- **適切なラベリング**: フォーム要素に適切なラベルを設定
- **キーボードナビゲーション**: キーボードのみで操作可能

### 音声入力対応

- 依頼作成時の備考欄で音声入力をサポート

---

## メール通知システム

### メールテンプレート

管理者がメールテンプレートを編集可能：

- ユーザー登録完了通知
- ガイド登録完了通知
- マッチング成立通知
- 報告書提出通知
- 報告書承認通知
- その他

### メール通知設定

- 各通知タイプの有効/無効を切り替え可能
- リマインダー日数の設定

### 実装

- **Laravel Mail**: SMTP経由でメール送信
- **メールドライバー**: SMTP、Mailgun、SendGridなどに対応

---

## 管理者機能

### ダッシュボード

- 全体統計の表示
- 承認待ち件数の表示
- 最近の操作ログの表示

### ユーザー・ガイド管理

- 一覧表示
- 承認/拒否
- プロフィール追加情報の編集
- 月次限度時間の設定

### マッチング管理

- 自動マッチング設定の切り替え
- 手動承認/却下
- 承諾一覧の確認

### 報告書管理

- 報告書一覧の確認
- 報告書の承認
- CSV出力（報告書一覧、利用実績）

### お知らせ管理

- お知らせの作成/編集/削除
- 対象者（ユーザー/ガイド/全員）の設定

### メール通知設定

- メールテンプレートの編集
- 通知設定の有効/無効切り替え

### 操作ログ

- 管理操作のログ記録
- ログ一覧の確認

---

## デプロイメント

### 環境要件

- **PHP**: 8.1以上
- **PostgreSQL**: 12以上
- **Composer**: 2.x
- **Node.js**: 18.x以上（フロントエンドビルド用）

### セットアップ手順

詳細なセットアップ手順は `docs/SETUP.md` を参照してください。

#### 1. 依存関係のインストール

```bash
# PHP依存関係
composer install

# Node.js依存関係（フロントエンド）
npm install
cd frontend && npm install
```

#### 2. 環境設定

```bash
# .envファイルの作成
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate

# JWT秘密鍵の生成（API認証用）
php artisan jwt:secret
```

#### 3. データベース設定

```bash
# マイグレーション実行
php artisan migrate

# シーダー実行（オプション）
php artisan db:seed
```

#### 4. アセットビルド

```bash
# フロントエンドビルド
npm run build:frontend

# Laravelアセットビルド
npm run build
```

#### 5. サーバー起動

```bash
# 開発サーバー
php artisan serve

# 本番環境では、Webサーバー（Nginx/Apache）とPHP-FPMを使用
```

### 本番環境の設定

- **Webサーバー**: NginxまたはApache
- **PHP-FPM**: PHPプロセス管理
- **データベース**: PostgreSQL（本番用）
- **SSL/TLS**: HTTPS対応
- **環境変数**: `.env`ファイルの適切な設定

### Railwayデプロイメント

`railway.json` を使用してRailwayにデプロイ可能です。

---

## 関連ドキュメント

- **API_SPECIFICATION.md**: 詳細なAPI仕様
- **docs/SETUP.md**: セットアップガイド
- **docs/IMPLEMENTATION_STATUS.md**: 実装完了状況
- **docs/DATABASE.md**: データベースドキュメント
- **docs/USAGE_TIME_SYSTEM.md**: 利用時間管理システムの詳細
- **docs/ADMIN_FEATURES.md**: 管理者機能の詳細
- **docs/TROUBLESHOOTING.md**: トラブルシューティング

---

## バージョン情報

- **バージョン**: 1.0.0
- **最終更新**: 2024年1月
- **ライセンス**: ISC

---

## 変更履歴

### 2024-01-17

- ユーザー向け月次限度時間APIエンドポイントを追加
- ユーザーダッシュボードに月次限度時間カードを追加

### 2024-01-15

- 利用時間計上システムの修正（`time`型と`date`型の組み合わせ処理を修正）

---

## 連絡先

プロジェクトに関する質問や問題がある場合は、プロジェクト管理者に連絡してください。


