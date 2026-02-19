# メール通知の確認方法

マッチング成立時にメール通知が正しく送信されるかを確認する方法を説明します。

## ✅ 重要な確認事項

**テストコマンドでメールが届いたということは、実際のマッチング成立時にもメールが届く仕組みが完成しています。**

テストコマンド（`php artisan test:email`）と実際のマッチング成立時は、**完全に同じメール送信機能**（`EmailNotificationService::sendMatchingNotification()`）を使用しています。詳細は [EMAIL_CONFIRMATION.md](./EMAIL_CONFIRMATION.md) を参照してください。

## メールが送信されないとき

**まず診断コマンドを実行**してください。

```bash
# 現在のメール設定を表示
php artisan mail:check

# テスト送信（送信先を指定）
php artisan mail:check あなたのメール@example.com
```

よくある原因:
- **MAIL_MAILER=log のまま** → 実際には送信されず、`storage/logs/laravel.log` にのみ出力されます。送信したい場合は `resend` または `smtp` に変更。
- **Resend 利用時**: `RESEND_API_KEY` 未設定、`MAIL_FROM_ADDRESS` が `onboarding@resend.dev` または認証済みドメインでない。→ [RESEND_SETUP.md](./RESEND_SETUP.md) を参照。
- **SMTP 利用時**: `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` の誤り、Gmail の場合はアプリパスワードが必要。
- **本番で設定を変えたあと** → `php artisan config:clear` のあと `php artisan config:cache` を実行していない。

## メールが実際に届くかどうか

テストコマンド `php artisan test:email matching your@email.com` を実行した場合、**メールの届き先は`.env`ファイルの設定によって変わります**：

| 設定 | 実際のメールに届くか | 確認方法 |
|------|---------------------|---------|
| `MAIL_MAILER=log` | ❌ 届かない | `storage/logs/laravel.log` を確認 |
| `MAIL_MAILER=smtp` (Mailtrap) | ❌ 届かない | MailtrapのWebインターフェースで確認 |
| `MAIL_MAILER=smtp` (Gmail設定) | ✅ **届く** | 実際のGmailメールボックスで確認 |
| `MAIL_MAILER=smtp` (その他のSMTP) | ✅ 届く（設定による） | 実際のメールボックスで確認 |
| `MAIL_MAILER=resend` (Resend) | ✅ 届く（設定による） | [RESEND_SETUP.md](./RESEND_SETUP.md) 参照 |

