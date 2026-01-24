# 開発ノート

このドキュメントには、開発中に発見した重要な注意点やベストプラクティスを記録します。

## Alpine.js に関する注意点

### `<template x-if>` の制約

Alpine.jsの`<template x-if>`ディレクティブを使用する際の重要な制約があります。

#### 問題：複数の`<template x-if>`を直接ネストできない

**❌ 正しく動作しない例：**

```html
<template x-if="condition1">
    <template x-if="condition2">
        <p>表示1</p>
    </template>
    <template x-if="condition3">
        <p>表示2</p>
    </template>
</template>
```

この構造では、外側の`<template x-if>`の中に複数の`<template x-if>`を直接ネストしているため、Alpine.jsはどれをルート要素として扱うべきか判断できず、正しく動作しません。

#### 解決方法：コンテナ要素でラップする

**✅ 正しく動作する例：**

```html
<template x-if="condition1">
    <div>  <!-- ← この<div>が1つのルート要素として機能 -->
        <template x-if="condition2">
            <p>表示1</p>
        </template>
        <template x-if="condition3">
            <p>表示2</p>
        </template>
    </div>
</template>
```

#### 重要なポイント

1. **`<template x-if>`は必ず1つのルート要素を持つ必要がある**
   - 外側の`<template x-if>`の中には、必ず1つのルート要素（例：`<div>`）が必要です

2. **複数の要素を条件分岐したい場合**
   - `<div>`などのコンテナ要素でラップしてから、その中に複数の`<template x-if>`を配置します

3. **`<template x-show>`との違い**
   - `<template x-show>`はこの制約がありません（要素は常にDOMに存在し、CSSの`display`で表示/非表示を切り替えるため）

#### 実装例（`resources/views/requests/index.blade.php`より）

```html
<template x-if="!applicantsLoading[request.id] && Array.isArray(applicantsMap[request.id])">
    <div>  <!-- ← コンテナ要素でラップ -->
        <template x-if="applicantsMap[request.id].length === 0">
            <p class="info-text">応募しているガイドはまだいません。</p>
        </template>
        <template x-if="applicantsMap[request.id].length > 0">
            <div class="applicants-list">
                <!-- 応募ガイドのリスト -->
            </div>
        </template>
    </div>
</template>
```

### 型の比較について

Alpine.jsのテンプレート内で数値と文字列を比較する場合、厳密等価演算子（`===`）ではなく緩い等価演算子（`==`）を使用することを推奨します。

**理由：**
- APIから返されるデータのIDが数値の場合と文字列の場合がある
- `===`は型も含めて比較するため、`4 === "4"`は`false`になる
- `==`は型変換を行うため、`4 == "4"`は`true`になる

**例：**
```html
<!-- 推奨 -->
<template x-if="expandedRequestId == request.id">
    ...
</template>

<!-- 非推奨（型が一致しない場合に動作しない） -->
<template x-if="expandedRequestId === request.id">
    ...
</template>
```

## プロフィール編集画面での入力可能項目の制御

### 概要
プロフィール編集画面では、ユーザーのロール（管理者、ユーザー、ガイド）に応じて、編集可能な項目が制御されています。

### 入力制御の詳細

#### 1. 基本情報（全ユーザー共通）

| 項目 | 管理者 | ユーザー/ガイド | 備考 |
|------|--------|----------------|------|
| お名前 | ✅ 編集可能 | ❌ 閲覧のみ（`readonly disabled`） | バックエンドでも管理者のみ更新可能 |
| 電話番号 | ✅ 編集可能 | ❌ 閲覧のみ（`readonly disabled`） | バックエンドでも管理者のみ更新可能 |
| 住所 | ✅ 編集可能 | ❌ 閲覧のみ（`readonly disabled`） | バックエンドでも管理者のみ更新可能 |
| 年齢 | ❌ 閲覧のみ | ❌ 閲覧のみ | 常に`readonly disabled`（生年月日から自動計算） |
| 生年月日 | ❌ 閲覧のみ | ❌ 閲覧のみ | 常に`readonly disabled` |

#### 2. ユーザー（`role = 'user'`）の場合

| 項目 | 管理者 | ユーザー | 備考 |
|------|--------|----------|------|
| 連絡手段 | ✅ 編集可能 | ✅ 編集可能 | 全員編集可能 |
| 備考 | ✅ 編集可能 | ✅ 編集可能 | 全員編集可能 |
| 受給者証番号 | 表示されない | ❌ 閲覧のみ | 管理者には表示されない |
| 自己紹介 | ✅ 編集可能 | ✅ 編集可能 | 全員編集可能 |

#### 3. ガイド（`role = 'guide'`）の場合

| 項目 | 管理者 | ガイド | 備考 |
|------|--------|--------|------|
| 自己紹介 | ✅ 編集可能 | ✅ 編集可能 | 全員編集可能 |
| 対応可能エリア | ✅ 編集可能 | ✅ 編集可能 | チェックボックス（複数選択可） |
| 対応可能日 | ✅ 編集可能 | ✅ 編集可能 | チェックボックス（複数選択可） |
| 対応可能時間帯 | ✅ 編集可能 | ✅ 編集可能 | チェックボックス（複数選択可） |
| 従業員番号 | ❌ 閲覧のみ | ❌ 閲覧のみ | 常に`readonly disabled` |
| 運営側からのコメント | 表示されない | ❌ 閲覧のみ | 管理者には表示されない |

