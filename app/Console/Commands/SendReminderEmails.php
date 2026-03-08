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
    protected $signature = 'emails:send-reminders {--force : 送信時刻を無視して今すぐ送信（動作確認用）}';
    protected $description = 'リマインドメールを送信';

    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    public function handle()
    {
        $now = Carbon::now()->format('H:i');
        $reminderSetting = EmailNotificationSetting::where('notification_type', 'reminder')->first();
        $announcementSetting = EmailNotificationSetting::where('notification_type', 'announcement_reminder')->first();

        // 送信時刻: 「リマインド」または「お知らせ未読リマインド」のどちらかで一致すれば送信
        $reminderTime = $reminderSetting && $reminderSetting->scheduled_time
            ? $this->normalizeTime(trim($reminderSetting->scheduled_time))
            : '09:00';
        $announcementTime = $announcementSetting && $announcementSetting->scheduled_time
            ? $this->normalizeTime(trim($announcementSetting->scheduled_time))
            : null;

        $timeMatches = ($now === $reminderTime) || ($announcementTime !== null && $now === $announcementTime);

        // --force でない場合は、いずれかの設定時刻と一致したときだけ送信
        if (! $this->option('force')) {
            if (! $timeMatches) {
                \Log::info('SendReminderEmails: 送信時刻外のためスキップ（送信なし）', [
                    'now' => $now,
                    'reminder_scheduled' => $reminderTime,
                    'announcement_scheduled' => $announcementTime,
                    'timezone' => config('app.timezone'),
                ]);
                return 0;
            }
        } else {
            $this->info("送信時刻を無視して実行します（now={$now}, reminder={$reminderTime}, announcement={$announcementTime}）");
        }

        \Log::info('SendReminderEmails: 送信処理を実行', [
            'now' => $now,
            'reminder_scheduled' => $reminderTime,
            'announcement_scheduled' => $announcementTime,
        ]);

        // リマインド通知が有効かチェック（依頼リマインド・報告書リマインドの送信可否）
        if (!$reminderSetting || !$reminderSetting->is_enabled) {
            $this->info('リマインド通知は無効です');
            // 無効でも当日/前日リマインド・お知らせリマインドは送信するため続行
        }

        $reminderDays = ($reminderSetting && $reminderSetting->is_enabled) ? ($reminderSetting->reminder_days ?? 3) : 0;
        $targetDate = Carbon::now()->subDays($reminderDays);

        // 承認待ちの依頼を取得（リマインド有効時のみ）
        $pendingRequests = $reminderDays > 0 ? Request::where('status', 'pending')
            ->where('created_at', '<=', $targetDate)
            ->with('user')
            ->get() : collect();

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
        // 条件: 
        // 1. 依頼日が今日以前（依頼日当日または過去）
        // 2. 報告書が未作成、下書き、または修正依頼状態
        // 3. ユーザー承認されていない（ユーザー承認後は送信停止）
        // 4. 時刻制限: 現在時刻が19時以降（このコマンド自体が19時以降に実行される想定）
        $currentHour = (int) Carbon::now()->format('H');
        $shouldSendReportReminder = $currentHour >= 19 || $this->option('force');
        
        if ($shouldSendReportReminder) {
            $matchingsWithoutReport = Matching::with(['guide', 'request', 'report'])
                ->whereNull('report_completed_at')
                ->where('status', '!=', 'cancelled')
                ->whereHas('request', function ($q) {
                    // 依頼日が今日以前（依頼日当日または過去）
                    $q->whereDate('request_date', '<=', Carbon::today());
                })
                ->where(function ($q) {
                    // 報告書が未作成、下書き、修正依頼状態、または提出済み（ユーザー未承認）
                    $q->whereDoesntHave('report')
                        ->orWhereHas('report', function ($r) {
                            $r->whereIn('status', ['draft', 'revision_requested', 'submitted']);
                        });
                })
                ->get();

            foreach ($matchingsWithoutReport as $matching) {
                if ($matching->guide) {
                    $requestDate = $matching->request && $matching->request->request_date
                        ? $matching->request->request_date->format('Y-m-d')
                        : '';
                    
                    // 報告書のステータスを確認
                    $reportStatus = $matching->report ? $matching->report->status : 'none';
                    
                    // ユーザー承認済み（user_approved）以降はガイドへの催促は不要
                    if (in_array($reportStatus, ['user_approved', 'admin_approved', 'approved'], true)) {
                        continue;
                    }
                    
                    if ($this->emailService->sendReportMissingReminderNotification($matching->guide, [
                        'matching_id' => $matching->id,
                        'request_date' => $requestDate,
                        'report_status' => $reportStatus,
                    ])) {
                        $sentCount++;
                    }
                }
            }
        } else {
            \Log::info('SendReminderEmails: 報告書リマインドは19時以降に送信', ['current_hour' => $currentHour]);
        }

        // 報告書承認待ちリマインド（ユーザー向け）
        // 報告書が提出済み（submitted）だがユーザー未承認のものについて、ユーザーに毎日催促
        // 19時以降に送信
        if ($shouldSendReportReminder) {
            $reportsAwaitingUserApproval = \App\Models\Report::with(['user', 'guide', 'request'])
                ->where('status', 'submitted')
                ->get();

            foreach ($reportsAwaitingUserApproval as $report) {
                if ($report->user) {
                    $requestDate = $report->request && $report->request->request_date
                        ? ($report->request->request_date instanceof \Carbon\Carbon 
                            ? $report->request->request_date->format('Y-m-d') 
                            : (string)$report->request->request_date)
                        : '';
                    $guideName = $report->guide ? $report->guide->name : '';
                    
                    if ($this->emailService->sendReportApprovalReminderNotification($report->user, [
                        'report_id' => $report->id,
                        'request_date' => $requestDate,
                        'guide_name' => $guideName,
                    ])) {
                        $sentCount++;
                    }
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

        // お知らせ未読リマインド（ユーザー・ガイド向け・reminder_enabled が true のお知らせのみ）
        $announcementReminderSetting = EmailNotificationSetting::where('notification_type', 'announcement_reminder')->first();
        if ($announcementReminderSetting && $announcementReminderSetting->is_enabled) {
            $announcements = Announcement::where('reminder_enabled', true)->orderBy('created_at', 'desc')->get();
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
        \Log::info('SendReminderEmails: 完了', ['sent_count' => $sentCount]);
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

    /**
     * 送信時刻を HH:MM に正規化（"9:00" → "09:00"）。比較ずれを防ぐ。
     */
    private function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $time, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        return $time;
    }
}




