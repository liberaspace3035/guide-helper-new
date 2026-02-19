# Resend でメール送信（本番設定・トラブルシュート）

本番で Resend を使っているがメールが届かない場合の確認手順です。

## 0. まず診断コマンドを実行する

**メールが送信されない**ときは、次のコマンドで設定と送信を確認できます。

```bash
# 設定のみ表示（ドライバ・送信元・Resend キー有無など）
php artisan mail:check

# テスト送信まで実行（送信先を指定）
php artisan mail:check あなたのメールアドレス@example.com
```

- ドライバが `log` のまま → 実際には送信されません。`MAIL_MAILER=resend`（または smtp）に変更してください。
- Resend で「RESEND_API_KEY 未設定」と出る → `.env` に `RESEND_API_KEY=re_...` を追加し、`config:clear` / `config:cache` をやり直してください。
- テスト送信で例外が出る → 表示されたエラーメッセージと [Resend ダッシュボード](https://resend.com/emails) の送信履歴を確認してください。

## 1. パッケージのインストール

Resend を使うには **resend/resend-laravel** が必要です（未導入の場合は以下を実行）。

```bash
composer require resend/resend-laravel
```

## 2. 本番の環境変数

本番サーバーの `.env` に以下が**すべて**設定されているか確認してください。

```env
# メールドライバーを resend にすること
MAIL_MAILER=resend

# Resend API キー（Resend ダッシュボード → API Keys で発行）
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxxxxx

# 送信元アドレス（重要：下記を参照）
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="${APP_NAME}"
```

### 送信元アドレス（MAIL_FROM_ADDRESS）について

- **テスト・開発**: `onboarding@resend.dev` を使うと、ドメイン認証なしで送信できます。
- **本番で独自ドメインを使う場合**: Resend でドメインを追加・認証し、そのドメインのアドレス（例: `noreply@yourdomain.com`）を `MAIL_FROM_ADDRESS` に設定してください。
- **未認証のドメインや誤ったアドレス**だと、Resend が送信を拒否したり、届かない原因になります。

## 3. よくある「届かない」原因と対処

| 原因 | 対処 |
|------|------|
| **MAIL_MAILER が resend になっていない** | `.env` で `MAIL_MAILER=resend` にし、`php artisan config:clear` を実行。本番で `config:cache` している場合は `php artisan config:cache` をやり直す。 |
| **RESEND_API_KEY が未設定・間違い** | Resend ダッシュボードの [API Keys](https://resend.com/api-keys) でキーを確認。`.env` の `RESEND_API_KEY=re_...` にコピー（前後にスペースや改行を入れない）。 |
| **送信元アドレスが不正** | テスト時は `MAIL_FROM_ADDRESS=onboarding@resend.dev`。本番は Resend で認証済みドメインのアドレスを指定。 |
| **設定キャッシュが古い** | 本番で `php artisan config:cache` を使っている場合、環境変数変更後に必ず `php artisan config:clear` のあと `php artisan config:cache` を実行。 |
| **キー名の typo** | 正しくは **RESEND_API_KEY**（`RESEND_KEY` ではない）。`config/services.php` の `resend.key` は `env('RESEND_API_KEY')` を参照している。 |
| **メールがキューに入ったまま** | キューで送信している場合は、ワーカーが動いていないと送信されない。`php artisan queue:work` を実行するか、Supervisor 等で常時起動する。 |
| **Connection timed out to smtp.resend.com:587** | Railway など多くの PaaS は**送信 SMTP（ポート 587）をブロック**します。`MAIL_MAILER=smtp` と `MAIL_HOST=smtp.resend.com` ではなく、**MAIL_MAILER=resend** と **RESEND_API_KEY** を使って Resend の **API 経由**で送信してください（HTTPS はブロックされません）。 |

## 4. キャッシュクリア（最重要・Railway で環境変数変更後）

Laravel は設定をキャッシュするため、**Railway の Variables を更新しても古いキャッシュが残っていると新しい設定が読み込まれません。**

**方法 A: ブラウザから強制クリア**

アプリに `/clear` ルートを追加済みの場合、以下にアクセスするだけです。

```
https://あなたのアプリ.up.railway.app/clear
```

→ 「Cache Cleared」と表示されれば config と cache がクリアされています。そのあと `/test-mail` や `/mail-debug` で再テストしてください。

**方法 B: コマンドで実行（Web ターミナルやデプロイ後スクリプトで）**

```bash
php artisan config:clear
php artisan cache:clear
```

本番で `config:cache` を使っている場合は、上記のあと `php artisan config:cache` をやり直すか、再デプロイしてください。

## 5. 設定反映の確認（本番サーバーで実行）

```bash
# 現在のメールドライバーを確認（tinker で）
php artisan tinker
>>> config('mail.default')
=> "resend"
>>> config('services.resend.key')
=> "re_xxxx..."
```

`mail.default` が `resend`、`services.resend.key` に API キーが出ていれば、Laravel 側の設定は問題ありません。

## 6. エラー内容の特定（デバッグ）

「送信されない」のが **エラーで止まっている** のか **成功しているが届かない** のかを切り分けます。

**ブラウザでデバッグ用 URL にアクセス:**

```
https://あなたのアプリ.up.railway.app/mail-debug
```

- **「送信処理は成功しました」** → 設定は通っている。届かない場合は迷惑メール・受信サーバーのブロックを確認。
- **「送信失敗: ...」** → 表示されたエラーメッセージが重要です。
  - `Connection could not be established` → MAIL_PORT・MAIL_ENCRYPTION（smtp の場合）を確認。
  - `Authentication failed` → RESEND_API_KEY や MAIL_PASSWORD が正しいか、前後にスペースが入っていないか確認。
  - Resend のエラー → [Resend ダッシュボード](https://resend.com/emails) の送信履歴も確認。

**送信先について:** `/mail-debug` と `/test-mail` の送信先は **MAIL_TEST_TO**（未設定時は MAIL_FROM_ADDRESS）です。Railway の Variables に `MAIL_TEST_TO=受信確認用のあなたのメールアドレス` を設定してください。

## 7. キュー（Queue）の設定確認

メールを **キューで送信** している場合、Railway 上で **Worker（ワーカー）が動いていないとメールは送信されません。**

- `.env` で `QUEUE_CONNECTION=database` や `redis` になっていないか確認。
- **テストとして** 一時的に `QUEUE_CONNECTION=sync`（同期送信）に変えて試してみてください。届くようなら、ワーカーが動いていないことが原因です。

※ このアプリでは `EmailNotificationService` は `Mail::raw()` をその場で実行しており、キューには入れていません。別の箇所でキューを使っている場合のみ上記を確認してください。

## 8. テスト送信

- ブラウザで `/test-mail` にアクセス（テスト用ルートが有効な場合）。
- または `php artisan tinker` で:

```php
Mail::raw('Resend テスト', function ($m) {
    $m->to('受信確認用のあなたのメールアドレス')->subject('Resend Test');
});
```

エラーが出る場合は、表示されたメッセージと `storage/logs/laravel.log` を確認してください。

## 9. Resend ダッシュボードで確認

- [Resend → Emails](https://resend.com/emails) で送信履歴とステータス（Sent / Bounced / など）を確認できます。
- 送信は成功しているが「届かない」場合は、迷惑メールフォルダや受信サーバーのブロックを確認してください。

## 10. まとめチェックリスト

- [ ] `composer require resend/resend-laravel` 済み
- [ ] `.env` に `MAIL_MAILER=resend`
- [ ] `.env` に `RESEND_API_KEY=re_...`（正しいキー）
- [ ] `.env` に `MAIL_FROM_ADDRESS`（`onboarding@resend.dev` または認証済みドメインのアドレス）
- [ ] 環境変数変更後に `config:clear` → `config:cache`（本番）
- [ ] キュー利用時は `queue:work` が動いている
- [ ] Resend ダッシュボードで送信履歴・エラーを確認

これでも届かない場合は、`storage/logs/laravel.log` の直近のエラーと、Resend ダッシュボードの該当メールのステータスを確認すると原因を特定しやすくなります。