### 実装箇所

#### フロントエンド（Blade版）
**ファイル**: `resources/views/profile.blade.php`

- 管理者チェック: `@if(!$user->isAdmin())` で`readonly disabled`属性を追加
- ユーザー/ガイドの分岐: `@if($user->isUser())` / `@if($user->isGuide())` で表示項目を制御

#### フロントエンド（React版）
**ファイル**: `frontend/src/pages/Profile.jsx`

- 管理者チェック: `readOnly={!isAdmin}` `disabled={!isAdmin}` で制御
- ユーザー/ガイドの分岐: `{!isGuide && ...}` / `{isGuide && ...}` で表示項目を制御

#### バックエンド
**ファイル**: `app/Http/Controllers/ProfileController.php`

```php
// 管理者のみ氏名・電話・住所を更新可能
if ($user->isAdmin() && isset($validated['name'])) {
    $user->update([
        'name' => $validated['name'],
        'phone' => $validated['phone'] ?? $user->phone,
        'address' => $validated['address'] ?? $user->address,
    ]);
}
```

**ファイル**: `backend/routes/users.js`

```javascript
// ユーザー情報更新（氏名・電話番号は管理者のみ）
if (req.user.role === 'admin') {
    // name, phone, address の更新処理
}
```

### セキュリティ上の注意点

1. **フロントエンドとバックエンドの両方で制御**
   - フロントエンドで`readonly disabled`を設定しても、HTTPリクエストを直接送信すれば回避可能
   - バックエンドでも管理者チェックを実装し、二重に保護

2. **ロールベースのアクセス制御**
   - `$user->isAdmin()`, `$user->isUser()`, `$user->isGuide()` メソッドを使用
   - バックエンドでは`req.user.role`または`$user->role`でチェック

3. **閲覧専用項目**
   - `readonly disabled`属性で編集を防止
   - バックエンドでは更新処理を実装しない

### 関連ファイル
- `resources/views/profile.blade.php` - Blade版のプロフィール編集画面
- `frontend/src/pages/Profile.jsx` - React版のプロフィール編集画面
- `app/Http/Controllers/ProfileController.php` - プロフィール更新コントローラー（Laravel）
- `backend/routes/users.js` - プロフィール更新API（Node.js）

---

## その他の開発ノート

今後、開発中に発見した重要な注意点やベストプラクティスをここに追加していきます。

---

## 依頼一覧から完了したマッチングの除外

### 概要
報告書が承認されてマッチングが完了（`status = 'completed'`）した場合、ユーザーの依頼一覧からその依頼を非表示にする機能を実装しました。

### 実装内容

#### 問題点
初期実装では、完了したマッチングに関連する依頼が依頼一覧に表示され続けていました。`matchedGuides()`メソッドでは完了したマッチングを除外していましたが、依頼一覧自体（`myRequests()`）では完了したマッチングを考慮していませんでした。

#### 解決方法

**ファイル**: `app/Http/Controllers/Api/RequestController.php`

1. **`myRequests()`メソッドの修正**
   - 完了したマッチングに関連する依頼IDを取得
   - `whereNotIn()`を使用して、完了したマッチングに関連する依頼を依頼一覧から除外

```php
public function myRequests()
{
    $user = Auth::user();
    
    // 完了したマッチングに関連する依頼IDを取得
    $completedMatchingRequestIds = Matching::where('user_id', $user->id)
        ->where('status', 'completed')
        ->pluck('request_id')
        ->toArray();
    
    // 完了したマッチングに関連する依頼を除外
    $requests = RequestModel::where('requests.user_id', $user->id)
        ->whereNotIn('requests.id', $completedMatchingRequestIds)
        // ... 以下略
}
```

2. **`matchedGuides()`メソッドの修正**
   - マッチングのステータスが`completed`のものを除外する条件を追加
   - `$matched`と`$selected`の両方のクエリで除外条件を適用

```php
->where(function($query) {
    // マッチングのステータスが 'completed' でないもの、またはマッチングが存在しないもの
    $query->whereNull('matchings.status')
          ->orWhere('matchings.status', '!=', 'completed');
})
```

### 動作条件
- マッチングのステータスが`completed`の場合、そのマッチングに関連する依頼は依頼一覧から非表示になります
- 報告書が承認されると、マッチングのステータスが`completed`に更新されます（`backend/routes/reports.js`で実装）

### 確認方法
1. ユーザーアカウントでログインし、依頼一覧ページ（`/requests`）にアクセス
2. 完了したマッチングに関連する依頼が表示されないことを確認
3. データベースクエリ（`CHECK_COMPLETED_MATCHINGS.sql`）を使用して、完了したマッチングの状態を確認

### 関連ファイル
- `app/Http/Controllers/Api/RequestController.php` - 修正箇所
- `backend/routes/reports.js` - 報告書承認時のマッチングステータス更新
- `CHECK_COMPLETED_MATCHINGS.sql` - データベース状態確認用クエリ

### 修正日時
2026年1月

---

