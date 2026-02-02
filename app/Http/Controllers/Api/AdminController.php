<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminService;
use App\Services\AdminOperationLogService;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected $adminService;
    protected $logService;

    public function __construct(AdminService $adminService, AdminOperationLogService $logService)
    {
        $this->adminService = $adminService;
        $this->logService = $logService;
    }

    public function requests()
    {
        try {
            $requests = $this->adminService->getAllRequests();
            return response()->json(['requests' => $requests]);
        } catch (\Exception $e) {
            \Log::error('AdminController::requests error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '依頼一覧の取得中にエラーが発生しました'], 500);
        }
    }

    public function acceptances()
    {
        try {
            $acceptances = $this->adminService->getPendingAcceptances();
            return response()->json(['acceptances' => $acceptances]);
        } catch (\Exception $e) {
            \Log::error('AdminController::acceptances error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '承諾一覧の取得中にエラーが発生しました'], 500);
        }
    }

    public function reports()
    {
        try {
            $reports = $this->adminService->getPendingReports();
            return response()->json(['reports' => $reports]);
        } catch (\Exception $e) {
            \Log::error('AdminController::reports error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '報告書一覧の取得中にエラーが発生しました'], 500);
        }
    }

    public function userApprovedReports()
    {
        try {
            $reports = $this->adminService->getUserApprovedReports();
            return response()->json(['reports' => $reports]);
        } catch (\Exception $e) {
            \Log::error('AdminController::userApprovedReports error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'ユーザー承認済み報告書一覧の取得中にエラーが発生しました'], 500);
        }
    }

    public function approveReport(Request $request, $id)
    {
        try {
            $admin = Auth::user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json(['error' => '管理者権限が必要です'], 403);
            }

            $reportService = app(\App\Services\ReportService::class);
            $report = $reportService->adminApproveReport($id, $admin->id);

            // ログ記録
            $this->logService->logReportApproval($admin->id, $id, $request);

            return response()->json([
                'message' => '報告書を管理者承認しました',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            \Log::error('AdminController::approveReport error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function stats()
    {
        try {
            $stats = $this->adminService->getStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('AdminController::stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '統計情報の取得中にエラーが発生しました'], 500);
        }
    }

    public function userStats()
    {
        try {
            $stats = $this->adminService->getUserStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('AdminController::userStats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'ユーザー統計情報の取得中にエラーが発生しました'], 500);
        }
    }

    public function getAutoMatching()
    {
        try {
            $autoMatching = $this->adminService->getAutoMatchingSetting();
            return response()->json(['auto_matching' => $autoMatching]);
        } catch (\Exception $e) {
            \Log::error('AdminController::getAutoMatching error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '自動マッチング設定の取得中にエラーが発生しました'], 500);
        }
    }

    public function updateAutoMatching(Request $request)
    {
        $request->validate([
            'auto_matching' => 'required|boolean',
        ]);

        $this->adminService->updateAutoMatching($request->input('auto_matching'));
        
        return response()->json([
            'message' => '自動マッチング設定が更新されました',
            'auto_matching' => $request->input('auto_matching'),
        ]);
    }

    public function approveMatching(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
            'guide_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $matching = $this->adminService->approveMatching(
                $request->input('request_id'),
                $request->input('guide_id')
            );

            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logMatchingApproval(
                    $admin->id,
                    $request->input('request_id'),
                    $request->input('guide_id'),
                    $request
                );
            }

            return response()->json([
                'message' => 'マッチングが承認されました',
                'matching_id' => $matching->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function rejectMatching(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
            'guide_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->adminService->rejectMatching(
                $request->input('request_id'),
                $request->input('guide_id')
            );

            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logMatchingRejection(
                    $admin->id,
                    $request->input('request_id'),
                    $request->input('guide_id'),
                    $request
                );
            }

            return response()->json(['message' => 'マッチングが却下されました']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reportCsv($id)
    {
        $report = $this->adminService->getReportForCsv($id);
        
        if (!$report) {
            return response()->json(['error' => '報告書が見つかりません'], 404);
        }
        
        $csvHeader = 'ID,利用日,開始時刻,終了時刻,ユーザー名,ユーザーメール,受給者証番号,ガイド名,ガイドメール,従業員番号,依頼タイプ,依頼日,承認日時,サービス内容,報告内容' . "\n";
        $csvRow = sprintf(
            '%d,%s,%s,%s,"%s","%s",%s,"%s","%s",%s,%s,%s,%s,"%s","%s"',
            $report['id'],
            $report['actual_date'],
            $report['actual_start_time'],
            $report['actual_end_time'],
            $report['user_name'],
            $report['user_email'],
            $report['recipient_number'] ?? '',
            $report['guide_name'],
            $report['guide_email'],
            $report['employee_number'] ?? '',
            $report['request_type'],
            $report['request_date'] ?? '',
            $report['approved_at'],
            str_replace('"', '""', $report['service_content'] ?? ''),
            str_replace('"', '""', $report['report_content'] ?? '')
        );
        
        $csv = "\xEF\xBB\xBF" . $csvHeader . $csvRow; // BOMを追加してExcelで正しく表示
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename=report_' . $id . '.csv');
    }

    public function reportsCsv()
    {
        $reports = $this->adminService->getReportsForCsv();
        
        $csvHeader = 'ID,利用日,開始時刻,終了時刻,ユーザー名,ユーザーメール,受給者証番号,ガイド名,ガイドメール,従業員番号,依頼タイプ,依頼日,承認日時' . "\n";
        $csvRows = array_map(function($report) {
            return sprintf(
                '%d,%s,%s,%s,"%s","%s",%s,"%s","%s",%s,%s,%s,%s',
                $report['id'],
                $report['actual_date'],
                $report['actual_start_time'],
                $report['actual_end_time'],
                $report['user_name'],
                $report['user_email'],
                $report['recipient_number'] ?? '',
                $report['guide_name'],
                $report['guide_email'],
                $report['employee_number'] ?? '',
                $report['request_type'],
                $report['request_date'] ?? '',
                $report['approved_at']
            );
        }, $reports);
        
        $csv = "\xEF\xBB\xBF" . $csvHeader . implode("\n", $csvRows); // BOMを追加してExcelで正しく表示
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename=reports.csv');
    }

    public function usageCsv(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $reports = $this->adminService->getUsageForCsv($startDate, $endDate);
        
        $csvHeader = 'ID,利用日,開始時刻,終了時刻,利用時間(分),ユーザー名,受給者証番号,ガイド名,従業員番号,依頼タイプ' . "\n";
        $csvRows = array_map(function($report) {
            return sprintf(
                '%d,%s,%s,%s,%d,"%s",%s,"%s",%s,%s',
                $report['id'],
                $report['actual_date'],
                $report['actual_start_time'],
                $report['actual_end_time'],
                $report['duration_minutes'],
                $report['user_name'],
                $report['recipient_number'] ?? '',
                $report['guide_name'],
                $report['employee_number'] ?? '',
                $report['request_type']
            );
        }, $reports);
        
        $csv = "\xEF\xBB\xBF" . $csvHeader . implode("\n", $csvRows); // BOMを追加してExcelで正しく表示
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename=usage.csv');
    }

    public function users()
    {
        $users = $this->adminService->getAllUsers();
        return response()->json(['users' => $users]);
    }

    public function guides()
    {
        $guides = $this->adminService->getAllGuides();
        return response()->json(['guides' => $guides]);
    }

    public function updateUserProfileExtra(Request $request, int $id)
    {
        $request->validate([
            'recipient_number' => 'nullable|string',
            'admin_comment' => 'nullable|string',
        ]);

        $this->adminService->updateUserProfileExtra(
            $id,
            $request->input('recipient_number'),
            $request->input('admin_comment')
        );

        return response()->json(['message' => 'ユーザー情報を更新しました']);
    }

    public function updateUserProfile(Request $request, int $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'introduction' => 'nullable|string',
            'recipient_number' => 'nullable|string',
            'admin_comment' => 'nullable|string',
        ]);

        try {
            $this->adminService->updateUserProfile($id, $request->only([
                'name',
                'phone',
                'address',
                'contact_method',
                'notes',
                'introduction',
                'recipient_number',
                'admin_comment',
            ]));

            return response()->json(['message' => 'ユーザープロフィールを更新しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'ユーザーが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::updateUserProfile error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateGuideProfileExtra(Request $request, int $id)
    {
        $request->validate([
            'employee_number' => 'nullable|string',
        ]);

        $this->adminService->updateGuideProfileExtra(
            $id,
            $request->input('employee_number')
        );

        return response()->json(['message' => 'ガイド情報を更新しました']);
    }

    public function updateGuideProfile(Request $request, int $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'introduction' => 'nullable|string',
            'available_areas' => 'nullable|array',
            'available_days' => 'nullable|array',
            'available_times' => 'nullable|array',
            'employee_number' => 'nullable|string',
            'admin_comment' => 'nullable|string',
        ]);

        try {
            $this->adminService->updateGuideProfile($id, $request->only([
                'name',
                'phone',
                'address',
                'introduction',
                'available_areas',
                'available_days',
                'available_times',
                'employee_number',
                'admin_comment',
            ]));

            return response()->json(['message' => 'ガイドプロフィールを更新しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'ガイドが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::updateGuideProfile error: ' . $e->getMessage(), [
                'guide_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function approveUser(Request $request, int $id)
    {
        try {
            \Log::info('AdminController::approveUser', [
                'user_id' => $id,
                'admin_id' => Auth::id(),
            ]);
            
            $this->adminService->approveUser($id);
            
            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logUserApproval($admin->id, $id, $request);
            }
            
            return response()->json(['message' => 'ユーザーを承認しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('AdminController::approveUser - User not found', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'ユーザーが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::approveUser - Error', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function approveGuide(Request $request, int $id)
    {
        try {
            \Log::info('AdminController::approveGuide', [
                'guide_id' => $id,
                'admin_id' => Auth::id(),
            ]);
            
            $this->adminService->approveGuide($id);
            
            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logGuideApproval($admin->id, $id, $request);
            }
            
            return response()->json(['message' => 'ガイドを承認しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('AdminController::approveGuide - Guide not found', [
                'guide_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'ガイドが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::approveGuide - Error', [
                'guide_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function rejectUser(Request $request, int $id)
    {
        try {
            \Log::info('AdminController::rejectUser', [
                'user_id' => $id,
                'admin_id' => Auth::id(),
            ]);
            
            $this->adminService->rejectUser($id);
            
            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logUserRejection($admin->id, $id, $request);
            }
            
            return response()->json(['message' => 'ユーザーを拒否しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('AdminController::rejectUser - User not found', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'ユーザーが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::rejectUser - Error', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function rejectGuide(Request $request, int $id)
    {
        try {
            \Log::info('AdminController::rejectGuide', [
                'guide_id' => $id,
                'admin_id' => Auth::id(),
            ]);
            
            $this->adminService->rejectGuide($id);
            
            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                $this->logService->logGuideRejection($admin->id, $id, $request);
            }
            
            return response()->json(['message' => 'ガイドを拒否しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('AdminController::rejectGuide - Guide not found', [
                'guide_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'ガイドが見つかりません'], 404);
        } catch (\Exception $e) {
            \Log::error('AdminController::rejectGuide - Error', [
                'guide_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * 管理操作ログ一覧取得
     */
    public function operationLogs(Request $request)
    {
        try {
            $limit = $request->query('limit', 100);
            $operationType = $request->query('operation_type');
            $targetType = $request->query('target_type');

            $logService = app(\App\Services\AdminOperationLogService::class);
            $logs = $logService->getLogs($limit, $operationType, $targetType);

            return response()->json(['logs' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ユーザーの月次限度時間を設定
     */
    public function setUserMonthlyLimit(Request $request, int $userId)
    {
        $request->validate([
            'limit_hours' => 'required|numeric|min:0',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $limitService = app(\App\Services\UserMonthlyLimitService::class);
            $limit = $limitService->setLimit(
                $userId,
                $request->input('limit_hours'),
                $request->input('year'),
                $request->input('month')
            );

            return response()->json([
                'message' => '限度時間を設定しました',
                'limit' => $limit,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * ユーザーの月次限度時間一覧を取得
     */
    public function getUserMonthlyLimits(Request $request, int $userId)
    {
        try {
            $query = \App\Models\UserMonthlyLimit::where('user_id', $userId);
            
            // 年月でフィルタリング（オプション）
            if ($request->has('year') && $request->has('month')) {
                $query->where('year', $request->input('year'))
                      ->where('month', $request->input('month'));
            }
            
            $limits = $query->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // 使用時間と残時間を計算
            $limitService = app(\App\Services\UserMonthlyLimitService::class);
            $limits = $limits->map(function($limit) use ($limitService) {
                $usedHours = $limitService->getUsedHours($limit->user_id, $limit->year, $limit->month);
                $remainingHours = $limitService->getRemainingHours($limit->user_id, $limit->year, $limit->month);
                
                return [
                    'id' => $limit->id,
                    'user_id' => $limit->user_id,
                    'year' => $limit->year,
                    'month' => $limit->month,
                    'limit_hours' => $limit->limit_hours,
                    'used_hours' => $usedHours,
                    'remaining_hours' => $remainingHours,
                    'created_at' => $limit->created_at,
                    'updated_at' => $limit->updated_at,
                ];
            });

            return response()->json(['limits' => $limits]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

