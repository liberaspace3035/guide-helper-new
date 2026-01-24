-- 視覚障害者とガイドヘルパーのマッチングアプリケーション データベーススキーマ（PostgreSQL版）

-- ユーザーテーブル（視覚障害者）
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    birth_date DATE NULL,
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'guide', 'admin')),
    is_allowed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_role ON users(role);

-- updated_atを自動更新するためのトリガー関数
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- usersテーブルのupdated_atトリガー
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ユーザープロフィールテーブル
CREATE TABLE IF NOT EXISTS user_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    contact_method VARCHAR(50),
    notes TEXT,
    recipient_number VARCHAR(100),
    admin_comment TEXT,
    introduction TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id)
);

-- ガイドプロフィールテーブル
CREATE TABLE IF NOT EXISTS guide_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    introduction TEXT,
    available_areas TEXT, -- JSON形式で保存（例: ["東京都", "大阪府"]）
    available_days TEXT, -- JSON形式で保存（例: ["平日", "土日"]）
    available_times TEXT, -- JSON形式で保存（例: ["午前", "午後", "夜間"]）
    employee_number VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id)
);

-- 依頼テーブル
CREATE TABLE IF NOT EXISTS requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    request_type VARCHAR(20) NOT NULL CHECK (request_type IN ('外出', '自宅')),
    destination_address TEXT NOT NULL, -- 詳細な住所（マスキング前）
    masked_address VARCHAR(255), -- マスキング後の住所（例: 東京都渋谷区周辺）
    service_content TEXT NOT NULL,
    request_date DATE NOT NULL,
    request_time TIME NOT NULL,
    duration INTEGER, -- 所要時間（分）
    notes TEXT, -- 音声入力やAI整形前のテキスト
    formatted_notes TEXT, -- AI整形後のテキスト
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'guide_accepted', 'matched', 'in_progress', 'completed', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_id ON requests(user_id);
CREATE INDEX IF NOT EXISTS idx_status ON requests(status);
CREATE INDEX IF NOT EXISTS idx_request_date ON requests(request_date);

-- requestsテーブルのupdated_atトリガー
CREATE TRIGGER update_requests_updated_at BEFORE UPDATE ON requests
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ガイド承諾テーブル
CREATE TABLE IF NOT EXISTS guide_acceptances (
    id SERIAL PRIMARY KEY,
    request_id INTEGER NOT NULL,
    guide_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'declined', 'matched', 'rejected')),
    admin_decision VARCHAR(20) DEFAULT 'pending' CHECK (admin_decision IN ('auto', 'approved', 'rejected', 'pending')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (guide_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_request_id ON guide_acceptances(request_id);
CREATE INDEX IF NOT EXISTS idx_guide_id ON guide_acceptances(guide_id);
CREATE INDEX IF NOT EXISTS idx_status ON guide_acceptances(status);

-- guide_acceptancesテーブルのupdated_atトリガー
CREATE TRIGGER update_guide_acceptances_updated_at BEFORE UPDATE ON guide_acceptances
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- マッチングテーブル
CREATE TABLE IF NOT EXISTS matchings (
    id SERIAL PRIMARY KEY,
    request_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    guide_id INTEGER NOT NULL,
    matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    status VARCHAR(20) DEFAULT 'matched' CHECK (status IN ('matched', 'in_progress', 'completed', 'cancelled')),
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (guide_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_request_id ON matchings(request_id);
CREATE INDEX IF NOT EXISTS idx_user_id ON matchings(user_id);
CREATE INDEX IF NOT EXISTS idx_guide_id ON matchings(guide_id);

-- チャットメッセージテーブル
CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    matching_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matching_id) REFERENCES matchings(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_matching_id ON chat_messages(matching_id);
CREATE INDEX IF NOT EXISTS idx_created_at ON chat_messages(created_at);

-- 報告書テーブル
CREATE TABLE IF NOT EXISTS reports (
    id SERIAL PRIMARY KEY,
    matching_id INTEGER NOT NULL,
    request_id INTEGER NOT NULL,
    guide_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    service_content TEXT, -- ユーザー入力内容（編集可能）
    report_content TEXT, -- ガイドの自由記入欄
    actual_date DATE,
    actual_start_time TIME,
    actual_end_time TIME,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'approved', 'revision_requested')),
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matching_id) REFERENCES matchings(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (guide_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_matching_id ON reports(matching_id);
CREATE INDEX IF NOT EXISTS idx_status ON reports(status);
CREATE INDEX IF NOT EXISTS idx_guide_id ON reports(guide_id);

-- reportsテーブルのupdated_atトリガー
CREATE TRIGGER update_reports_updated_at BEFORE UPDATE ON reports
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 通知テーブル
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'request', 'acceptance', 'matching', 'report', etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INTEGER, -- 関連するID（request_id, matching_id等）
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_read_at ON notifications(read_at);
CREATE INDEX IF NOT EXISTS idx_created_at ON notifications(created_at);

-- 管理者設定テーブル
CREATE TABLE IF NOT EXISTS admin_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- admin_settingsテーブルのupdated_atトリガー
CREATE TRIGGER update_admin_settings_updated_at BEFORE UPDATE ON admin_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 初期管理者設定（自動マッチングのON/OFF）
INSERT INTO admin_settings (setting_key, setting_value) VALUES ('auto_matching', 'false')
ON CONFLICT (setting_key) DO UPDATE SET setting_value = admin_settings.setting_value;
