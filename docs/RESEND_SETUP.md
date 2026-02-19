# Resend でメール送信（本番設定・トラブルシュート）

本番で Resend を使っているがメールが届かない場合の確認手順です。

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

## 4. 設定反映の確認（本番サーバーで実行）

```bash
# キャッシュを消してから再度キャッシュ（本番で config:cache を使っている場合）
php artisan config:clear
php artisan config:cache

# 現在のメールドライバーを確認（tinker で）
php artisan tinker
>>> config('mail.default')
=> "resend"
>>> config('services.resend.key')
=> "re_xxxx..."
```

`mail.default` が `resend`、`services.resend.key` に API キーが出ていれば、Laravel 側の設定は問題ありません。

## 5. テスト送信

- ブラウザで `/test-mail` にアクセス（テスト用ルートが有効な場合）。
- または `php artisan tinker` で:

```php
Mail::raw('Resend テスト', function ($m) {
    $m->to('受信確認用のあなたのメールアドレス')->subject('Resend Test');
});
```

エラーが出る場合は、表示されたメッセージと `storage/logs/laravel.log` を確認してください。

## 6. Resend ダッシュボードで確認

- [Resend → Emails](https://resend.com/emails) で送信履歴とステータス（Sent / Bounced / など）を確認できます。
- 送信は成功しているが「届かない」場合は、迷惑メールフォルダや受信サーバーのブロックを確認してください。

## 7. まとめチェックリスト

- [ ] `composer require resend/resend-laravel` 済み
- [ ] `.env` に `MAIL_MAILER=resend`
- [ ] `.env` に `RESEND_API_KEY=re_...`（正しいキー）
- [ ] `.env` に `MAIL_FROM_ADDRESS`（`onboarding@resend.dev` または認証済みドメインのアドレス）
- [ ] 環境変数変更後に `config:clear` → `config:cache`（本番）
- [ ] キュー利用時は `queue:work` が動いている
- [ ] Resend ダッシュボードで送信履歴・エラーを確認

これでも届かない場合は、`storage/logs/laravel.log` の直近のエラーと、Resend ダッシュボードの該当メールのステータスを確認すると原因を特定しやすくなります。
