<?php

namespace App\Services;

use App\Models\GuideProposal;
use App\Models\Request;
use App\Models\GuideAcceptance;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserBlock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GuideProposalService
{
    protected MatchingService $matchingService;
    protected MaskAddressService $maskAddressService;

    public function __construct(MatchingService $matchingService, MaskAddressService $maskAddressService)
    {
        $this->matchingService = $matchingService;
        $this->maskAddressService = $maskAddressService;
    }

    /**
     * ガイドが利用者に支援を提案する
     */
    /**
     * @return array フォーマット済み提案データ
     */
    public function create(int $guideId, array $data): array
    {
        $guide = User::findOrFail($guideId);
        if ($guide->role !== 'guide' || !$guide->is_allowed) {
            throw new \Exception('ガイドとして承認されていません');
        }
        $user = User::with('userProfile')->findOrFail($data['user_id']);
        if ($user->role !== 'user' || !$user->is_allowed) {
            throw new \Exception('提案先の利用者が見つかりません');
        }
        if ($user->userProfile && $user->userProfile->accept_guide_proposals === false) {
            throw new \Exception('この利用者はガイドの提案を受け取らない設定です');
        }

        // ブロック関係のチェック
        $isBlocked = UserBlock::where(function ($query) use ($guideId, $user) {
            $query->where('blocker_id', $guideId)->where('blocked_id', $user->id);
        })->orWhere(function ($query) use ($guideId, $user) {
            $query->where('blocker_id', $user->id)->where('blocked_id', $guideId);
        })->exists();

        if ($isBlocked) {
            throw new \Exception('この利用者に提案することはできません');
        }

        $requestType = $data['request_type'] ?? 'outing';
        if (!in_array($requestType, ['outing', 'home'], true)) {
            throw new \Exception('request_type は outing または home を指定してください');
        }
        $this->assertGuideCanProposeRequestType($guide, $requestType);

        // 過去の日付での提案は作成不可
        $proposedDate = $data['proposed_date'] ?? null;
        if ($proposedDate) {
            $proposedDateCarbon = $proposedDate instanceof Carbon ? $proposedDate : Carbon::parse($proposedDate);
            if ($proposedDateCarbon->lt(Carbon::today())) {
                throw new \Exception('過去の日付での提案はできません');
            }
        }

        $model = GuideProposal::create([
            'guide_id' => $guideId,
            'user_id' => $user->id,
            'request_type' => $requestType,
            'proposed_date' => $data['proposed_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'service_content' => $data['service_content'] ?? null,
            'message' => $data['message'] ?? null,
            'prefecture' => $data['prefecture'] ?? null,
            'destination_address' => $data['destination_address'] ?? null,
            'meeting_place' => $data['meeting_place'] ?? null,
            'status' => 'pending',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'guide_proposal',
            'title' => 'ガイドから支援の提案がありました',
            'message' => $guide->name . ' さんから' . ($requestType === 'home' ? '自宅' : '外出') . '支援の提案があります。ダッシュボードでご確認ください。',
            'related_id' => $model->id,
        ]);

        $model->load('guide:id,name', 'user:id,name');
        return $this->formatProposal($model);
    }

    /**
     * ガイドが全利用者に一斉提案する（提案を受け取る設定の利用者のみ）
     *
     * @return array 作成された提案の件数とメッセージ
     */
    public function createForAll(int $guideId, array $data): array
    {
        $guide = User::findOrFail($guideId);
        if ($guide->role !== 'guide' || !$guide->is_allowed) {
            throw new \Exception('ガイドとして承認されていません');
        }
        $requestType = $data['request_type'] ?? 'outing';
        if (!in_array($requestType, ['outing', 'home'], true)) {
            throw new \Exception('request_type は outing または home を指定してください');
        }
        $this->assertGuideCanProposeRequestType($guide, $requestType);

        // 過去の日付での提案は作成不可
        $proposedDate = $data['proposed_date'] ?? null;
        if ($proposedDate) {
            $proposedDateCarbon = $proposedDate instanceof Carbon ? $proposedDate : Carbon::parse($proposedDate);
            if ($proposedDateCarbon->lt(Carbon::today())) {
                throw new \Exception('過去の日付での一斉提案はできません');
            }
        }

        // ガイドとブロック関係にあるユーザーIDを取得
        $blockedUserIds = UserBlock::where('blocker_id', $guideId)->pluck('blocked_id')->toArray();
        $blockedByUserIds = UserBlock::where('blocked_id', $guideId)->pluck('blocker_id')->toArray();
        $allBlockedUserIds = array_unique(array_merge($blockedUserIds, $blockedByUserIds));

        $query = User::where('role', 'user')
            ->where('is_allowed', true)
            ->where(function ($q) {
                $q->whereDoesntHave('userProfile')
                    ->orWhereHas('userProfile', fn ($q2) => $q2->where('accept_guide_proposals', true));
            });
        
        if (!empty($allBlockedUserIds)) {
            $query->whereNotIn('id', $allBlockedUserIds);
        }
        
        $userIds = $query->pluck('id');

        if ($userIds->isEmpty()) {
            throw new \Exception('提案を受け取る設定の利用者がいません');
        }

        $bulkGroupId = \Illuminate\Support\Str::uuid()->toString();
        $created = 0;
        foreach ($userIds as $userId) {
            $model = GuideProposal::create([
                'guide_id' => $guideId,
                'bulk_group_id' => $bulkGroupId,
                'user_id' => $userId,
                'request_type' => $requestType,
                'proposed_date' => $data['proposed_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'service_content' => $data['service_content'] ?? null,
                'message' => $data['message'] ?? null,
                'prefecture' => $data['prefecture'] ?? null,
                'destination_address' => $data['destination_address'] ?? null,
                'meeting_place' => $data['meeting_place'] ?? null,
                'status' => 'pending',
            ]);
            Notification::create([
                'user_id' => $userId,
                'type' => 'guide_proposal',
                'title' => 'ガイドから支援の提案がありました',
                'message' => $guide->name . ' さんから' . ($requestType === 'home' ? '自宅' : '外出') . '支援の提案があります。ダッシュボードでご確認ください。',
                'related_id' => $model->id,
            ]);
            $created++;
        }

        return [
            'created_count' => $created,
            'message' => '全体に一斉提案を送信しました',
        ];
    }

    /**
     * ガイドが指定した依頼タイプを提案可能かを検証
     */
    private function assertGuideCanProposeRequestType(User $guide, string $requestType): void
    {
        $guideProfile = $guide->guideProfile;
        if (!$guideProfile) {
            throw new \Exception('提案を行うには、プロフィールで必要資格を設定してください');
        }

        if ($requestType === 'outing' && !$guideProfile->canSupportOuting()) {
            throw new \Exception('外出支援を提案するには、同行援護一般課程または応用課程の資格が必要です');
        }

        if ($requestType === 'home' && !$guideProfile->canSupportHome()) {
            throw new \Exception('自宅支援を提案するには、介護福祉士・介護実務者研修・介護初任者研修のいずれかの資格が必要です');
        }
    }

    /**
     * ガイドが送った提案一覧（一斉提案は bulk_group_id で1件にまとめて返す）
     * 過去の日付の提案は除外
     */
    public function listForGuide(int $guideId): array
    {
        $proposals = GuideProposal::where('guide_id', $guideId)
            ->whereDate('proposed_date', '>=', Carbon::today())
            ->with(['user:id,name', 'user.userProfile:id,user_id,show_name_in_proposals'])
            ->orderBy('created_at', 'desc')
            ->get();

        $bulkGroups = [];
        $result = [];

        foreach ($proposals as $p) {
            if ($p->bulk_group_id !== null) {
                if (!isset($bulkGroups[$p->bulk_group_id])) {
                    $bulkGroups[$p->bulk_group_id] = [
                        'count' => 0,
                        'accepted' => 0,
                        'rejected' => 0,
                        'pending' => 0,
                        'first' => $p,
                    ];
                }
                $bulkGroups[$p->bulk_group_id]['count']++;
                if ($p->status === 'accepted') {
                    $bulkGroups[$p->bulk_group_id]['accepted']++;
                } elseif ($p->status === 'rejected') {
                    $bulkGroups[$p->bulk_group_id]['rejected']++;
                } else {
                    $bulkGroups[$p->bulk_group_id]['pending']++;
                }
                continue;
            }
            $result[] = $this->formatProposal($p);
        }

        foreach ($bulkGroups as $gid => $g) {
            $first = $g['first'];
            $typeLabel = $first->request_type === 'home' ? '自宅支援' : '外出支援';
            $result[] = [
                'id' => null,
                'bulk_group_id' => $gid,
                'is_bulk' => true,
                'request_type' => $first->request_type,
                'request_type_label' => $typeLabel,
                'proposed_date' => $first->proposed_date?->format('Y-m-d'),
                'total_count' => $g['count'],
                'accepted_count' => $g['accepted'],
                'rejected_count' => $g['rejected'],
                'pending_count' => $g['pending'],
                'status' => $g['pending'] > 0 ? 'pending' : ($g['accepted'] > 0 ? 'accepted' : 'rejected'),
                'created_at' => $first->created_at?->toIso8601String() ?? $first->created_at?->format(\DateTime::ATOM),
            ];
        }

        usort($result, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $result;
    }

    /**
     * 利用者に届いている提案一覧（未対応のみ or 全て）
     * 過去の日付の提案は除外
     */
    public function listForUser(int $userId, bool $pendingOnly = true): array
    {
        $query = GuideProposal::where('user_id', $userId)
            ->whereDate('proposed_date', '>=', Carbon::today())
            ->with('guide:id,name');
        if ($pendingOnly) {
            $query->where('status', 'pending');
        }
        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($p) => $this->formatProposal($p))
            ->toArray();
    }

    /**
     * 利用者が提案を承諾する → 依頼＋マッチングを自動作成
     */
    public function accept(int $proposalId, int $userId): array
    {
        $proposal = GuideProposal::where('id', $proposalId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->firstOrFail();

        // 過去の日付の提案は承諾不可
        $proposedDate = $proposal->proposed_date;
        if ($proposedDate) {
            $proposedDateCarbon = $proposedDate instanceof Carbon ? $proposedDate : Carbon::parse($proposedDate);
            if ($proposedDateCarbon->lt(Carbon::today())) {
                throw new \Exception('この提案は既に期限が過ぎています（提案日が過去です）');
            }
        }

        return DB::transaction(function () use ($proposal) {
            $proposal->update(['status' => 'accepted']);

            $destAddress = $proposal->destination_address ?: '（ガイド提案・詳細はチャットでご確認ください）';
            $prefecture = $proposal->prefecture ?: '';
            $fullAddress = $prefecture . $destAddress;
            $maskedAddress = $this->maskAddressService->maskAddress($fullAddress) ?: $destAddress;

            $startTime = $proposal->start_time ?? '09:00';
            $endTime = $proposal->end_time ?? '10:00';
            $duration = null;
            if ($startTime && $endTime) {
                $s = explode(':', $startTime);
                $e = explode(':', $endTime);
                $mins = ((int) $e[0]) * 60 + ((int) $e[1]) - ((int) $s[0]) * 60 - ((int) $s[1]);
                if ($mins < 0) $mins += 24 * 60;
                $duration = $mins;
            }

            $request = Request::create([
                'user_id' => $proposal->user_id,
                'nominated_guide_id' => $proposal->guide_id,
                'request_type' => $proposal->request_type,
                'prefecture' => $prefecture ?: null,
                'destination_address' => $fullAddress,
                'masked_address' => $maskedAddress,
                'meeting_place' => $proposal->meeting_place,
                'service_content' => $proposal->service_content ?: '（ガイド提案）',
                'request_date' => $proposal->proposed_date,
                'request_time' => $startTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $duration,
                'status' => 'pending',
            ]);

            GuideAcceptance::create([
                'request_id' => $request->id,
                'guide_id' => $proposal->guide_id,
                'status' => 'pending',
                'user_selected' => true,
            ]);

            $matching = $this->matchingService->createMatching($request->id, $proposal->user_id, $proposal->guide_id);

            $guide = User::find($proposal->guide_id);
            Notification::create([
                'user_id' => $proposal->guide_id,
                'type' => 'guide_proposal',
                'title' => '支援の提案が承諾されました',
                'message' => 'あなたの支援提案が承諾されました。ガイドが確定しています。',
                'related_id' => $matching->id,
            ]);

            return [
                'request_id' => $request->id,
                'matching_id' => $matching->id,
                'message' => '提案を承諾しました。ガイドが確定しています。',
            ];
        });
    }

    /**
     * 利用者が提案を拒否する
     */
    public function reject(int $proposalId, int $userId): void
    {
        $proposal = GuideProposal::where('id', $proposalId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->firstOrFail();

        $proposal->update(['status' => 'rejected']);

        Notification::create([
            'user_id' => $proposal->guide_id,
            'type' => 'guide_proposal',
            'title' => '支援の提案が辞退されました',
            'message' => 'ご提案いただいた支援は辞退されました。',
            'related_id' => $proposal->id,
        ]);
    }

    /**
     * 提案先にできる利用者一覧（id, name のみ。ガイドが提案フォームで選択用）
     * 提案を受け取る設定の利用者のみ。氏名表示設定に応じて name または「利用者」を返す。
     */
    public function listUsersForProposal(int $guideId): array
    {
        // ガイドとブロック関係にあるユーザーIDを取得
        $blockedUserIds = UserBlock::where('blocker_id', $guideId)->pluck('blocked_id')->toArray();
        $blockedByUserIds = UserBlock::where('blocked_id', $guideId)->pluck('blocker_id')->toArray();
        $allBlockedUserIds = array_unique(array_merge($blockedUserIds, $blockedByUserIds));

        $query = User::where('role', 'user')
            ->where('is_allowed', true)
            ->with('userProfile:id,user_id,accept_guide_proposals,show_name_in_proposals')
            ->orderBy('name');
        
        if (!empty($allBlockedUserIds)) {
            $query->whereNotIn('id', $allBlockedUserIds);
        }

        return $query->get(['id', 'name'])
            ->filter(fn ($u) => !$u->userProfile || $u->userProfile->accept_guide_proposals !== false)
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => ($u->userProfile && $u->userProfile->show_name_in_proposals) ? $u->name : '利用者',
            ])
            ->values()
            ->toArray();
    }

    private function formatProposal(GuideProposal $p): array
    {
        $typeLabel = $p->request_type === 'home' ? '自宅支援' : '外出支援';
        $user = $p->user;
        $showName = $user && $user->userProfile && $user->userProfile->show_name_in_proposals;
        $userDisplay = $user ? ['id' => $user->id, 'name' => $showName ? $user->name : '利用者'] : null;
        return [
            'id' => $p->id,
            'guide_id' => $p->guide_id,
            'guide' => $p->guide ? ['id' => $p->guide->id, 'name' => $p->guide->name] : null,
            'user_id' => $p->user_id,
            'user' => $userDisplay,
            'request_type' => $p->request_type,
            'request_type_label' => $typeLabel,
            'proposed_date' => $p->proposed_date?->format('Y-m-d'),
            'start_time' => $p->start_time,
            'end_time' => $p->end_time,
            'service_content' => $p->service_content,
            'message' => $p->message,
            'status' => $p->status,
            'created_at' => $p->created_at?->toIso8601String() ?? $p->created_at?->format(\DateTime::ATOM),
        ];
    }
}
