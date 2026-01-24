-- 要件定義書に基づく追加フィールドのマイグレーション（PostgreSQL版）

-- ============================================
-- 1. usersテーブルの拡張
-- ============================================

-- 郵便番号を追加
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(10) NULL,
ADD COLUMN IF NOT EXISTS email_confirmed BOOLEAN DEFAULT FALSE;

COMMENT ON COLUMN users.postal_code IS '郵便番号';
COMMENT ON COLUMN users.email_confirmed IS 'メール確認済み';

-- ============================================
-- 2. user_profilesテーブルの拡張
-- ============================================

ALTER TABLE user_profiles
ADD COLUMN IF NOT EXISTS interview_date_1 TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS interview_date_2 TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS interview_date_3 TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS application_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS visual_disability_status TEXT NULL,
ADD COLUMN IF NOT EXISTS disability_support_level VARCHAR(10) NULL,
ADD COLUMN IF NOT EXISTS daily_life_situation TEXT NULL;

COMMENT ON COLUMN user_profiles.interview_date_1 IS '面談希望日時（第1希望）';
COMMENT ON COLUMN user_profiles.interview_date_2 IS '面談希望日時（第2希望）';
COMMENT ON COLUMN user_profiles.interview_date_3 IS '面談希望日時（第3希望）';
COMMENT ON COLUMN user_profiles.application_reason IS '応募のきっかけ';
COMMENT ON COLUMN user_profiles.visual_disability_status IS '視覚障害の状況';
COMMENT ON COLUMN user_profiles.disability_support_level IS '障害支援区分';
COMMENT ON COLUMN user_profiles.daily_life_situation IS '普段の生活状況';

-- ============================================
-- 3. guide_profilesテーブルの拡張
-- ============================================

ALTER TABLE guide_profiles
ADD COLUMN IF NOT EXISTS application_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS goal TEXT NULL,
ADD COLUMN IF NOT EXISTS qualifications TEXT NULL,
ADD COLUMN IF NOT EXISTS preferred_work_hours TEXT NULL;

COMMENT ON COLUMN guide_profiles.application_reason IS '応募理由';
COMMENT ON COLUMN guide_profiles.goal IS '実現したいこと';
COMMENT ON COLUMN guide_profiles.qualifications IS '保有資格（JSON形式）';
COMMENT ON COLUMN guide_profiles.preferred_work_hours IS '希望勤務時間';

-- 従業員番号の一意制約追加（NULLを除く）
CREATE UNIQUE INDEX IF NOT EXISTS idx_guide_profiles_employee_number_unique 
ON guide_profiles(employee_number) 
WHERE employee_number IS NOT NULL;

-- ============================================
-- 4. requestsテーブルの拡張（指名機能）
-- ============================================

ALTER TABLE requests
ADD COLUMN IF NOT EXISTS nominated_guide_id INTEGER NULL;

COMMENT ON COLUMN requests.nominated_guide_id IS '指名ガイドID';

CREATE INDEX IF NOT EXISTS idx_nominated_guide_id ON requests(nominated_guide_id);

