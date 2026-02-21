<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailNotificationService;
use App\Models\EmailNotificationSetting;
use App\Models\Request;
use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Matching;
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

        // 報告書未提出リマインド（ガイド向け）
        $matchingsWithoutReport = Matching::with(['guide', 'request'])
            ->whereNull('report_completed_at')
            ->where('status', '!=', 'cancelled')
            ->where('matched_at', '<=', $targetDate)
            ->where(function ($q) {
                $q->whereDoesntHave('report')
                    ->orWhereHas('report', function ($r) {
                        $r->whereIn('status', ['draft', 'revision_requested']);
                    });
            })
            ->get();

        foreach ($matchingsWithoutReport as $matching) {
            if ($matching->guide) {
                $requestDate = $matching->request && $matching->request->request_date
                    ? $matching->request->request_date->format('Y-m-d')
                    : '';
                if ($this->emailService->sendReportMissingReminderNotification($matching->guide, [
                    'matching_id' => $matching->id,
                    'request_date' => $requestDate,
                ])) {
                    $sentCount++;
                }
            }
        }

        // 当日リマインド（依頼日が今日のマッチング・ユーザーとガイドに送信）
        $matchingsSameDay = Matching::with(['user', 'guide', 'request'])
            ->whereNull('report_completed_at')
            ->where('status', '!=', 'cancelled')
            ->whereHas('request', function ($q) {
                $q->whereDate('request_date', Carbon::today());
            })
            ->get();

        foreach ($matchingsSameDay as $matching) {
            $data = $this->buildReminderRequestData($matching->request);
            if ($matching->user && $this->emailService->sendReminderSameDayNotification($matching->user, $data)) {
                $sentCount++;
            }
            if ($matching->guide && $this->emailService->sendReminderSameDayNotification($matching->guide, $data)) {
                $sentCount++;
            }
        }

        // 前日リマインド（依頼日が明日のマッチング・ユーザーとガイドに送信）
        $matchingsDayBefore = Matching::with(['user', 'guide', 'request'])
            ->whereNull('report_completed_at')
            ->where('status', '!=', 'cancelled')
            ->whereHas('request', function ($q) {
                $q->whereDate('request_date', Carbon::tomorrow());
            })
            ->get();

        foreach ($matchingsDayBefore as $matching) {
            $data = $this->buildReminderRequestData($matching->request);
            if ($matching->user && $this->emailService->sendReminderDayBeforeNotification($matching->user, $data)) {
                $sentCount++;
            }
            if ($matching->guide && $this->emailService->sendReminderDayBeforeNotification($matching->guide, $data)) {
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

    /**
     * 当日・前日リマインド用の依頼データを組み立て
     */
    protected function buildReminderRequestData(?\App\Models\Request $request): array
    {
        if (!$request) {
            return [
                'request_id' => '',
                'request_type' => '',
                'request_date' => '',
                'request_time' => '',
                'masked_address' => '',
            ];
        }
        $requestDate = $request->request_date;
        if ($requestDate instanceof \Carbon\Carbon) {
            $requestDate = $requestDate->format('Y-m-d');
        } elseif ($requestDate instanceof \DateTimeInterface) {
            $requestDate = $requestDate->format('Y-m-d');
        } else {
            $requestDate = (string) $requestDate;
        }
        $requestTime = $request->request_time ?? $request->start_time ?? '';
        if ($requestTime instanceof \DateTimeInterface) {
            $requestTime = $requestTime->format('H:i');
        }
        return [
            'request_id' => (string) $request->id,
            'request_type' => $request->request_type === 'outing' ? '外出' : '自宅',
            'request_date' => $requestDate,
            'request_time' => (string) $requestTime,
            'masked_address' => $request->masked_address ?? '',
        ];
    }
}




