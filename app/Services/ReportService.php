<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Matching;
use App\Models\Request;
use App\Models\Notification;
use App\Models\User;
use App\Services\UserMonthlyLimitService;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    protected $limitService;
    protected $emailService;

    public function __construct(UserMonthlyLimitService $limitService, EmailNotificationService $emailService)
    {
        $this->limitService = $limitService;
        $this->emailService = $emailService;
    }

    public function createOrUpdateReport(array $data, int $guideId): Report
    {
        $matching = Matching::where('id', $data['matching_id'])
            ->where('guide_id', $guideId)
            ->firstOrFail();

        // 既存の報告書を確認
        $existingReport = Report::where('matching_id', $data['matching_id'])->first();

        if ($existingReport) {
            if ($existingReport->status === 'admin_approved' || $existingReport->status === 'approved') {
                throw new \Exception('既に承認済みの報告書です');
            }

            // 既存の報告書を更新
            // 要件：日付変更不可 - actual_dateは変更しない
            $existingReport->update([
                'service_content' => $data['service_content'] ?? $existingReport->service_content,
                'report_content' => $data['report_content'] ?? $existingReport->report_content,
                // actual_dateは変更不可（要件により）
                'actual_start_time' => $data['actual_start_time'] ?? $existingReport->actual_start_time,
                'actual_end_time' => $data['actual_end_time'] ?? $existingReport->actual_end_time,
                'status' => 'draft',
            ]);

            return $existingReport;
        }

        // 依頼情報を取得（初期値として使用）
        $request = Request::find($matching->request_id);
        $initialServiceContent = $request->service_content ?? '';
        
        // 要件：日付変更不可 - 依頼日（request_date）を固定値として使用
        $fixedDate = $request->request_date ?? null;

        // 新しい報告書を作成
        return Report::create([
            'matching_id' => $data['matching_id'],
            'request_id' => $matching->request_id,
            'guide_id' => $guideId,
            'user_id' => $matching->user_id,
            'service_content' => $data['service_content'] ?? $initialServiceContent,
            'report_content' => $data['report_content'] ?? null,
            'actual_date' => $fixedDate, // 依頼日を固定（変更不可）
            'actual_start_time' => $data['actual_start_time'] ?? null,
            'actual_end_time' => $data['actual_end_time'] ?? null,
            'status' => 'draft',
        ]);
    }

    public function submitReport(int $reportId, int $guideId): Report
    {
        $report = Report::where('id', $reportId)
            ->where('guide_id', $guideId)
            ->firstOrFail();

        if ($report->status === 'admin_approved' || $report->status === 'approved') {
            throw new \Exception('既に承認済みの報告書です');
        }

        // 報告書を提出状態に更新
        $report->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // ユーザーに通知
        Notification::create([
            'user_id' => $report->user_id,
            'type' => 'report',
            'title' => '報告書が提出されました',
            'message' => 'ガイドから報告書が提出されました。承認または修正依頼を行ってください。',
            'related_id' => $reportId,
        ]);

        // メール通知を送信
        $user = User::find($report->user_id);
        $guide = User::find($report->guide_id);
        if ($user && $guide) {
            $this->emailService->sendReportSubmittedNotification($user, [
                'id' => $report->id,
                'guide_name' => $guide->name,
                'actual_date' => $report->actual_date,
            ]);
        }

        return $report;
    }

    /**
     * ユーザー承認（第1段階）
     */
    public function approveReport(int $reportId, int $userId): Report
    {
        $report = Report::where('id', $reportId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($report->status !== 'submitted') {
            throw new \Exception('提出済みの報告書のみ承認できます');
        }

        // ユーザー承認（第1段階）
        $report->update([
            'status' => 'user_approved',
            'user_approved_at' => now(),
        ]);

        // ガイドに通知
        Notification::create([
            'user_id' => $report->guide_id,
            'type' => 'report',
            'title' => '報告書がユーザー承認されました',
            'message' => '報告書がユーザー承認されました。管理者の承認を待っています。',
            'related_id' => $reportId,
        ]);

        // 管理者に通知
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'report',
                'title' => '報告書の管理者承認待ち',
                'message' => '報告書がユーザー承認されました。管理者承認をお願いします。',
                'related_id' => $reportId,
            ]);
        }

        // メール通知を送信（ユーザー承認時）
        $guide = User::find($report->guide_id);
        if ($guide) {
            $this->emailService->sendReportSubmittedNotification($guide, [
                'id' => $report->id,
                'actual_date' => $report->actual_date,
            ]);
        }

        return $report;
    }

    /**
     * 管理者承認（第2段階）
     */
    public function adminApproveReport(int $reportId, int $adminId): Report
    {
        $report = Report::findOrFail($reportId);

        if ($report->status !== 'user_approved') {
            throw new \Exception('ユーザー承認済みの報告書のみ管理者承認できます');
        }

        // 管理者承認（第2段階）
        $report->update([
            'status' => 'admin_approved',
            'admin_approved_at' => now(),
            'approved_at' => now(), // 後方互換性のため
        ]);

        // マッチングを「完了」状態に更新し、report_completed_atを設定（チャット利用終了日）
        $matching = \App\Models\Matching::find($report->matching_id);
        if ($matching) {
            $updateData = [];

            // ステータスが既にキャンセルでなければ「completed」に更新（報告書が管理者承認された場合は必ず完了）
            if ($matching->status !== 'cancelled') {
                $updateData['status'] = 'completed';
            }

            // 完了日時
            if (!$matching->completed_at) {
                $updateData['completed_at'] = now();
            }

            // 報告書完了日時（チャット利用制限用）
            if (!$matching->report_completed_at) {
                $updateData['report_completed_at'] = now();
            }

            if (!empty($updateData)) {
                $matching->update($updateData);
            }
        }

        // 利用者の限度時間を更新（報告書確定時に自動更新）
        // 報告書を再読み込みして最新のデータを取得
        $report->refresh();
        
        if ($report->actual_date && $report->actual_start_time && $report->actual_end_time) {
            try {
                // 実施時間を計算（分単位）
                // actual_date は date型、actual_start_time/actual_end_time は time型なので組み合わせる必要がある
                $actualDate = Carbon::parse($report->actual_date);
                
                // actual_start_time と actual_end_time を文字列として取得（time型）
                // Carbonオブジェクトの場合はformat()で文字列に変換、そうでない場合はgetRawOriginal()を使用
                if ($report->actual_start_time instanceof Carbon) {
                    $startTimeStr = $report->actual_start_time->format('H:i:s');
                } elseif (is_string($report->actual_start_time)) {
                    $startTimeStr = $report->actual_start_time;
                } else {
                    $startTimeStr = $report->getRawOriginal('actual_start_time');
                }
                
                if ($report->actual_end_time instanceof Carbon) {
                    $endTimeStr = $report->actual_end_time->format('H:i:s');
                } elseif (is_string($report->actual_end_time)) {
                    $endTimeStr = $report->actual_end_time;
                } else {
                    $endTimeStr = $report->getRawOriginal('actual_end_time');
                }
                
                // actual_date と時刻を組み合わせて datetime を作成
                $startDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $startTimeStr);
                $endDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $endTimeStr);
                
                // 終了時刻が開始時刻より小さい場合、翌日とみなす
                if ($endDateTime->lt($startDateTime)) {
                    $endDateTime->addDay();
                }
                
                $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
                $usedHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
                
                // 実施日から年月を取得
                $year = $actualDate->year;
                $month = $actualDate->month;
                
                // 使用時間を追加
                $this->limitService->addUsedHours($report->user_id, $usedHours, $year, $month);
                
                \Log::info('利用時間を計上しました', [
                    'report_id' => $reportId,
                    'user_id' => $report->user_id,
                    'used_hours' => $usedHours,
                    'year' => $year,
                    'month' => $month,
                ]);
            } catch (\Exception $e) {
                \Log::error('利用時間計上エラー', [
                    'report_id' => $reportId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // エラーが発生しても処理は続行（利用時間計上は重要だが、承認処理自体は成功させる）
            }
        } else {
            \Log::warning('利用時間計上スキップ: 必要なデータが不足しています', [
                'report_id' => $reportId,
                'actual_date' => $report->actual_date,
                'actual_start_time' => $report->actual_start_time,
                'actual_end_time' => $report->actual_end_time,
            ]);
        }

        // ユーザーとガイドに通知
        Notification::create([
            'user_id' => $report->user_id,
            'type' => 'report',
            'title' => '報告書が管理者承認されました',
            'message' => '報告書が管理者承認されました。',
            'related_id' => $reportId,
        ]);

        Notification::create([
            'user_id' => $report->guide_id,
            'type' => 'report',
            'title' => '報告書が管理者承認されました',
            'message' => '報告書が管理者承認されました。',
            'related_id' => $reportId,
        ]);

        // メール通知を送信
        $guide = User::find($report->guide_id);
        if ($guide) {
            $this->emailService->sendReportApprovedNotification($guide, [
                'id' => $report->id,
                'actual_date' => $report->actual_date,
            ]);
        }

        return $report;
    }

    public function requestRevision(int $reportId, int $userId, string $revisionNotes): Report
    {
        $report = Report::where('id', $reportId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($report->status !== 'submitted' && $report->status !== 'user_approved') {
            throw new \Exception('提出済みまたはユーザー承認済みの報告書のみ修正依頼できます');
        }

        $report->update([
            'status' => 'revision_requested',
            'revision_notes' => $revisionNotes,
        ]);

        // ガイドに通知
        Notification::create([
            'user_id' => $report->guide_id,
            'type' => 'report',
            'title' => '報告書の修正依頼',
            'message' => "報告書に修正依頼がありました。\n修正内容: {$revisionNotes}",
            'related_id' => $reportId,
        ]);

        return $report;
    }

    public function getGuideReports(int $guideId)
    {
        return Report::where('guide_id', $guideId)
            ->with(['user:id,name', 'request:id,request_type,request_date'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserReport(int $reportId, int $userId)
    {
        return Report::where('id', $reportId)
            ->where('user_id', $userId)
            ->with(['guide:id,name', 'request:id,request_type,request_date'])
            ->firstOrFail();
    }
}