-- 外部キー制約追加
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'fk_nominated_guide'
    ) THEN
        ALTER TABLE requests 
        ADD CONSTRAINT fk_nominated_guide 
        FOREIGN KEY (nominated_guide_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL;
    END IF;
END $$;

-- ============================================
-- 5. 利用者の月次限度時間管理テーブル
-- ============================================

CREATE TABLE IF NOT EXISTS user_monthly_limits (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    year INTEGER NOT NULL,
    month INTEGER NOT NULL,
    limit_hours DECIMAL(5, 2) NOT NULL,
    used_hours DECIMAL(5, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, year, month)
);

COMMENT ON COLUMN user_monthly_limits.limit_hours IS '月次限度時間（時間）';
COMMENT ON COLUMN user_monthly_limits.used_hours IS '使用時間（時間）';

CREATE INDEX IF NOT EXISTS idx_user_id ON user_monthly_limits(user_id);
CREATE INDEX IF NOT EXISTS idx_year_month ON user_monthly_limits(year, month);

-- user_monthly_limitsテーブルのupdated_atトリガー
CREATE TRIGGER update_user_monthly_limits_updated_at BEFORE UPDATE ON user_monthly_limits
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- 6. 管理操作ログテーブル
-- ============================================

CREATE TABLE IF NOT EXISTS admin_operation_logs (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL,
    operation_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INTEGER NULL,
    operation_details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

COMMENT ON COLUMN admin_operation_logs.operation_type IS '操作種別（user_approve, guide_approve, matching_approve等）';
COMMENT ON COLUMN admin_operation_logs.target_type IS '対象種別（user, guide, matching, report等）';
COMMENT ON COLUMN admin_operation_logs.target_id IS '対象ID';
COMMENT ON COLUMN admin_operation_logs.operation_details IS '操作詳細（JSON形式）';

CREATE INDEX IF NOT EXISTS idx_admin_id ON admin_operation_logs(admin_id);
CREATE INDEX IF NOT EXISTS idx_operation_type ON admin_operation_logs(operation_type);
CREATE INDEX IF NOT EXISTS idx_created_at ON admin_operation_logs(created_at);

-- ============================================
-- 7. メールテンプレートテーブル
-- ============================================

CREATE TABLE IF NOT EXISTS email_templates (
    id SERIAL PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON COLUMN email_templates.template_key IS 'テンプレートキー（request_notification, reminder等）';
COMMENT ON COLUMN email_templates.subject IS '件名';
COMMENT ON COLUMN email_templates.body IS '本文';

CREATE INDEX IF NOT EXISTS idx_template_key ON email_templates(template_key);

-- email_templatesテーブルのupdated_atトリガー
CREATE TRIGGER update_email_templates_updated_at BEFORE UPDATE ON email_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 初期テンプレートの挿入
INSERT INTO email_templates (template_key, subject, body) VALUES
('request_notification', '新しい依頼が届きました', '新しい依頼が届きました。詳細を確認してください。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}'),
('matching_notification', 'マッチングが成立しました', 'マッチングが成立しました。チャットで詳細を確認してください。\n\nマッチングID: {{matching_id}}\n依頼タイプ: {{request_type}}\n日時: {{request_date}} {{request_time}}'),
('report_submitted', '報告書が提出されました', '報告書が提出されました。承認または修正依頼を行ってください。\n\n報告書ID: {{report_id}}\nガイド: {{guide_name}}\n実施日: {{actual_date}}'),
('report_approved', '報告書が承認されました', '報告書が承認されました。\n\n報告書ID: {{report_id}}\n実施日: {{actual_date}}'),
('reminder_pending_request', '承認待ちの依頼があります', '承認待ちの依頼があります。確認をお願いします。\n\n依頼ID: {{request_id}}')
ON CONFLICT (template_key) DO UPDATE SET subject=EXCLUDED.subject, body=EXCLUDED.body;

-- ============================================
-- 8. メール通知設定テーブル
-- ============================================

CREATE TABLE IF NOT EXISTS email_notification_settings (
    id SERIAL PRIMARY KEY,
    notification_type VARCHAR(50) UNIQUE NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    reminder_days INTEGER NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON COLUMN email_notification_settings.notification_type IS '通知種別（request, matching, report, reminder）';
COMMENT ON COLUMN email_notification_settings.is_enabled IS '通知有効/無効';
COMMENT ON COLUMN email_notification_settings.reminder_days IS 'リマインド日数（リマインド通知の場合）';

-- email_notification_settingsテーブルのupdated_atトリガー
CREATE TRIGGER update_email_notification_settings_updated_at BEFORE UPDATE ON email_notification_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 初期設定の挿入
INSERT INTO email_notification_settings (notification_type, is_enabled) VALUES
('request', TRUE),
('matching', TRUE),
('report', TRUE),
('reminder', TRUE)
ON CONFLICT (notification_type) DO UPDATE SET is_enabled=EXCLUDED.is_enabled;

-- ============================================
-- 9. チャット利用期間制限のためのインデックス追加
-- ============================================

-- matchingsテーブルに報告書完了日を追加（チャット利用期間の判定用）
ALTER TABLE matchings
ADD COLUMN IF NOT EXISTS report_completed_at TIMESTAMP NULL;

COMMENT ON COLUMN matchings.report_completed_at IS '報告書完了日時（チャット利用終了日）';
