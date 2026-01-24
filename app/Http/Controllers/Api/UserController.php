<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserMonthlyLimitService;

class UserController extends Controller
{
    protected $limitService;

    public function __construct(UserMonthlyLimitService $limitService)
    {
        $this->limitService = $limitService;
    }

    /**
     * ユーザー自身の月次限度時間を取得
     */
    public function getMyMonthlyLimit(Request $request)
    {
        try {
            // セッション認証（web）とJWT認証（api）の両方をサポート
            $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            // 年月でフィルタリング（オプション、デフォルトは当月）
            $now = \Carbon\Carbon::now();
            $year = $request->input('year', $now->year);
            $month = $request->input('month', $now->month);
            
            // 限度時間を取得（なければ作成）
            $limit = $this->limitService->getOrCreateLimit($user->id, $year, $month);
            $usedHours = $this->limitService->getUsedHours($user->id, $year, $month);
            $remainingHours = $this->limitService->getRemainingHours($user->id, $year, $month);
            
            return response()->json([
                'limit' => [
                    'id' => $limit->id,
                    'user_id' => $limit->user_id,
                    'year' => $limit->year,
                    'month' => $limit->month,
                    'limit_hours' => (float) $limit->limit_hours,
                    'used_hours' => $usedHours,
                    'remaining_hours' => $remainingHours,
                    'created_at' => $limit->created_at,
                    'updated_at' => $limit->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('UserController::getMyMonthlyLimit error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '月次限度時間の取得中にエラーが発生しました'], 500);
        }
    }

    /**
     * ユーザー自身の月次限度時間一覧を取得
     */
    public function getMyMonthlyLimits(Request $request)
    {
        try {
            // セッション認証（web）とJWT認証（api）の両方をサポート
            $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            $query = \App\Models\UserMonthlyLimit::where('user_id', $user->id);
            
            // 年月でフィルタリング（オプション）
            if ($request->has('year') && $request->has('month')) {
                $query->where('year', $request->input('year'))
                      ->where('month', $request->input('month'));
            }
            
            $limits = $query->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // 使用時間と残時間を計算
            $limits = $limits->map(function($limit) {
                $usedHours = $this->limitService->getUsedHours($limit->user_id, $limit->year, $limit->month);
                $remainingHours = $this->limitService->getRemainingHours($limit->user_id, $limit->year, $limit->month);
                
                return [
                    'id' => $limit->id,
                    'user_id' => $limit->user_id,
                    'year' => $limit->year,
                    'month' => $limit->month,
                    'limit_hours' => (float) $limit->limit_hours,
                    'used_hours' => $usedHours,
                    'remaining_hours' => $remainingHours,
                    'created_at' => $limit->created_at,
                    'updated_at' => $limit->updated_at,
                ];
            });

            return response()->json(['limits' => $limits]);
        } catch (\Exception $e) {
            \Log::error('UserController::getMyMonthlyLimits error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '月次限度時間一覧の取得中にエラーが発生しました'], 500);
        }
    }
}

