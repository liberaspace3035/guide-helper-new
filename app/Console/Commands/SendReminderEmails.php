<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailNotificationService;
use App\Models\EmailNotificationSetting;
use App\Models\Request;
use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Carbon\Carbon;

class SendReminderEmails extends Command
{
    protected $signature = 'emails:send-reminders';
    protected $description = 'リマインドメールを送信';

    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    public function handle()
    {
        // リマインド通知が有効かチェック
        $reminderSetting = EmailNotificationSetting::where('notification_type', 'reminder')->first();
        if (!$reminderSetting || !$reminderSetting->is_enabled) {
            $this->info('リマインド通知は無効です');
            return 0;
        }

        $reminderDays = $reminderSetting->reminder_days ?? 3; // デフォルト3日
        $targetDate = Carbon::now()->subDays($reminderDays);

        // 承認待ちの依頼を取得
        $pendingRequests = Request::where('status', 'pending')
            ->where('created_at', '<=', $targetDate)
            ->with('user')
            ->get();

        $sentCount = 0;
        foreach ($pendingRequests as $request) {
            if ($request->user) {
                $this->emailService->sendReminderNotification($request->user, [
                    'id' => $request->id,
                ]);
                $sentCount++;
            }
        }

        // お知らせ未読リマインド（ユーザー・ガイド向け）
        $announcementReminderSetting = EmailNotificationSetting::where('notification_type', 'announcement_reminder')->first();
        if ($announcementReminderSetting && $announcementReminderSetting->is_enabled) {
            $announcements = Announcement::orderBy('created_at', 'desc')->get();
            if ($announcements->isNotEmpty()) {
                $readIdsByUser = AnnouncementRead::get()
                    ->groupBy('user_id')
                    ->map(fn ($rows) => $rows->pluck('announcement_id')->all())
                    ->all();

                foreach (['user', 'guide'] as $role) {
                    $users = User::where('role', $role)->get();
                    foreach ($users as $user) {
                        $readIds = $readIdsByUser[$user->id] ?? [];
                        $unreadList = [];
                        foreach ($announcements as $a) {
                            $targets = $a->target_audience === 'all' ? ['user', 'guide'] : [$a->target_audience];
                            if (!in_array($role, $targets, true)) {
                                continue;
                            }
                            if (in_array((int) $a->id, $readIds, true)) {
                                continue;
                            }
                            $unreadList[] = ['title' => $a->title ?? '', 'id' => $a->id];
                        }
                        if (count($unreadList) > 0 && $this->emailService->sendAnnouncementReminderNotification($user, $unreadList)) {
                            $sentCount++;
                        }
                    }
                }
            }
        }

        $this->info("リマインドメールを {$sentCount} 件送信しました");
        return 0;
    }
}




