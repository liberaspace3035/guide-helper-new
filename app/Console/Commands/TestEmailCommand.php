<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailNotificationService;
use App\Models\User;
use App\Models\EmailTemplate;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {type : メールタイプ (matching|request|report_submitted|report_approved|reminder)} {email : 送信先メールアドレス}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'メール通知をテスト送信します';

    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->argument('email');

        // メールタイプの検証
        $validTypes = ['matching', 'request', 'report_submitted', 'report_approved', 'reminder'];
        if (!in_array($type, $validTypes)) {
            $this->error("無効なメールタイプです。使用可能なタイプ: " . implode(', ', $validTypes));
            return 1;
        }

        // メールアドレスの検証
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("無効なメールアドレスです: {$email}");
            return 1;
        }

        // ユーザーを取得または作成
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'email' => $email,
                'name' => 'テストユーザー',
                'password_hash' => bcrypt('password'),
                'role' => 'user',
                'is_allowed' => true,
            ]);
            $this->info("テストユーザーを作成しました: {$email}");
        }

        // メールテンプレートの確認
        $templateKey = $this->getTemplateKey($type);
        $template = EmailTemplate::where('template_key', $templateKey)->first();
        
        if (!$template) {
            $this->error("メールテンプレートが見つかりません: {$templateKey}");
            $this->warn("以下のコマンドでメールテンプレートを初期化してください:");
            $this->warn("mysql -u root -p your_database < database/migrations/seed_email_templates.sql");
            return 1;
        }

        if (!$template->is_active) {
            $this->warn("メールテンプレートが無効です: {$templateKey}");
        }

        // メール送信の設定確認
        $mailer = config('mail.default');
        $this->info("現在のメール設定: {$mailer}");
        
        if ($mailer === 'log') {
            $this->info("ログドライバーが使用されています。メールは storage/logs/laravel.log に記録されます。");
        } elseif ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $this->info("SMTPドライバーが使用されています。ホスト: {$host}");
            if (strpos($host, 'mailtrap') !== false) {
                $this->info("Mailtrapを使用しています。https://mailtrap.io/ でメールを確認してください。");
            }
        }

        // テストデータの準備
        $testData = $this->getTestData($type);

        // メール送信
        $this->info("メールを送信しています...");
        
        try {
            $result = match($type) {
                'matching' => $this->emailService->sendMatchingNotification($user, $testData),
                'request' => $this->emailService->sendRequestNotification($user, $testData),
                'report_submitted' => $this->emailService->sendReportSubmittedNotification($user, $testData),
                'report_approved' => $this->emailService->sendReportApprovedNotification($user, $testData),
                'reminder' => $this->emailService->sendReminderNotification($user, $testData),
            };

            if ($result) {
                $this->info("✓ メール送信に成功しました!");
                
                if ($mailer === 'log') {
                    $this->info("ログファイルを確認してください: storage/logs/laravel.log");
                    $this->info("最新のログを確認するには: tail -f storage/logs/laravel.log");
                } else {
                    $this->info("送信先: {$email}");
                    $this->info("件名: {$template->subject}");
                }
                
                return 0;
            } else {
                $this->error("メール送信に失敗しました。ログを確認してください。");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("エラーが発生しました: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * メールタイプからテンプレートキーを取得
     */
    protected function getTemplateKey(string $type): string
    {
        return match($type) {
            'matching' => 'matching_notification',
            'request' => 'request_notification',
            'report_submitted' => 'report_submitted',
            'report_approved' => 'report_approved',
            'reminder' => 'reminder_pending_request',
        };
    }

    /**
     * テストデータを取得
     */
    protected function getTestData(string $type): array
    {
        return match($type) {
            'matching' => [
                'id' => 999,
                'request_type' => '外出支援',
                'request_date' => now()->format('Y-m-d'),
                'request_time' => '10:00',
            ],
            'request' => [
                'id' => 999,
                'request_type' => '外出支援',
                'request_date' => now()->format('Y-m-d'),
                'request_time' => '10:00',
                'masked_address' => '東京都千代田区',
            ],
            'report_submitted' => [
                'id' => 999,
                'guide_name' => 'テストガイド',
                'actual_date' => now()->format('Y-m-d'),
            ],
            'report_approved' => [
                'id' => 999,
                'actual_date' => now()->format('Y-m-d'),
            ],
            'reminder' => [
                'id' => 999,
            ],
        };
    }
}
