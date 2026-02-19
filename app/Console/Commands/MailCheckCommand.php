<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailCheckCommand extends Command
{
    protected $signature = 'mail:check {email? : テスト送信先（省略時は設定のみ表示）}';

    protected $description = 'メール設定を確認し、必要ならテスト送信する';

    public function handle(): int
    {
        $driver = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info('=== メール設定 ===');
        $this->line("ドライバ (MAIL_MAILER): <comment>{$driver}</comment>");
        $this->line("送信元アドレス (MAIL_FROM_ADDRESS): <comment>" . ($fromAddress ?: '(未設定)') . "</comment>");
        $this->line("送信元名 (MAIL_FROM_NAME): <comment>" . ($fromName ?: '(未設定)') . "</comment>");

        if ($driver === 'resend') {
            $key = config('services.resend.key');
            $this->line('Resend API キー (RESEND_API_KEY): <comment>' . ($key ? '設定済み (' . substr($key, 0, 8) . '...)' : '未設定') . '</comment>');
            if (!$key) {
                $this->error('RESEND_API_KEY が設定されていません。.env に追加してください。');
            }
            if (!$fromAddress || $fromAddress === 'hello@example.com') {
                $this->warn('MAIL_FROM_ADDRESS は onboarding@resend.dev または Resend で認証済みのドメインにしてください。');
            }
        }

        if ($driver === 'smtp') {
            $this->line('SMTP HOST: <comment>' . (config('mail.mailers.smtp.host') ?: '(未設定)') . '</comment>');
            $this->line('SMTP USERNAME: <comment>' . (config('mail.mailers.smtp.username') ? '設定済み' : '(未設定)') . '</comment>');
            $this->line('SMTP PASSWORD: <comment>' . (config('mail.mailers.smtp.password') ? '設定済み' : '(未設定)') . '</comment>');
        }

        if ($driver === 'log') {
            $this->warn('現在は log ドライバのため、実際にはメールは送信されません。storage/logs に出力されます。');
            $this->line('本番で送信するには MAIL_MAILER=resend または MAIL_MAILER=smtp にしてください。');
        }

        $testEmail = $this->argument('email');
        if (!$testEmail) {
            $this->newLine();
            $this->info('テスト送信する場合: php artisan mail:check 送信先@example.com');
            return 0;
        }

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error("無効なメールアドレスです: {$testEmail}");
            return 1;
        }

        $this->newLine();
        $this->info("テストメールを送信中: {$testEmail}");

        try {
            Mail::raw('これはメール設定のテスト送信です。届いていれば設定は正しく動作しています。', function ($message) use ($testEmail) {
                $message->to($testEmail)->subject('メール設定テスト（ガイドヘルパー）');
            });
            $this->info('送信完了。受信トレイ（迷惑メール含む）を確認してください。');
            return 0;
        } catch (\Throwable $e) {
            $this->error('送信エラー: ' . $e->getMessage());
            $this->line('例外: ' . get_class($e));
            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }
}
