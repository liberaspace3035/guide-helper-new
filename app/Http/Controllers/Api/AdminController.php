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

    public function batchApproveReports(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json(['error' => '管理者権限が必要です'], 403);
            }

            $request->validate([
                'report_ids' => 'required|array',
                'report_ids.*' => 'required|integer|exists:reports,id',
            ]);

            $reportService = app(\App\Services\ReportService::class);
            $results = $reportService->batchAdminApproveReports($request->input('report_ids'), $admin->id);

            foreach ($request->input('report_ids') as $reportId) {
                $this->logService->logReportApproval($admin->id, $reportId, $request);
            }

            return response()->json([
                'message' => '一括承認処理が完了しました',
                'successful_count' => $results['successful_count'],
                'failed_count' => $results['failed_count'],
                'failed_items' => $results['failed_items'],
            ]);
        } catch (\Exception $e) {
            \Log::error('AdminController::batchApproveReports error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function batchReturnReports(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json(['error' => '管理者権限が必要です'], 403);
            }

            $request->validate([
                'report_ids' => 'required|array',
                'report_ids.*' => 'required|integer|exists:reports,id',
                'revision_notes' => 'required|string|max:1000',
            ]);

            $reportService = app(\App\Services\ReportService::class);
            $results = $reportService->batchAdminRequestRevision($request->input('report_ids'), $admin->id, $request->input('revision_notes'));

            foreach ($request->input('report_ids') as $reportId) {
                $this->logService->logReportRevisionRequest($admin->id, $reportId, $request->input('revision_notes'), $request);
            }

            return response()->json([
                'message' => '一括差し戻し処理が完了しました',
                'successful_count' => $results['successful_count'],
                'failed_count' => $results['failed_count'],
                'failed_items' => $results['failed_items'],
            ]);
        } catch (\Exception $e) {
            \Log::error('AdminController::batchReturnReports error: ' . $e->getMessage(), [
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

    public function batchApproveMatchings(Request $request)
    {
        $request->validate([
            'matchings' => 'required|array|min:1',
            'matchings.*.request_id' => 'required|integer|exists:requests,id',
            'matchings.*.guide_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $results = $this->adminService->batchApproveMatchings($request->input('matchings'));

            // ログ記録
            $admin = Auth::user();
            if ($admin && $admin->isAdmin()) {
                foreach ($results['success'] as $success) {
                    $this->logService->logMatchingApproval(
                        $admin->id,
                        $success['request_id'],
                        $success['guide_id'],
                        $request
                    );
                }
            }

            $message = sprintf(
                '%d件のマッチングを承認しました。%s',
                count($results['success']),
                count($results['failed']) > 0 ? sprintf('%d件の承認に失敗しました。', count($results['failed'])) : ''
            );

            return response()->json([
                'message' => $message,
                'results' => $results,
            ]);
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

    public function users(Request $request)
    {
        $sort = $request->query('sort', 'created_desc');
        $search = $request->query('search');
        $allowedSort = ['pending_first', 'created_desc', 'created_asc', 'name_asc', 'name_desc'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_desc';
        }
        $users = $this->adminService->getAllUsers($sort, $search !== null && trim($search) !== '' ? trim($search) : null);
        return response()->json(['users' => $users]);
    }

    public function guides(Request $request)
    {
        $sort = $request->query('sort', 'created_desc');
        $search = $request->query('search');
        $allowedSort = ['pending_first', 'created_desc', 'created_asc', 'name_asc', 'name_desc'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_desc';
        }
        $guides = $this->adminService->getAllGuides($sort, $search !== null && trim($search) !== '' ? trim($search) : null);
        return response()->json(['guides' => $guides]);
    }

    public function updateUserProfileExtra(Request $request, int $id)
    {
        try {
            $request->validate([
                'recipient_number' => 'nullable|string|regex:/^\d{10}$/',
                'admin_comment' => 'nullable|string',
            ], [
                'recipient_number.regex' => '受給者証番号は半角数字10桁で入力してください。',
            ]);
            

            $this->adminService->updateUserProfileExtra(
                $id,
                $request->input('recipient_number'),
                $request->input('admin_comment')
            );

            return response()->json(['message' => 'ユーザー情報を更新しました']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors()
            ], 422);
        }
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
            'recipient_number' => 'nullable|string|regex:/^\d{10}$/',
            'admin_comment' => 'nullable|string',
        ], [
            'recipient_number.regex' => '受給者証番号は半角数字10桁で入力してください。',
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
        try {
            $request->validate([
                'employee_number' => 'nullable|string|regex:/^\d{3}-\d{3}$/',
            ], [
                'employee_number.regex' => '従業員番号は000-000形式（半角数字6桁をハイフンで区切る）で入力してください。',
            ]);

            $this->adminService->updateGuideProfileExtra(
                $id,
                $request->input('employee_number')
            );

            return response()->json(['message' => 'ガイド情報を更新しました']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors()
            ], 422);
        }
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
            'employee_number' => 'nullable|string|regex:/^\d{3}-\d{3}$/',
            'admin_comment' => 'nullable|string',
        ], [
            'employee_number.regex' => '従業員番号は000-000形式（半角数字6桁をハイフンで区切る）で入力してください。',
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
     * ユーザーの月次限度時間を設定（request_type 省略時は外出・自宅両方に同じ値を設定）
     */
    public function setUserMonthlyLimit(Request $request, int $userId)
    {
        $request->validate([
            'limit_hours' => 'required|numeric|min:0',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'request_type' => 'nullable|string|in:outing,home',
        ]);

        try {
            $limitService = app(\App\Services\UserMonthlyLimitService::class);
            $limitHours = (float) $request->input('limit_hours');
            $year = (int) $request->input('year');
            $month = (int) $request->input('month');
            $requestType = $request->input('request_type');

            if ($requestType) {
                $limit = $limitService->setLimit($userId, $limitHours, $year, $month, $requestType);
                return response()->json(['message' => '限度時間を設定しました', 'limit' => $limit]);
            }
            // 未指定の場合は外出・自宅両方に同じ値を設定（後方互換）
            $limitService->setLimit($userId, $limitHours, $year, $month, 'outing');
            $limitService->setLimit($userId, $limitHours, $year, $month, 'home');
            $limit = $limitService->getOrCreateLimit($userId, $year, $month, 'outing');
            return response()->json(['message' => '限度時間を設定しました（外出・自宅）', 'limit' => $limit]);
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

            // 使用時間と残時間を計算（request_type ごと）
            $limitService = app(\App\Services\UserMonthlyLimitService::class);
            $limits = $limits->map(function($limit) use ($limitService) {
                $requestType = $limit->request_type ?? 'outing';
                $usedHours = $limitService->getUsedHours($limit->user_id, $limit->year, $limit->month, $requestType);
                $remainingHours = $limitService->getRemainingHours($limit->user_id, $limit->year, $limit->month, $requestType);

                return [
                    'id' => $limit->id,
                    'user_id' => $limit->user_id,
                    'year' => $limit->year,
                    'month' => $limit->month,
                    'request_type' => $requestType,
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

    /**
     * 全利用者の指定月の限度時間・残時間一覧を取得（照会用・一括表示用）
     */
    public function getUsersMonthlyLimitsSummary(Request $request)
    {
        try {
            $year = $request->query('year') ? (int) $request->query('year') : null;
            $month = $request->query('month') ? (int) $request->query('month') : null;
            $summary = $this->adminService->getAllUsersMonthlyLimitsSummary($year, $month);
            return response()->json([
                'year' => $year ?? (int) now()->format('Y'),
                'month' => $month ?? (int) now()->format('n'),
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 全利用者の指定月の限度時間・残時間一覧をCSVでダウンロード
     */
    public function getUsersMonthlyLimitsSummaryCsv(Request $request)
    {
        try {
            $year = $request->query('year') ? (int) $request->query('year') : (int) now()->format('Y');
            $month = $request->query('month') ? (int) $request->query('month') : (int) now()->format('n');
            $rows = $this->adminService->getAllUsersMonthlyLimitsSummary($year, $month);

            $csvHeader = 'ユーザーID,ユーザー名,メール,受給者証番号,年,月,限度時間(時間),使用時間(時間),残時間(時間)' . "\n";
            $csvRows = array_map(function ($row) {
                return sprintf(
                    '%d,"%s","%s","%s",%d,%d,%s,%s,%s',
                    $row['user_id'],
                    str_replace('"', '""', $row['user_name']),
                    str_replace('"', '""', $row['email']),
                    str_replace('"', '""', $row['recipient_number']),
                    $row['year'],
                    $row['month'],
                    $row['limit_hours'],
                    $row['used_hours'],
                    $row['remaining_hours']
                );
            }, $rows);

            $csv = "\xEF\xBB\xBF" . $csvHeader . implode("\n", $csvRows);

            $filename = sprintf('monthly_limits_%04d%02d.csv', $year, $month);

            return response($csv, 200)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

