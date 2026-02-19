<?php

namespace App\Services;

use App\Models\GuideProposal;
use App\Models\Request;
use App\Models\GuideAcceptance;
use App\Models\Notification;
use App\Models\User;
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
        $user = User::findOrFail($data['user_id']);
        if ($user->role !== 'user' || !$user->is_allowed) {
            throw new \Exception('提案先の利用者が見つかりません');
        }
        $requestType = $data['request_type'] ?? 'outing';
        if (!in_array($requestType, ['outing', 'home'], true)) {
            throw new \Exception('request_type は outing または home を指定してください');
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
     * ガイドが送った提案一覧
     */
    public function listForGuide(int $guideId): array
    {
        return GuideProposal::where('guide_id', $guideId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($p) => $this->formatProposal($p))
            ->toArray();
    }

    /**
     * 利用者に届いている提案一覧（未対応のみ or 全て）
     */
    public function listForUser(int $userId, bool $pendingOnly = true): array
    {
        $query = GuideProposal::where('user_id', $userId)
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
                'message' => 'あなたの支援提案が承諾されました。マッチングが成立しています。',
                'related_id' => $matching->id,
            ]);

            return [
                'request_id' => $request->id,
                'matching_id' => $matching->id,
                'message' => '提案を承諾しました。マッチングが成立しています。',
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
     */
    public function listUsersForProposal(int $guideId): array
    {
        return User::where('role', 'user')
            ->where('is_allowed', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();
    }

    private function formatProposal(GuideProposal $p): array
    {
        $typeLabel = $p->request_type === 'home' ? '自宅支援' : '外出支援';
        return [
            'id' => $p->id,
            'guide_id' => $p->guide_id,
            'guide' => $p->guide ? ['id' => $p->guide->id, 'name' => $p->guide->name] : null,
            'user_id' => $p->user_id,
            'user' => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
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
