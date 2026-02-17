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
        if (!$user) {
            return response()->json(['error' => '認証が必要です'], 401);
        }

        // 完了したマッチングに関連する依頼IDを取得
        $completedMatchingRequestIds = Matching::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('request_id')
            ->toArray();
        
        // 完了したマッチングに関連する依頼とキャンセルされた依頼を除外
        $requests = RequestModel::where('requests.user_id', $user->id)
            ->whereNotIn('requests.id', $completedMatchingRequestIds)
            ->where('requests.status', '!=', 'cancelled') // キャンセルされた依頼を除外
            ->orderBy('requests.created_at', 'desc')
            ->get();
        
        // 依頼IDのリストを取得
        $requestIds = $requests->pluck('id')->toArray();
        
        // マッチング情報を一括取得（依頼がある場合のみ）
        $matchedGuides = collect();
        if (!empty($requestIds)) {
            $matchedGuides = GuideAcceptance::join('requests', 'guide_acceptances.request_id', '=', 'requests.id')
                ->where('requests.user_id', $user->id)
                ->whereIn('guide_acceptances.request_id', $requestIds)
            ->where(function($query) {
                // マッチング成立済み
                $query->where(function($q) {
                    $q->where('guide_acceptances.status', 'matched')
                      ->where('guide_acceptances.admin_decision', 'approved')
                      ->where('guide_acceptances.user_selected', 1);
                })
                // またはユーザーが選択済み
                ->orWhere('guide_acceptances.user_selected', 1);
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
            ->get()
            ->groupBy('request_id')
            ->map(function ($items) {
                // マッチングIDがあるものを優先
                $matched = $items->firstWhere('matching_id', '!=', null);
                return $matched ? [
                    'guide_id' => $matched->guide_id,
                    'matching_id' => $matched->matching_id,
                ] : [
                    'guide_id' => $items->first()->guide_id,
                    'matching_id' => null,
                ];
            });
        }
        
        // リクエストにマッチング情報を追加
        $requests = $requests->map(function ($request) use ($matchedGuides) {
            // request_typeを日本語に変換
            $requestTypeMap = [
                'outing' => '外出',
                'home' => '自宅',
            ];
            $request->request_type = $requestTypeMap[$request->request_type] ?? $request->request_type;
            
            // マッチング情報を追加
            if (isset($matchedGuides[$request->id])) {
                $request->matched_guide_id = $matchedGuides[$request->id]['guide_id'];
                $request->matching_id = $matchedGuides[$request->id]['matching_id'];
            }
            
            return $request;
        });

        return response()->json(['requests' => $requests]);
    }

    public function availableForGuide()
    {
        // セッション認証を使用
        $guide = auth()->user();
        if (!$guide) {
            return response()->json(['error' => '認証が必要です'], 401);
        }
        $guide->load('guideProfile');
        if (!$guide->guideProfile || trim((string) ($guide->guideProfile->introduction ?? '')) === '') {
            return response()->json([
                'error' => '依頼に応募するには、プロフィールの自己PR（自己紹介）の入力が必要です。',
                'introduction_required' => true,
            ], 403);
        }
        $requests = $this->requestService->getAvailableRequestsForGuide($guide->id);
        
        // request_typeを日本語に変換して配列に変換
        $requestsArray = $requests->map(function ($request) {
            $requestTypeMap = [
                'outing' => '外出',
                'home' => '自宅',
            ];
            
            // stdClassオブジェクトを配列に変換
            $requestArray = (array) $request;
            
            // request_typeを日本語に変換
            if (isset($requestArray['request_type'])) {
                $requestArray['request_type'] = $requestTypeMap[$requestArray['request_type']] ?? $requestArray['request_type'];
            }
            
            return $requestArray;
        })->values()->toArray();
        
        return response()->json(['requests' => $requestsArray]);
    }

    public function applicants($id)
    {
        $user = Auth::user();
        
        // リクエスト所有確認
        $request = RequestModel::findOrFail($id);
        if ($request->user_id !== $user->id) {
            return response()->json(['error' => 'この依頼の応募者を見る権限がありません'], 403);
        }

        // 応募ガイドの情報を取得（pending と declined の両方）
        $acceptances = GuideAcceptance::where('request_id', $id)
            ->whereIn('status', ['pending', 'declined'])
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
     * 指名用の利用可能なガイド一覧を取得（地域・性別・年齢・キーワードで検索・ページネーション対応）
     */
    public function availableGuides(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 50);
        $page = max(1, (int) $request->input('page', 1));
        $area = $request->input('area');       // 都道府県
        $gender = $request->input('gender');   // male / female 等
        $ageMin = $request->has('age_min') ? (int) $request->input('age_min') : null;
        $ageMax = $request->has('age_max') ? (int) $request->input('age_max') : null;
        $keyword = $request->input('keyword'); // 自己PR（introduction）のキーワード

        $query = User::query()
            ->where('users.role', 'guide')
            ->where('users.is_allowed', true)
            ->join('guide_profiles', 'users.id', '=', 'guide_profiles.user_id')
            ->select('users.id', 'users.name', 'users.gender', 'users.birth_date', 'guide_profiles.introduction', 'guide_profiles.available_areas');

        if (!empty($area)) {
            $query->whereJsonContains('guide_profiles.available_areas', $area);
        }
        if (!empty($gender)) {
            $query->where('users.gender', $gender);
        }
        if ($ageMin !== null && $ageMin !== '') {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, users.birth_date, CURDATE()) >= ?', [$ageMin]);
        }
        if ($ageMax !== null && $ageMax !== '') {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, users.birth_date, CURDATE()) <= ?', [$ageMax]);
        }
        if (!empty($keyword)) {
            $query->where('guide_profiles.introduction', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
        }

        $query->orderBy('users.name');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $guides = $paginator->getCollection()->map(function ($row) {
            $age = $row->birth_date ? \Carbon\Carbon::parse($row->birth_date)->age : null;
            $availableAreas = is_string($row->available_areas) ? json_decode($row->available_areas, true) : $row->available_areas;
            return [
                'id' => $row->id,
                'name' => $row->name,
                'gender' => $row->gender,
                'age' => $age,
                'introduction' => $row->introduction ?? null,
                'available_areas' => is_array($availableAreas) ? $availableAreas : [],
            ];
        });

        return response()->json([
            'guides' => $guides,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * 依頼をキャンセル
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => '認証が必要です'], 401);
            }

            $requestModel = $this->requestService->cancelRequest($id, $user->id);
            
            return response()->json([
                'message' => '依頼をキャンセルしました',
                'request' => $requestModel,
            ]);
        } catch (\Exception $e) {
            \Log::error('RequestController::cancel - エラー', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $id,
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

