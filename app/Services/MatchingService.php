<?php

namespace App\Services;

use App\Models\Matching;
use App\Models\Request;
use App\Models\GuideAcceptance;
use App\Models\Report;
use App\Models\Notification;
use App\Models\AdminSetting;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function acceptRequest(int $requestId, int $guideId): array
    {
        // ガイドのロールチェック
        $guide = User::findOrFail($guideId);
        if ($guide->role !== 'guide') {
            throw new \Exception('ガイドとして登録されていないユーザーです');
        }

        // 報告書が未提出の場合は承諾不可
        $pendingReports = Report::where('guide_id', $guideId)
            ->whereIn('status', ['draft', 'submitted'])
            ->exists();

        if ($pendingReports) {
            throw new \Exception('未提出または承認待ちの報告書があります。報告書を完了してから新しい依頼を承諾してください');
        }

        // 依頼の存在確認
        $request = Request::findOrFail($requestId);

        // 既に承諾済みかチェック
        $existingAcceptance = GuideAcceptance::where('request_id', $requestId)
            ->where('guide_id', $guideId)
            ->first();

        if ($existingAcceptance) {
            throw new \Exception('この依頼は既に承諾済みです');
        }

        // 承諾レコード作成
        GuideAcceptance::create([
            'request_id' => $requestId,
            'guide_id' => $guideId,
            'status' => 'pending',
            'admin_decision' => 'pending',
        ]);

        // 依頼ステータスを更新
        $request->update(['status' => 'guide_accepted']);

        // 管理者に通知
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'acceptance',
                'title' => 'ガイドが依頼を承諾しました',
                'message' => 'ガイドが依頼を承諾しました。マッチングを確認してください。',
                'related_id' => $requestId,
            ]);
        }

        // 自動マッチング設定を確認
        $autoMatching = AdminSetting::where('setting_key', 'auto_matching')
            ->value('setting_value') === 'true';

        if ($autoMatching) {
            // 自動マッチングの場合は即座にマッチング成立
            $matching = $this->createMatching($requestId, $request->user_id, $guideId);
            return [
                'message' => '依頼を承諾しました。自動マッチングによりマッチングが成立しました。',
                'auto_matched' => true,
                'matching_id' => $matching->id,
            ];
        }

        return [
            'message' => '依頼を承諾しました。管理者の承認を待っています。',
            'auto_matched' => false,
        ];
    }

    public function createMatching(int $requestId, int $userId, int $guideId): Matching
    {
        return DB::transaction(function () use ($requestId, $userId, $guideId) {
            // ガイドのロールチェック
            $guide = User::findOrFail($guideId);
            if ($guide->role !== 'guide') {
                throw new \Exception('ガイドとして登録されていないユーザーです');
            }

            // ユーザーのロールチェック
            $user = User::findOrFail($userId);
            if ($user->role !== 'user') {
                throw new \Exception('ユーザーとして登録されていないユーザーです');
            }

            // マッチング作成
            $matching = Matching::create([
                'request_id' => $requestId,
                'user_id' => $userId,
                'guide_id' => $guideId,
                'status' => 'matched',
            ]);

            // 依頼ステータスを更新
            Request::where('id', $requestId)->update(['status' => 'matched']);

            // 承諾ステータスを更新
            GuideAcceptance::where('request_id', $requestId)
                ->where('guide_id', $guideId)
                ->update([
                    'status' => 'matched',
                    'admin_decision' => 'auto',
                ]);

            // ユーザーとガイドに通知
            Notification::create([
                'user_id' => $userId,
                'type' => 'matching',
                'title' => 'マッチングが成立しました',
                'message' => 'マッチングが成立しました。チャットで詳細を確認してください。',
                'related_id' => $matching->id,
            ]);

            Notification::create([
                'user_id' => $guideId,
                'type' => 'matching',
                'title' => 'マッチングが成立しました',
                'message' => 'マッチングが成立しました。チャットで詳細を確認してください。',
                'related_id' => $matching->id,
            ]);

            // メール通知を送信
            $request = Request::find($requestId);
            if ($request) {
                $user = User::find($userId);
                $guide = User::find($guideId);
                
                if ($user) {
                    $this->emailService->sendMatchingNotification($user, [
                        'id' => $matching->id,
                        'request_type' => $request->request_type,
                        'request_date' => $request->request_date,
                        'request_time' => $request->request_time,
                    ]);
                }
                
                if ($guide) {
                    $this->emailService->sendMatchingNotification($guide, [
                        'id' => $matching->id,
                        'request_type' => $request->request_type,
                        'request_date' => $request->request_date,
                        'request_time' => $request->request_time,
                    ]);
                }
            }

            return $matching;
        });
    }

    public function getUserMatchings(int $userId)
    {
        // 報告書が管理者承認されたマッチング（report_completed_at が設定されている）は除外
        $matchings = Matching::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('guide_id', $userId);
            })
            ->whereNull('report_completed_at') // 管理者承認済みのマッチングを除外
            ->with(['request', 'user', 'guide'])
            ->orderBy('matched_at', 'desc')
            ->get();
        
        // コレクションを強制的に評価してリレーションをロード
        $matchings->each(function ($matching) {
            $matching->request;
            $matching->user;
            $matching->guide;
        });
        
        return $matchings->map(function ($matching) use ($userId) {
                $requestDate = $matching->request->request_date ?? null;
                if ($requestDate && is_object($requestDate) && method_exists($requestDate, 'format')) {
                    $requestDate = $requestDate->format('Y-m-d');
                }
                
                return [
                    'id' => (int) $matching->id,
                    'request_id' => (int) $matching->request_id,
                    'user_id' => (int) $matching->user_id,
                    'guide_id' => (int) $matching->guide_id,
                    'status' => $matching->status,
                    'matched_at' => $matching->matched_at ? ($matching->matched_at instanceof \Carbon\Carbon ? $matching->matched_at->toIso8601String() : $matching->matched_at) : null,
                    'completed_at' => $matching->completed_at ? ($matching->completed_at instanceof \Carbon\Carbon ? $matching->completed_at->toIso8601String() : $matching->completed_at) : null,
                    'report_completed_at' => $matching->report_completed_at ? ($matching->report_completed_at instanceof \Carbon\Carbon ? $matching->report_completed_at->toIso8601String() : $matching->report_completed_at) : null,
                    'guide_name' => $matching->user_id === $userId ? ($matching->guide->name ?? '') : null,
                    'user_name' => $matching->guide_id === $userId ? ($matching->user->name ?? '') : null,
                    'request_type' => $matching->request->request_type ?? '',
                    'masked_address' => $matching->request->masked_address ?? '',
                    'request_date' => $requestDate ?? '',
                    'request_time' => $matching->request->request_time ?? '',
                    'start_time' => $matching->request->start_time ?? null,
                    'end_time' => $matching->request->end_time ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * マッチングキャンセル（ユーザーまたはガイドがキャンセル）
     */
    public function cancelMatching(int $matchingId, int $userId): Matching
    {
        $matching = Matching::findOrFail($matchingId);

        // 権限チェック（ユーザーまたはガイドのみキャンセル可能）
        if ($matching->user_id !== $userId && $matching->guide_id !== $userId) {
            throw new \Exception('このマッチングをキャンセルする権限がありません');
        }

        // 既にキャンセル済みの場合はエラー
        if ($matching->status === 'cancelled') {
            throw new \Exception('このマッチングは既にキャンセルされています');
        }

        // 報告書が提出済みの場合はキャンセル不可
        $report = Report::where('matching_id', $matchingId)
            ->whereIn('status', ['submitted', 'user_approved', 'admin_approved', 'approved'])
            ->first();

        if ($report) {
            throw new \Exception('報告書が提出済みのため、マッチングをキャンセルできません');
        }

        // マッチングをキャンセル状態に更新
        $matching->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        // 依頼ステータスを更新
        $request = Request::find($matching->request_id);
        if ($request) {
            $request->update(['status' => 'pending']);
        }

        // 相手に通知
        $otherUserId = $matching->user_id === $userId ? $matching->guide_id : $matching->user_id;
        Notification::create([
            'user_id' => $otherUserId,
            'type' => 'matching',
            'title' => 'マッチングがキャンセルされました',
            'message' => 'マッチングがキャンセルされました。',
            'related_id' => $matchingId,
        ]);

        return $matching;
    }

    /**
     * ガイドが依頼を辞退する
     */
    public function declineRequest(int $requestId, int $guideId): void
    {
        $acceptance = GuideAcceptance::where('request_id', $requestId)
            ->where('guide_id', $guideId)
            ->where('status', 'pending')
            ->firstOrFail();
        
        // ステータスを更新
        $acceptance->update(['status' => 'declined']);
        
        // ユーザーに通知
        $request = Request::findOrFail($requestId);
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'request',
            'title' => 'ガイドが依頼を辞退しました',
            'message' => "依頼ID {$requestId} について、ガイドが辞退しました。",
            'related_id' => $requestId,
            'created_at' => now(),
        ]);
        
        // 管理者に通知
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'acceptance',
                'title' => 'ガイドが依頼を辞退しました',
                'message' => "依頼ID {$requestId} について、ガイドが辞退しました。",
                'related_id' => $requestId,
                'created_at' => now(),
            ]);
        }
    }
}


