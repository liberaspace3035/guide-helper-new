<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\RequestService;
use App\Models\Request as RequestModel;
use App\Models\GuideAcceptance;
use App\Models\Matching;
use App\Models\User;

class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    public function myRequests()
    {
        $user = Auth::user();
        
        // 完了したマッチングに関連する依頼IDを取得
        $completedMatchingRequestIds = Matching::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('request_id')
            ->toArray();
        
        // 完了したマッチングに関連する依頼を除外
        $requests = RequestModel::where('requests.user_id', $user->id)
            ->whereNotIn('requests.id', $completedMatchingRequestIds)
            ->leftJoin('matchings', 'requests.id', '=', 'matchings.request_id')
            ->select('requests.*', 'matchings.id as matching_id')
            ->orderBy('requests.created_at', 'desc')
            ->get()
            ->map(function ($request) {
                // request_typeを日本語に変換
                $requestTypeMap = [
                    'outing' => '外出',
                    'home' => '自宅',
                ];
                $request->request_type = $requestTypeMap[$request->request_type] ?? $request->request_type;
                return $request;
            });

        return response()->json(['requests' => $requests]);
    }

    public function availableForGuide()
    {
        // セッション認証（web）とJWT認証（api）の両方をサポート
        $guide = Auth::guard('web')->user() ?? Auth::guard('api')->user();
        
        if (!$guide) {
            return response()->json(['error' => '認証が必要です'], 401);
        }
        
        $requests = $this->requestService->getAvailableRequestsForGuide($guide->id);
        
        // request_typeを日本語に変換（stdClassオブジェクトのプロパティを更新）
        $requests = $requests->map(function ($request) {
            $requestTypeMap = [
                'outing' => '外出',
                'home' => '自宅',
            ];
            // stdClassオブジェクトのプロパティを更新
            if (isset($request->request_type)) {
                $request->request_type = $requestTypeMap[$request->request_type] ?? $request->request_type;
            }
            return $request;
        });
        
        return response()->json(['requests' => $requests]);
    }

    public function applicants($id)
    {
        $user = Auth::user();
        
        // リクエスト所有確認
        $request = RequestModel::findOrFail($id);
        if ($request->user_id !== $user->id) {
            return response()->json(['error' => 'この依頼の応募者を見る権限がありません'], 403);
        }

        // 応募ガイドの情報を取得
        $acceptances = GuideAcceptance::where('request_id', $id)
            ->where('status', 'pending')
            ->with(['guide:id,name,gender,birth_date', 'guide.guideProfile:id,user_id,introduction'])
            ->get();

        $guides = $acceptances->map(function ($acceptance) {
            $guide = $acceptance->guide;
            $age = $guide->birth_date ? \Carbon\Carbon::parse($guide->birth_date)->age : null;
            $matching = Matching::where('request_id', $acceptance->request_id)
                ->where('guide_id', $acceptance->guide_id)
                ->first();
            
            return [
                'guide_id' => $acceptance->guide_id,
                'name' => $guide->name ?? '',
                'gender' => $guide->gender ?? null,
                'age' => $age,
                'introduction' => $guide->guideProfile->introduction ?? null,
                'status' => $acceptance->status,
                'admin_decision' => $acceptance->admin_decision,
                'user_selected' => $acceptance->user_selected ?? false,
                'matching_id' => $matching->id ?? null,
            ];
        });

        return response()->json(['guides' => $guides]);
    }

    public function matchedGuides()
    {
        $user = Auth::user();
        
        // マッチング成立済みのガイド（status = 'matched' かつ admin_decision = 'approved' かつ user_selected = 1）
        // ただし、報告書が承認されて完了（status = 'completed'）したマッチングは除外
        $matched = GuideAcceptance::join('requests', 'guide_acceptances.request_id', '=', 'requests.id')
            ->where('requests.user_id', $user->id)
            ->where('guide_acceptances.status', 'matched')
            ->where('guide_acceptances.admin_decision', 'approved')
            ->where('guide_acceptances.user_selected', 1)
            ->leftJoin('matchings', function($join) {
                $join->on('matchings.request_id', '=', 'guide_acceptances.request_id')
                     ->on('matchings.guide_id', '=', 'guide_acceptances.guide_id');
            })
            ->where(function($query) {
                // マッチングのステータスが 'completed' でないもの、またはマッチングが存在しないもの
                $query->whereNull('matchings.status')
                      ->orWhere('matchings.status', '!=', 'completed');
            })
            ->select('guide_acceptances.request_id', 'guide_acceptances.guide_id', 'matchings.id as matching_id', 'matchings.status as matching_status')
            ->orderBy('guide_acceptances.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'request_id' => $item->request_id,
                    'guide_id' => $item->guide_id,
                    'matching_id' => $item->matching_id,
                ];
            });

        // ユーザーが選択済みだが、まだマッチング成立していないガイド（user_selected = 1）
        // ただし、報告書が承認されて完了（status = 'completed'）したマッチングは除外
        $selected = GuideAcceptance::join('requests', 'guide_acceptances.request_id', '=', 'requests.id')
            ->where('requests.user_id', $user->id)
            ->where('guide_acceptances.user_selected', 1)
            ->where(function($query) {
                $query->where('guide_acceptances.status', '!=', 'matched')
                      ->orWhere('guide_acceptances.admin_decision', '!=', 'approved')
                      ->orWhereNull('guide_acceptances.admin_decision');
            })
            ->leftJoin('matchings', function($join) {
                $join->on('matchings.request_id', '=', 'guide_acceptances.request_id')
                     ->on('matchings.guide_id', '=', 'guide_acceptances.guide_id');
            })
            ->where(function($query) {
                // マッチングのステータスが 'completed' でないもの、またはマッチングが存在しないもの
                $query->whereNull('matchings.status')
                      ->orWhere('matchings.status', '!=', 'completed');
            })
            ->select('guide_acceptances.request_id', 'guide_acceptances.guide_id', 'matchings.id as matching_id')
            ->orderBy('guide_acceptances.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'request_id' => $item->request_id,
                    'guide_id' => $item->guide_id,
                    'matching_id' => $item->matching_id,
                ];
            });

        // selectedにmatchedを追加（重複を避けるため、matchedのrequest_id+guide_idの組み合わせを収集）
        $matchedKeys = $matched->map(function ($item) {
            return $item['request_id'] . '_' . $item['guide_id'];
        })->toArray();
        
        // selectedからmatchedに含まれるものを除外
        $selectedOnly = $selected->filter(function ($item) use ($matchedKeys) {
            return !in_array($item['request_id'] . '_' . $item['guide_id'], $matchedKeys);
        });
        
        // 両方をマージ
        $allSelected = $matched->concat($selectedOnly)->values();

        return response()->json([
            'matched' => $matched,
            'selected' => $allSelected
        ]);
    }

    public function selectGuide(Request $request, $id)
    {
        $user = Auth::user();
        $guideId = $request->input('guide_id');

        if (!$guideId) {
            return response()->json(['error' => 'guide_idを指定してください'], 400);
        }

        // リクエスト所有確認
        $requestModel = RequestModel::findOrFail($id);
        if ($requestModel->user_id !== $user->id) {
            return response()->json(['error' => 'この依頼を選択する権限がありません'], 403);
        }

        // ガイド選択処理
        $acceptance = GuideAcceptance::where('request_id', $id)
            ->where('guide_id', $guideId)
            ->firstOrFail();

        $acceptance->update(['user_selected' => 1]);

        // 自動マッチング設定を確認
        $autoMatching = \App\Models\AdminSetting::where('setting_key', 'auto_matching')
            ->value('setting_value') === 'true';

        if ($autoMatching) {
            // 自動マッチングの場合は即座にマッチング成立
            $matchingService = app(\App\Services\MatchingService::class);
            $matching = $matchingService->createMatching($id, $user->id, $guideId);
            
            return response()->json([
                'message' => 'ガイドが選択されました。自動マッチングによりマッチングが成立しました。',
                'auto_matching' => true,
                'matching_id' => $matching->id,
            ]);
        }

        return response()->json([
            'message' => 'ガイドが選択されました。管理者の承認を待っています。',
            'auto_matching' => false,
        ]);
    }

    /**
     * 指名用の利用可能なガイド一覧を取得
     */
    public function availableGuides()
    {
        // 承認済みのガイドのみを取得
        $guides = User::where('role', 'guide')
            ->where('is_allowed', true)
            ->with('guideProfile:id,user_id,introduction')
            ->select('id', 'name', 'gender', 'birth_date')
            ->orderBy('name')
            ->get()
            ->map(function ($guide) {
                $age = $guide->birth_date ? \Carbon\Carbon::parse($guide->birth_date)->age : null;
                return [
                    'id' => $guide->id,
                    'name' => $guide->name,
                    'gender' => $guide->gender,
                    'age' => $age,
                    'introduction' => $guide->guideProfile->introduction ?? null,
                ];
            });

        return response()->json(['guides' => $guides]);
    }
}

