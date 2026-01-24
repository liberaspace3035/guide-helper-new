# GmailのSMTP設定手順

## .envファイルの設定

`.env`ファイルの以下の部分を以下のように変更してください：

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com  # ← あなたのGmailアドレスに変更してください
MAIL_PASSWORD=yltkhkqydiqmnjbq  # ← アプリパスワード（スペースを削除）
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_gmail@gmail.com  # ← あなたのGmailアドレスに変更してください
MAIL_FROM_NAME="${APP_NAME}"
```

## 注意事項

1. **Gmailアドレス**: `your_gmail@gmail.com` の部分を、実際のGmailアドレスに変更してください
2. **アプリパスワード**: スペースを削除して `yltkhkqydiqmnjbq` として使用してください
3. **セキュリティ**: アプリパスワードが公開されている場合は、Gmailの設定から再生成することを推奨します

## 設定後の確認

```bash
# 設定をクリアして再読み込み
php artisan config:clear

# テストコマンドで確認（あなたのGmailアドレスを指定）
php artisan test:email matching your@gmail.com
```

メールが実際のGmailに届くことを確認してください。



