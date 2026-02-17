<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserMonthlyLimitService;
use App\Services\DashboardService;

class UserController extends Controller
{
    protected $limitService;
    protected $dashboardService;

    public function __construct(UserMonthlyLimitService $limitService, DashboardService $dashboardService)
    {
        $this->limitService = $limitService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * ユーザー自身の月次限度時間を取得
     */
    public function getMyMonthlyLimit(Request $request)
    {
        try {
            // セッション認証を使用
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            // 年月でフィルタリング（オプション、デフォルトは当月）
            $now = \Carbon\Carbon::now();
            $year = $request->input('year', $now->year);
            $month = $request->input('month', $now->month);
            
            // 外出・自宅それぞれの限度時間を取得
            $outing = $this->limitService->getOrCreateLimit($user->id, $year, $month, 'outing');
            $home = $this->limitService->getOrCreateLimit($user->id, $year, $month, 'home');
            $buildLimit = function ($row) {
                $used = (float) $row->used_hours;
                $limit = (float) $row->limit_hours;
                return [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'year' => $row->year,
                    'month' => $row->month,
                    'request_type' => $row->request_type ?? 'outing',
                    'limit_hours' => $limit,
                    'used_hours' => $used,
                    'remaining_hours' => max(0, $limit - $used),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            };

            return response()->json([
                'year' => (int) $year,
                'month' => (int) $month,
                'limits' => [
                    'outing' => $buildLimit($outing),
                    'home' => $buildLimit($home),
                ],
                // 後方互換: 単一 limit は外出のデータを返す
                'limit' => $buildLimit($outing),
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
            // セッション認証を使用
            $user = auth()->user();
            
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

            // 使用時間と残時間を計算（request_type ごと）
            $limits = $limits->map(function($limit) {
                $requestType = $limit->request_type ?? 'outing';
                $usedHours = $this->limitService->getUsedHours($limit->user_id, $limit->year, $limit->month, $requestType);
                $remainingHours = $this->limitService->getRemainingHours($limit->user_id, $limit->year, $limit->month, $requestType);

                return [
                    'id' => $limit->id,
                    'user_id' => $limit->user_id,
                    'year' => $limit->year,
                    'month' => $limit->month,
                    'request_type' => $requestType,
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

    /**
     * 利用時間統計を取得（月別）
     */
    public function getUsageStats(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            
            // DashboardServiceのメソッドを使用して統計を取得
            // ただし、DashboardServiceは現在の月を取得するので、リフレクションまたは新しいメソッドが必要
            // 一時的に、直接クエリを実行する方法を使用
            
            if ($user->role === 'user') {
                $stats = $this->getUserUsageStatsForMonth($user->id, $year, $month);
            } elseif ($user->role === 'guide') {
                $stats = $this->getGuideUsageStatsForMonth($user->id, $year, $month);
            } else {
                return response()->json(['error' => 'この機能はユーザーまたはガイドのみ利用可能です'], 403);
            }
            
            return response()->json([
                'current_month' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('UserController::getUsageStats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '利用時間統計の取得中にエラーが発生しました'], 500);
        }
    }

    protected function getUserUsageStatsForMonth(int $userId, int $year, int $month): array
    {
        $currentMonthStats = \App\Models\Report::where('reports.user_id', $userId)
            ->whereIn('reports.status', ['admin_approved', 'approved'])
            ->whereNotNull('reports.actual_date')
            ->whereNotNull('reports.actual_start_time')
            ->whereNotNull('reports.actual_end_time')
            ->whereRaw("EXTRACT(YEAR FROM reports.actual_date) = ?", [$year])
            ->whereRaw("EXTRACT(MONTH FROM reports.actual_date) = ?", [$month])
            ->join('requests', 'reports.request_id', '=', 'requests.id')
            ->selectRaw('requests.request_type')
            ->selectRaw("SUM(EXTRACT(EPOCH FROM ((reports.actual_date || ' ' || reports.actual_end_time)::timestamp - (reports.actual_date || ' ' || reports.actual_start_time)::timestamp)) / 60) as total_minutes")
            ->groupBy('requests.request_type')
            ->get();

        $currentMonthTotal = \App\Models\Report::where('user_id', $userId)
            ->whereIn('status', ['admin_approved', 'approved'])
            ->whereNotNull('actual_date')
            ->whereNotNull('actual_start_time')
            ->whereNotNull('actual_end_time')
            ->whereRaw("EXTRACT(YEAR FROM actual_date) = ?", [$year])
            ->whereRaw("EXTRACT(MONTH FROM actual_date) = ?", [$month])
            ->selectRaw("SUM(EXTRACT(EPOCH FROM ((actual_date || ' ' || actual_end_time)::timestamp - (actual_date || ' ' || actual_start_time)::timestamp)) / 60) as total_minutes")
            ->first();

        $typeStats = [
            '外出' => 0,
            '自宅' => 0
        ];

        foreach ($currentMonthStats as $stat) {
            $requestType = $stat->request_type;
            if ($requestType === 'outing') {
                $typeStats['外出'] = round($stat->total_minutes / 60 * 10) / 10;
            } elseif ($requestType === 'home') {
                $typeStats['自宅'] = round($stat->total_minutes / 60 * 10) / 10;
            }
        }

        $totalMinutes = $currentMonthTotal->total_minutes ?? 0;

        return [
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60 * 10) / 10,
            'by_type' => $typeStats
        ];
    }

    protected function getGuideUsageStatsForMonth(int $guideId, int $year, int $month): array
    {
        $currentMonthStats = \App\Models\Report::where('reports.guide_id', $guideId)
            ->whereIn('reports.status', ['admin_approved', 'approved'])
            ->whereNotNull('reports.actual_date')
            ->whereNotNull('reports.actual_start_time')
            ->whereNotNull('reports.actual_end_time')
            ->whereRaw("EXTRACT(YEAR FROM reports.actual_date) = ?", [$year])
            ->whereRaw("EXTRACT(MONTH FROM reports.actual_date) = ?", [$month])
            ->join('requests', 'reports.request_id', '=', 'requests.id')
            ->selectRaw('requests.request_type')
            ->selectRaw("SUM(EXTRACT(EPOCH FROM ((reports.actual_date || ' ' || reports.actual_end_time)::timestamp - (reports.actual_date || ' ' || reports.actual_start_time)::timestamp)) / 60) as total_minutes")
            ->groupBy('requests.request_type')
            ->get();

        $currentMonthTotal = \App\Models\Report::where('guide_id', $guideId)
            ->whereIn('status', ['admin_approved', 'approved'])
            ->whereNotNull('actual_date')
            ->whereNotNull('actual_start_time')
            ->whereNotNull('actual_end_time')
            ->whereRaw("EXTRACT(YEAR FROM actual_date) = ?", [$year])
            ->whereRaw("EXTRACT(MONTH FROM actual_date) = ?", [$month])
            ->selectRaw("SUM(EXTRACT(EPOCH FROM ((actual_date || ' ' || actual_end_time)::timestamp - (actual_date || ' ' || actual_start_time)::timestamp)) / 60) as total_minutes")
            ->first();

        $typeStats = [
            '外出' => 0,
            '自宅' => 0
        ];

        foreach ($currentMonthStats as $stat) {
            $requestType = $stat->request_type;
            if ($requestType === 'outing') {
                $typeStats['外出'] = round($stat->total_minutes / 60 * 10) / 10;
            } elseif ($requestType === 'home') {
                $typeStats['自宅'] = round($stat->total_minutes / 60 * 10) / 10;
            }
        }

        $totalMinutes = $currentMonthTotal->total_minutes ?? 0;

        return [
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60 * 10) / 10,
            'by_type' => $typeStats
        ];
    }
}

