<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\EmailNotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * 通知が有効かチェック
     */
    public function isNotificationEnabled(string $notificationType): bool
    {
        $setting = EmailNotificationSetting::where('notification_type', $notificationType)->first();
        return $setting ? $setting->is_enabled : true; // デフォルトは有効
    }

    /**
     * テンプレートを取得
     */
    public function getTemplate(string $templateKey): ?EmailTemplate
    {
        return EmailTemplate::where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * テンプレートの変数を置換
     */
    protected function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * メールを送信
     */
    public function sendNotification(string $templateKey, User $user, array $variables = []): bool
    {
        // 通知が有効かチェック
        if (!$this->isNotificationEnabled($this->getNotificationType($templateKey))) {
            return false;
        }

        $template = $this->getTemplate($templateKey);
        if (!$template) {
            \Log::warning("メールテンプレートが見つかりません: {$templateKey}");
            return false;
        }

        $subject = $this->replaceVariables($template->subject, $variables);
        $body = $this->replaceVariables($template->body, $variables);
        // テンプレートに literal \n が入っている場合に改行に変換（メールで \n がそのまま表示されるのを防ぐ）
        $body = str_replace(["\\n", "\\r"], ["\n", "\r"], $body);

        try {
            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                    ->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            \Log::error('メール送信エラー: ' . $e->getMessage(), [
                'template_key' => $templateKey,
                'user_id' => $user->id,
            ]);
            return false;
        }
    }

    /**
     * テンプレートキーから通知種別を取得
     */
    protected function getNotificationType(string $templateKey): string
    {
        $mapping = [
            'request_notification' => 'request',
            'matching_notification' => 'matching',
            'report_submitted' => 'report',
            'report_approved' => 'report',
            'reminder_pending_request' => 'reminder',
            'password_reset' => 'password_reset',
            'user_registration_thanks' => 'registration',
            'guide_registration_thanks' => 'registration',
            'reminder_same_day' => 'reminder',
            'reminder_day_before' => 'reminder',
            'reminder_report_missing' => 'reminder',
            'announcement_reminder_unread' => 'announcement_reminder',
            'report_revision_requested' => 'report',
            'report_admin_revision_requested' => 'report',
        ];
        return $mapping[$templateKey] ?? 'request';
    }

    /**
     * お知らせ未読リマインドを送信（未読お知らせ一覧を記載）
     *
     * @param  array<int, array{title: string, id?: int}>  $unreadList  [ ['title' => '...', 'id' => ...], ... ]
     */
    public function sendAnnouncementReminderNotification(User $user, array $unreadList): bool
    {
        $lines = [];
        foreach ($unreadList as $item) {
            $title = $item['title'] ?? '';
            $lines[] = '・' . $title;
        }
        $unreadListText = implode("\n", $lines);

        return $this->sendNotification('announcement_reminder_unread', $user, [
            'user_name' => $user->name ?? '',
            'unread_list' => $unreadListText,
        ]);
    }

    /**
     * 依頼通知を送信
     */
    public function sendRequestNotification(User $guide, array $requestData): bool
    {
        return $this->sendNotification('request_notification', $guide, [
            'request_id' => $requestData['id'] ?? '',
            'request_type' => $requestData['request_type'] ?? '',
            'request_date' => $requestData['request_date'] ?? '',
            'request_time' => $requestData['request_time'] ?? '',
            'masked_address' => $requestData['masked_address'] ?? '',
        ]);
    }

    /**
     * マッチング通知を送信
     */
    public function sendMatchingNotification(User $user, array $matchingData): bool
    {
        return $this->sendNotification('matching_notification', $user, [
            'matching_id' => $matchingData['id'] ?? '',
            'request_type' => $matchingData['request_type'] ?? '',
            'request_date' => $matchingData['request_date'] ?? '',
            'request_time' => $matchingData['request_time'] ?? '',
        ]);
    }

    /**
     * 報告書提出通知を送信
     */
    public function sendReportSubmittedNotification(User $user, array $reportData): bool
    {
        return $this->sendNotification('report_submitted', $user, [
            'report_id' => $reportData['id'] ?? '',
            'guide_name' => $reportData['guide_name'] ?? '',
            'actual_date' => $reportData['actual_date'] ?? '',
        ]);
    }

    /**
     * 報告書承認通知を送信
     */
    public function sendReportApprovedNotification(User $guide, array $reportData): bool
    {
        return $this->sendNotification('report_approved', $guide, [
            'report_id' => $reportData['id'] ?? '',
            'actual_date' => $reportData['actual_date'] ?? '',
        ]);
    }

    /**
     * リマインド通知を送信
     */
    public function sendReminderNotification(User $user, array $reminderData): bool
    {
        return $this->sendNotification('reminder_pending_request', $user, [
            'request_id' => $reminderData['id'] ?? '',
        ]);
    }

    /**
     * 報告書未提出リマインドを送信（ガイド向け）
     */
    public function sendReportMissingReminderNotification(User $guide, array $data): bool
    {
        return $this->sendNotification('reminder_report_missing', $guide, [
            'user_name' => $guide->name ?? '',
            'matching_id' => $data['matching_id'] ?? '',
            'request_date' => $data['request_date'] ?? '',
        ]);
    }

    /**
     * 当日リマインドを送信（ユーザー・ガイド向け）
     */
    public function sendReminderSameDayNotification(User $user, array $data): bool
    {
        return $this->sendNotification('reminder_same_day', $user, [
            'user_name' => $user->name ?? '',
            'request_id' => $data['request_id'] ?? '',
            'request_type' => $data['request_type'] ?? '',
            'request_date' => $data['request_date'] ?? '',
            'request_time' => $data['request_time'] ?? '',
            'masked_address' => $data['masked_address'] ?? '',
        ]);
    }

    /**
     * 前日リマインドを送信（ユーザー・ガイド向け）
     */
    public function sendReminderDayBeforeNotification(User $user, array $data): bool
    {
        return $this->sendNotification('reminder_day_before', $user, [
            'user_name' => $user->name ?? '',
            'request_id' => $data['request_id'] ?? '',
            'request_type' => $data['request_type'] ?? '',
            'request_date' => $data['request_date'] ?? '',
            'request_time' => $data['request_time'] ?? '',
            'masked_address' => $data['masked_address'] ?? '',
        ]);
    }

    /**
     * 報告書修正依頼通知を送信（ユーザー→ガイド）
     */
    public function sendReportRevisionRequestedNotification(User $guide, array $data): bool
    {
        return $this->sendNotification('report_revision_requested', $guide, [
            'user_name' => $guide->name ?? '',
            'report_id' => $data['report_id'] ?? '',
            'actual_date' => $data['actual_date'] ?? '',
            'revision_notes' => $data['revision_notes'] ?? '',
        ]);
    }

    /**
     * 報告書管理者差し戻し通知を送信（管理者→ガイド）
     */
    public function sendReportAdminRevisionRequestedNotification(User $guide, array $data): bool
    {
        return $this->sendNotification('report_admin_revision_requested', $guide, [
            'user_name' => $guide->name ?? '',
            'report_id' => $data['report_id'] ?? '',
            'actual_date' => $data['actual_date'] ?? '',
            'revision_notes' => $data['revision_notes'] ?? '',
        ]);
    }

    /**
     * 登録者本人へ登録完了案内メールを送信（ユーザー/ガイド別テンプレート）
     */
    public function sendRegistrationThanksNotification(User $user, bool $isGuide): bool
    {
        $templateKey = $isGuide ? 'guide_registration_thanks' : 'user_registration_thanks';
        return $this->sendNotification($templateKey, $user, [
            'user_name' => $user->name ?? '',
        ]);
    }

    /**
     * 管理者向け：新規ユーザー/ガイド登録の通知メールを送信
     */
    public function sendAdminNewRegistrationNotification(User $admin, string $registrantName, bool $isGuide): bool
    {
        $subject = $isGuide ? '【管理者通知】新規ガイドが登録されました' : '【管理者通知】新規ユーザーが登録されました';
        $typeLabel = $isGuide ? 'ガイド' : 'ユーザー';
        $body = "管理者 様\n\n";
        $body .= "{$registrantName} さんが新規{$typeLabel}として登録しました。\n\n";
        $body .= ($isGuide ? 'ガイド管理' : 'ユーザー管理') . "の承認待ち一覧で確認し、審査を行ってください。\n\n";
        $body .= "ガイドマッチングアプリ 管理画面";

        try {
            Mail::raw($body, function ($message) use ($admin, $subject) {
                $message->to($admin->email, $admin->name)
                    ->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            \Log::error('管理者向け新規登録通知メール送信エラー: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
            ]);
            return false;
        }
    }

    /**
     * パスワードリセット通知を送信
     */
    public function sendPasswordResetNotification(User $user, string $resetUrl): bool
    {
        // テンプレートを使用する場合
        $template = $this->getTemplate('password_reset');
        if ($template) {
            return $this->sendNotification('password_reset', $user, [
                'reset_url' => $resetUrl,
                'user_name' => $user->name ?? '',
            ]);
        }

        // テンプレートがない場合は直接メール送信
        try {
            $subject = 'パスワードリセットのご案内';
            $body = "{$user->name} 様\n\n";
            $body .= "パスワードリセットのリクエストを受け付けました。\n\n";
            $body .= "以下のリンクをクリックして、新しいパスワードを設定してください。\n";
            $body .= "このリンクは60分間有効です。\n\n";
            $body .= "{$resetUrl}\n\n";
            $body .= "このリクエストをしていない場合は、このメールを無視してください。\n\n";
            $body .= "ガイドマッチングアプリ";

            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                    ->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            \Log::error('パスワードリセットメール送信エラー: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            return false;
        }
    }
}




