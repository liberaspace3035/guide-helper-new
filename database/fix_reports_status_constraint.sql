-- 報告書テーブルのstatusカラムのCHECK制約を更新
-- admin_approvedステータスを追加

ALTER TABLE reports 
DROP CONSTRAINT IF EXISTS reports_status_check;

ALTER TABLE reports 
ADD CONSTRAINT reports_status_check 
CHECK (status IN ('draft', 'submitted', 'user_approved', 'admin_approved', 'approved', 'revision_requested'));

