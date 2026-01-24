<?php

namespace App\Services;

use App\Models\AdminOperationLog;
use Illuminate\Http\Request;

class AdminOperationLogService
{
    /**
     * 管理操作ログを記録
     */
    public function log(
        int $adminId,
        string $operationType,
        string $targetType,
        ?int $targetId = null,
        ?array $operationDetails = null,
        ?Request $request = null
    ): AdminOperationLog {
        $ipAddress = $request ? $request->ip() : null;
        $userAgent = $request ? $request->userAgent() : null;

        return AdminOperationLog::create([
            'admin_id' => $adminId,
            'operation_type' => $operationType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'operation_details' => $operationDetails,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * ユーザー承認ログ
     */
    public function logUserApproval(int $adminId, int $userId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'user_approve',
            'user',
            $userId,
            ['action' => 'approve'],
            $request
        );
    }

    /**
     * ユーザー拒否ログ
     */
    public function logUserRejection(int $adminId, int $userId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'user_reject',
            'user',
            $userId,
            ['action' => 'reject'],
            $request
        );
    }

    /**
     * ガイド承認ログ
     */
    public function logGuideApproval(int $adminId, int $guideId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'guide_approve',
            'guide',
            $guideId,
            ['action' => 'approve'],
            $request
        );
    }

    /**
     * ガイド拒否ログ
     */
    public function logGuideRejection(int $adminId, int $guideId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'guide_reject',
            'guide',
            $guideId,
            ['action' => 'reject'],
            $request
        );
    }

    /**
     * マッチング承認ログ
     */
    public function logMatchingApproval(int $adminId, int $requestId, int $guideId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'matching_approve',
            'matching',
            $requestId,
            [
                'action' => 'approve',
                'request_id' => $requestId,
                'guide_id' => $guideId,
            ],
            $request
        );
    }

    /**
     * マッチング却下ログ
     */
    public function logMatchingRejection(int $adminId, int $requestId, int $guideId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'matching_reject',
            'matching',
            $requestId,
            [
                'action' => 'reject',
                'request_id' => $requestId,
                'guide_id' => $guideId,
            ],
            $request
        );
    }

    /**
     * 報告書承認ログ（ユーザーが承認した場合も記録）
     */
    public function logReportApproval(int $adminId, int $reportId, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'report_approve',
            'report',
            $reportId,
            ['action' => 'approve'],
            $request
        );
    }

    /**
     * 報告書修正依頼ログ
     */
    public function logReportRevisionRequest(int $adminId, int $reportId, string $revisionNotes, Request $request): AdminOperationLog
    {
        return $this->log(
            $adminId,
            'report_revision_request',
            'report',
            $reportId,
            [
                'action' => 'revision_request',
                'revision_notes' => $revisionNotes,
            ],
            $request
        );
    }

    /**
     * ログ一覧を取得
     */
    public function getLogs(int $limit = 100, ?string $operationType = null, ?string $targetType = null)
    {
        $query = AdminOperationLog::with('admin:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($operationType) {
            $query->where('operation_type', $operationType);
        }

        if ($targetType) {
            $query->where('target_type', $targetType);
        }

        return $query->get();
    }
}




