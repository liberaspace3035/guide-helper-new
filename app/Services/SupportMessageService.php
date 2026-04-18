<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Notification;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportMessageService
{
    public function getAutoReplySubject(): string
    {
        return AdminSetting::where('setting_key', 'support_auto_reply_subject')->value('setting_value')
            ?: '【One Step】お問い合わせを受け付けました';
    }

    public function getAutoReplyBodyTemplate(): string
    {
        return AdminSetting::where('setting_key', 'support_auto_reply_body')->value('setting_value')
            ?: "{{name}} 様\n\nお問い合わせありがとうございます。内容を確認のうえ、担当者よりご連絡いたします。\n\n一般社団法人With Blind\nOne Step 運営";
    }

    public function saveAutoReplyTemplate(string $subject, string $body): void
    {
        $now = now();
        AdminSetting::updateOrCreate(
            ['setting_key' => 'support_auto_reply_subject'],
            ['setting_value' => $subject, 'updated_at' => $now]
        );
        AdminSetting::updateOrCreate(
            ['setting_key' => 'support_auto_reply_body'],
            ['setting_value' => $body, 'updated_at' => $now]
        );
    }

    public function notifyAdminInbound(User $fromUser, string $body): void
    {
        $to = config('mail.support_notify_address');
        if (empty($to)) {
            Log::warning('support_notify_address が未設定のため、運営向け通知メールをスキップしました', [
                'user_id' => $fromUser->id,
            ]);

            return;
        }

        $roleLabel = $fromUser->isGuide() ? 'ガイド' : '利用者';
        $text = "利用者・ガイドからの運営へのメッセージが届きました。\n\n"
            . "送信者: {$fromUser->name}（ID: {$fromUser->id} / {$roleLabel}）\n"
            . "メール: {$fromUser->email}\n\n"
            . "----\n{$body}\n----\n\n"
            . '管理画面で返信: ' . url('/admin/support/' . $fromUser->id);

        try {
            Mail::raw($text, function ($message) use ($to, $fromUser) {
                $message->to($to)->subject('[One Step] 運営へのメッセージ: ' . $fromUser->name);
            });
        } catch (\Throwable $e) {
            Log::error('運営向けサポート通知メール送信失敗: ' . $e->getMessage());
        }
    }

    public function sendAutoReplyToUser(User $user): void
    {
        $subject = $this->getAutoReplySubject();
        $body = str_replace('{{name}}', $user->name, $this->getAutoReplyBodyTemplate());

        try {
            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('サポート自動返信メール送信失敗: ' . $e->getMessage(), ['user_id' => $user->id]);
        }
    }

    public function postFromParticipant(User $user, string $body): SupportMessage
    {
        if ($user->isAdmin()) {
            throw new \InvalidArgumentException('管理者はこの画面から送信できません');
        }

        $message = SupportMessage::create([
            'user_id' => $user->id,
            'is_from_admin' => false,
            'body' => $body,
        ]);

        $this->notifyAdminInbound($user, $body);
        $this->sendAutoReplyToUser($user);

        return $message;
    }

    public function postFromAdmin(User $admin, int $participantUserId, string $body): SupportMessage
    {
        if (!$admin->isAdmin()) {
            throw new \InvalidArgumentException('管理者のみが送信できます');
        }

        $participant = User::findOrFail($participantUserId);
        if ($participant->isAdmin()) {
            throw new \InvalidArgumentException('対象ユーザーが不正です');
        }

        $message = SupportMessage::create([
            'user_id' => $participant->id,
            'is_from_admin' => true,
            'body' => $body,
        ]);

        Notification::create([
            'user_id' => $participant->id,
            'type' => 'support',
            'title' => '運営からメッセージが届きました',
            'message' => mb_strimwidth($body, 0, 500, '…'),
            'related_id' => $message->id,
            'created_at' => now(),
        ]);

        return $message;
    }
}
