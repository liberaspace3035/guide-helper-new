-- ユーザー登録フォーム拡張のマイグレーション（PostgreSQL版）
-- 氏名分割、年齢、性別、住所フィールドの追加

-- 氏名を分割するためのフィールド追加
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS last_name_kana VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS first_name_kana VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS age INTEGER NULL,
ADD COLUMN IF NOT EXISTS gender VARCHAR(20) NULL CHECK (gender IN ('male', 'female', 'other', 'prefer_not_to_say')),
ADD COLUMN IF NOT EXISTS address TEXT NULL;

COMMENT ON COLUMN users.last_name IS '姓';
COMMENT ON COLUMN users.first_name IS '名';
COMMENT ON COLUMN users.last_name_kana IS '姓カナ';
COMMENT ON COLUMN users.first_name_kana IS '名カナ';
COMMENT ON COLUMN users.age IS '年齢';
COMMENT ON COLUMN users.gender IS '性別';
COMMENT ON COLUMN users.address IS '住所';

-- 既存のnameフィールドのデータをlast_nameとfirst_nameに分割（既存データがある場合）
-- 注意: 既存データがある場合は手動で分割する必要があります
-- PostgreSQL版: SPLIT_PART関数を使用
-- UPDATE users SET last_name = SPLIT_PART(name, ' ', 1), first_name = SPLIT_PART(name, ' ', 2) WHERE name IS NOT NULL AND last_name IS NULL;
