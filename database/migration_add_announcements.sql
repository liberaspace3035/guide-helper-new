-- お知らせ機能の追加（PostgreSQL版）
-- お知らせテーブル
CREATE TABLE IF NOT EXISTS announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_audience VARCHAR(20) NOT NULL CHECK (target_audience IN ('user', 'guide', 'all')),
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

COMMENT ON COLUMN announcements.title IS 'お知らせタイトル';
COMMENT ON COLUMN announcements.content IS 'お知らせ本文';
COMMENT ON COLUMN announcements.target_audience IS '対象者（ユーザー向け、ガイド向け、全体向け）';
COMMENT ON COLUMN announcements.created_by IS '作成者（管理者ID）';

CREATE INDEX IF NOT EXISTS idx_target_audience ON announcements(target_audience);
CREATE INDEX IF NOT EXISTS idx_created_at ON announcements(created_at);

-- announcementsテーブルのupdated_atトリガー
CREATE TRIGGER update_announcements_updated_at BEFORE UPDATE ON announcements
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- お知らせ既読管理テーブル
CREATE TABLE IF NOT EXISTS announcement_reads (
    id SERIAL PRIMARY KEY,
    announcement_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (announcement_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_user_id ON announcement_reads(user_id);
CREATE INDEX IF NOT EXISTS idx_announcement_id ON announcement_reads(announcement_id);