**実際のGmailに届けたい場合**は、[GmailのSMTP設定](#gmailを使用する場合)を行ってください。

## 目次
1. [メール設定の確認](#メール設定の確認)
2. [方法1: logドライバーを使用する（開発環境で推奨）](#方法1-logドライバーを使用する開発環境で推奨)
3. [方法2: Mailtrapを使用する（推奨）](#方法2-mailtrapを使用する推奨)
4. [方法3: 実際のメールサーバーを使用する](#方法3-実際のメールサーバーを使用する)
5. [メール送信のテスト方法](#メール送信のテスト方法)
6. [トラブルシューティング](#トラブルシューティング)

---

## メール設定の確認

まず、現在のメール設定を確認します。

### 1. `.env`ファイルの確認

`.env`ファイルに以下の設定があるか確認してください：

```env
MAIL_MAILER=log
# または
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. メールテンプレートの確認

メールテンプレートが正しく設定されているか確認します：

```bash
# データベースで確認
php artisan tinker
```

```php
// メールテンプレートの確認
\App\Models\EmailTemplate::where('template_key', 'matching_notification')->first();

// メール通知設定の確認
\App\Models\EmailNotificationSetting::where('notification_type', 'matching')->first();
```

---

## 方法1: logドライバーを使用する（開発環境で推奨）

最も簡単な方法です。メールは実際には送信されず、ログファイルに記録されます。

### 設定方法

`.env`ファイルに以下を設定：

```env
MAIL_MAILER=log
```

### 確認方法

1. マッチングを成立させる（ガイドが依頼を承諾し、自動マッチングが有効な場合）

2. ログファイルを確認：

```bash
# ログファイルの最新の内容を確認
tail -f storage/logs/laravel.log | grep -A 20 "matching"
```

または、ファイル全体を確認：

```bash
# macOS/Linux
cat storage/logs/laravel.log | grep -A 20 "matching"

# Windows (PowerShell)
Get-Content storage/logs/laravel.log | Select-String -Pattern "matching" -Context 0,20
```

3. ログファイル内で以下のような内容を確認：

```
[2024-01-01 12:00:00] local.INFO: メール送信成功 {"template_key":"matching_notification","user_id":1}
```

または、実際のメール内容が記録されている場合は：

```
To: user@example.com
Subject: マッチングが成立しました

マッチングが成立しました。チャットで詳細を確認してください。

マッチングID: 1
依頼タイプ: 外出支援
依頼日時: 2024-01-15 10:00
```

### メリット
- 設定が簡単
- メールサーバーが不要
- 送信履歴がログとして残る

### デメリット
- 実際のメールアドレスには届かない
- メールの見た目やレイアウトを確認できない

---

## 方法2: Mailtrapを使用する（推奨）

Mailtrapは、開発環境でメールをテストするための専用サービスです。実際にメールを送信しますが、実際のメールボックスには届かず、MailtrapのWebインターフェースで確認できます。

### 設定手順

#### 1. Mailtrapアカウントの作成

1. [Mailtrap](https://mailtrap.io/) にアクセス
2. 無料アカウントを作成（Freeプランで十分）
3. ログイン後、「Email Testing」→「Inboxes」を選択
4. デフォルトのInboxまたは新しいInboxを選択
5. 「SMTP Settings」タブを選択

#### 2. `.env`ファイルの設定

Mailtrapの設定情報を`.env`ファイルに追加：

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username  # MailtrapのSettingsから取得
MAIL_PASSWORD=your_mailtrap_password  # MailtrapのSettingsから取得
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### 3. 設定の反映

```bash
# キャッシュをクリア
php artisan config:clear
```

#### 4. メール送信のテスト

1. マッチングを成立させる
2. [Mailtrap](https://mailtrap.io/) にログイン
3. Inboxを確認すると、送信されたメールが表示されます
4. メールをクリックすると、件名・本文・HTML表示・プレーンテキスト表示などが確認できます

### メリット
- 実際のメール送信をシミュレートできる
- メールの見た目やレイアウトを確認できる
- スパムスコアのチェックも可能
- 無料プランで十分な機能がある

### デメリット
- 外部サービスの登録が必要
- インターネット接続が必要

---

## 方法3: 実際のメールサーバーを使用する

本番環境と同じ設定でテストする場合に使用します。

### Gmailを使用する場合

**この設定を行うと、テストコマンドで送信したメールは実際のGmailアドレスに届きます。**

#### 1. Gmailのアプリパスワードを取得

1. Googleアカウントの設定 → セキュリティ
2. 「2段階認証プロセス」を有効化（必須）
3. 「アプリパスワード」を生成
4. 生成されたパスワードをコピー（16文字のパスワードが生成されます）

**注意**: 通常のGmailパスワードではなく、**アプリパスワード**を使用する必要があります。

#### 2. `.env`ファイルの設定

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_app_password  # アプリパスワードを使用（16文字のパスワード）
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_gmail@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### 3. `.env`ファイルの設定（詳細）

**重要**: アプリパスワードはスペースを含む場合がありますが、`.env`ファイルには**スペースを削除**して記載してください。

例：アプリパスワードが `yltk hkqy diqm njbq` の場合
```env
MAIL_PASSWORD=yltkhkqydiqmnjbq  # スペースを削除
```

**設定例（実際の値）**:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com  # あなたのGmailアドレス
MAIL_PASSWORD=yltkhkqydiqmnjbq  # アプリパスワード（スペースなし）
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_gmail@gmail.com  # あなたのGmailアドレス
MAIL_FROM_NAME="${APP_NAME}"
```

**既存の設定を置き換える場合**:

もし`.env`ファイルに以下のような設定が残っている場合：
```env
MAIL_HOST=mailpit  # ← これを smtp.gmail.com に変更
MAIL_PORT=1025     # ← これを 587 に変更
MAIL_USERNAME=null # ← これをあなたのGmailアドレスに変更
MAIL_PASSWORD=null # ← これをアプリパスワードに変更
MAIL_ENCRYPTION=null # ← これを tls に変更
```

これらをすべて上記のGmail設定に変更してください。

#### 4. 設定の反映とテスト

```bash
# 設定をクリアして再読み込み（必須）
php artisan config:clear

# テストコマンドを実行（実際のGmailアドレスを指定）
php artisan test:email matching your@gmail.com
```

**成功時の出力例**:

```
現在のメール設定: smtp
SMTPドライバーが使用されています。ホスト: smtp.gmail.com
メールを送信しています...
✓ メール送信に成功しました!
送信先: your@gmail.com
件名: マッチングが成立しました
```

この設定でコマンドを実行すると、指定したGmailアドレスに実際にメールが届きます。メールボックス（受信トレイまたは迷惑メールフォルダ）を確認してください。

#### 5. よくあるエラーと解決方法

**エラー: `Connection could not be established with host "mailpit:1025"`**

- **原因**: 以前の設定（mailpitなど）が残っている
- **解決方法**: `.env`ファイルの`MAIL_HOST`を`smtp.gmail.com`に変更し、`php artisan config:clear`を実行

**エラー: `Authentication failed`**

- **原因**: アプリパスワードが間違っている、または通常のパスワードを使用している
- **解決方法**: Gmailのアプリパスワードを正しく生成し、`.env`ファイルに設定してください

**エラー: `2-Step Verification is not enabled`**

- **原因**: Gmailの2段階認証が有効になっていない
- **解決方法**: Googleアカウントの設定で2段階認証を有効化してください

#### 6. セキュリティに関する注意事項

⚠️ **重要**: アプリパスワードは機密情報です。以下の点に注意してください：

- `.env`ファイルは`.gitignore`に含まれていることを確認（Gitにコミットしない）
- アプリパスワードを公開の場で共有しない
- 設定が完了したら、必要に応じてアプリパスワードを再生成することを推奨
- 複数の場所で使用している場合は、各サービスごとに異なるアプリパスワードを生成する

### その他のメールサーバー

他のメールプロバイダー（Outlook、SendGrid、Mailgunなど）を使用する場合も、同様にSMTP設定を行います。

---

## メール送信のテスト方法

### 方法A: Artisanコマンドでテスト（推奨）

専用のテストコマンドを作成しました。このコマンドは、指定されたメールアドレスにテストメールを送信します。

**重要**: メールが実際のGmailに届くかどうかは、`.env`ファイルの設定によります：
- `MAIL_MAILER=log` の場合：**実際のメールには届きません**。ログファイルにのみ記録されます。
- `MAIL_MAILER=smtp` でGmailのSMTP設定をしている場合：**実際のGmailに届きます**。
- `MAIL_MAILER=smtp` でMailtrapを設定している場合：**実際のメールには届きません**。MailtrapのWebインターフェースでのみ確認できます。

#### 使用方法

```bash
# マッチング通知のテスト
php artisan test:email matching test@example.com

# 依頼通知のテスト
php artisan test:email request test@example.com

# 報告書提出通知のテスト
php artisan test:email report_submitted test@example.com

# 報告書承認通知のテスト
php artisan test:email report_approved test@example.com

# リマインダー通知のテスト
php artisan test:email reminder test@example.com
```

#### コマンドの動作

1. 指定されたメールアドレスにユーザーが存在しない場合、テストユーザーを作成します
2. メールテンプレートの存在を確認します
3. **現在のメール設定（log/smtp）を表示し、メールの届き先を案内します**
4. テストメールを送信します
5. 送信結果と確認方法を表示します

#### 実行例

**Gmail設定を使用している場合**:

```bash
$ php artisan test:email matching your@gmail.com

現在のメール設定: smtp
SMTPドライバーが使用されています。ホスト: smtp.gmail.com
メールを送信しています...
✓ メール送信に成功しました!
送信先: your@gmail.com
件名: マッチングが成立しました
```

→ 実際のGmailメールボックスでメールを確認できます。

**logドライバーを使用している場合**:

```bash
$ php artisan test:email matching your@email.com

現在のメール設定: log
ログドライバーが使用されています。メールは storage/logs/laravel.log に記録されます。
メールを送信しています...
✓ メール送信に成功しました!
ログファイルを確認してください: storage/logs/laravel.log
最新のログを確認するには: tail -f storage/logs/laravel.log
```

→ ログファイルを確認してください。

**Mailtrapを使用している場合**:

```bash
$ php artisan test:email matching your@email.com

現在のメール設定: smtp
SMTPドライバーが使用されています。ホスト: smtp.mailtrap.io
Mailtrapを使用しています。https://mailtrap.io/ でメールを確認してください。
メールを送信しています...
✓ メール送信に成功しました!
送信先: your@email.com
件名: マッチングが成立しました
```

→ MailtrapのWebインターフェースでメールを確認できます。

このコマンドを使用すると、実際のマッチングを成立させることなく、メール通知の動作を確認できます。

### 方法B: 実際のマッチングフローでテスト

1. **ユーザーアカウントで依頼を作成**
   - ダッシュボードにログイン
   - 新しい依頼を作成

2. **ガイドアカウントで依頼を承諾**
   - ガイドとしてログイン
   - 依頼一覧から該当の依頼を承諾

3. **自動マッチングが有効な場合**
   - 依頼を承諾すると即座にマッチングが成立
   - ユーザーとガイドの両方にメールが送信される

4. **自動マッチングが無効な場合**
   - 管理者が手動でマッチングを承認
   - 承認時にメールが送信される

### 方法C: Tinkerで直接テスト

```bash
php artisan tinker
```

```php
// メールサービスを取得
$emailService = app(\App\Services\EmailNotificationService::class);

// テスト用のユーザーを取得
$user = \App\Models\User::first();

// マッチング通知を送信
$emailService->sendMatchingNotification($user, [
    'id' => 1,
    'request_type' => '外出支援',
    'request_date' => '2024-01-15',
    'request_time' => '10:00',
]);

// 結果を確認
// logドライバーの場合: storage/logs/laravel.log を確認
// smtpドライバーの場合: Mailtrapや実際のメールボックスを確認
```

---

## トラブルシューティング

### メールが送信されない

#### 1. ログを確認

```bash
tail -f storage/logs/laravel.log
```

エラーメッセージがあるか確認してください。

#### 2. メールテンプレートが存在するか確認

```bash
php artisan tinker
```

```php
\App\Models\EmailTemplate::where('template_key', 'matching_notification')->first();
```

`null`が返る場合は、メールテンプレートが設定されていません。

#### 3. メール通知設定が有効か確認

```php
\App\Models\EmailNotificationSetting::where('notification_type', 'matching')->first();
```

`is_enabled`が`false`の場合、メールは送信されません。

#### 4. 設定が正しく読み込まれているか確認

```bash
php artisan config:clear
php artisan config:cache
```

### SMTP接続エラー

#### 1. 接続情報を確認

```bash
php artisan tinker
```

```php
config('mail.mailers.smtp');
```

設定が正しく読み込まれているか確認してください。

**Gmail設定の確認例**:

```php
// .envファイルの設定を確認
config('mail.mailers.smtp.host');  // "smtp.gmail.com" であることを確認
config('mail.mailers.smtp.port');  // 587 であることを確認
config('mail.mailers.smtp.encryption');  // "tls" であることを確認
config('mail.mailers.smtp.username');  // あなたのGmailアドレスであることを確認
// パスワードは表示されませんが、設定されているか確認
```

#### 2. ポート番号を確認

- **Gmail**: 587 (TLS) または 465 (SSL)
- **その他のSMTP**: 
  - TLS: 587
  - SSL: 465
  - 非暗号化: 25（推奨されない）

**Gmailの場合、587番ポートとTLS暗号化を使用することを推奨します**。

#### 3. 設定の反映確認

`.env`ファイルを変更した後は、必ず設定キャッシュをクリアしてください：

```bash
php artisan config:clear
```

設定が反映されない場合は、以下も試してください：

```bash
php artisan config:cache  # 本番環境の場合
```

#### 4. ファイアウォールの確認

ローカル開発環境でファイアウォールがブロックしていないか確認してください。

GmailのSMTPサーバー（smtp.gmail.com:587）への接続が許可されているか確認してください。

#### 5. Gmail特有のエラー

**エラー: `Connection timeout` または `Connection refused`**

- ポート587への接続がブロックされている可能性があります
- 会社のネットワークなど、ファイアウォールでSMTPポートがブロックされている場合は、IT管理者に確認してください

**エラー: `535-5.7.8 Username and Password not accepted`**

- アプリパスワードが正しく設定されているか確認
- 通常のGmailパスワードではなく、アプリパスワードを使用しているか確認
- 2段階認証が有効になっているか確認

### メールがスパムフォルダに入る

- `MAIL_FROM_ADDRESS`が適切なドメインか確認
- SPFレコードやDKIM設定を確認（本番環境の場合）
- Mailtrapのスパムスコアを確認

---

## 次のステップ

メール通知が正しく動作することを確認したら：

1. メールテンプレートをカスタマイズ（`resources/views/admin/dashboard.blade.php`のメールテンプレート管理から）
2. メール通知設定を調整（特定の通知を無効化など）
3. 本番環境にデプロイする前に、実際のメールサーバー設定を確認

---

## 参考リンク

- [Laravel Mail ドキュメント](https://laravel.com/docs/mail)
- [Mailtrap ドキュメント](https://mailtrap.io/docs/)
- [Gmail SMTP設定](https://support.google.com/mail/answer/7126229)

