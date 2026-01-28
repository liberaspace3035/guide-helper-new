<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MatchingService;

class MatchingController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    public function accept(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
        ]);

        try {
            // セッション認証を使用
            $guideId = auth()->id();
            
            if (!$guideId) {
                \Log::warning('MatchingController::accept - 認証ユーザーが見つかりません');
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            \Log::info('MatchingController::accept - 依頼承諾開始', [
                'guide_id' => $guideId,
                'request_id' => $request->input('request_id'),
            ]);
            
            $result = $this->matchingService->acceptRequest(
                $request->input('request_id'),
                $guideId
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('MatchingController::accept - エラー', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->input('request_id'),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function decline(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
        ]);

        try {
            // セッション認証を使用
            $guideId = auth()->id();
            
            if (!$guideId) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            // GuideAcceptanceレコードの存在確認
            $acceptance = \App\Models\GuideAcceptance::where('request_id', $request->input('request_id'))
                ->where('guide_id', $guideId)
                ->first();

            if (!$acceptance) {
                return response()->json(['error' => 'この依頼に応募していません'], 400);
            }
            
            // pending状態の場合のみ辞退可能
            if ($acceptance->status !== 'pending') {
                return response()->json(['error' => 'この依頼は既に処理済みです'], 400);
            }
            
            // MatchingServiceを使用して辞退処理
            $this->matchingService->declineRequest(
                $request->input('request_id'),
                $guideId
            );

            \Log::info('MatchingController::decline - 依頼辞退成功', [
                'guide_id' => $guideId,
                'request_id' => $request->input('request_id'),
            ]);

            return response()->json(['message' => '依頼を辞退しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('MatchingController::decline - レコードが見つかりません', [
                'message' => $e->getMessage(),
                'request_id' => $request->input('request_id'),
            ]);
            return response()->json(['error' => 'この依頼に応募していません'], 400);
        } catch (\Exception $e) {
            \Log::error('MatchingController::decline - エラー', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->input('request_id'),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function myMatchings()
    {
        // セッション認証を使用
        $userId = auth()->id();
        
        if (!$userId) {
            return response()->json(['error' => '認証が必要です'], 401);
        }
        
        $matchings = $this->matchingService->getUserMatchings($userId);
        
        return response()->json(['matchings' => $matchings]);
    }

    public function cancel(Request $request, $id)
    {
        try {
            $matching = $this->matchingService->cancelMatching($id, Auth::id());
            return response()->json([
                'message' => 'マッチングをキャンセルしました',
                'matching' => $matching,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}



