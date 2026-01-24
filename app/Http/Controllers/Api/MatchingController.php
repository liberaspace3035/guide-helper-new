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
            // セッション認証（web）とJWT認証（api）の両方をサポート
            $guideId = Auth::guard('web')->id() ?? Auth::guard('api')->id();
            
            if (!$guideId) {
                \Log::warning('MatchingController::accept - 認証ユーザーが見つかりません', [
                    'web_user' => Auth::guard('web')->id(),
                    'api_user' => Auth::guard('api')->id(),
                ]);
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
            // セッション認証（web）とJWT認証（api）の両方をサポート
            $guideId = Auth::guard('web')->id() ?? Auth::guard('api')->id();
            
            if (!$guideId) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            // 辞退処理（簡易版）
            $acceptance = \App\Models\GuideAcceptance::where('request_id', $request->input('request_id'))
                ->where('guide_id', $guideId)
                ->first();

            if ($acceptance) {
                $acceptance->update(['status' => 'declined']);
            }

            return response()->json(['message' => '依頼を辞退しました']);
        } catch (\Exception $e) {
            \Log::error('MatchingController::decline - エラー', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function myMatchings()
    {
        // セッション認証（web）とJWT認証（api）の両方をサポート
        $userId = Auth::guard('web')->id() ?? Auth::guard('api')->id();
        
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



