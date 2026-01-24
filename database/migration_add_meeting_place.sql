-- 依頼テーブルに待ち合わせ場所と時間範囲フィールドを追加（PostgreSQL版）
ALTER TABLE requests 
ADD COLUMN IF NOT EXISTS meeting_place TEXT NULL,
ADD COLUMN IF NOT EXISTS start_time TIME NULL,
ADD COLUMN IF NOT EXISTS end_time TIME NULL;

COMMENT ON COLUMN requests.meeting_place IS '待ち合わせ場所（外出依頼の場合のみ）';
COMMENT ON COLUMN requests.start_time IS '開始時刻';
COMMENT ON COLUMN requests.end_time IS '終了時刻';

-- 既存のrequest_timeをstart_timeにコピー（後で削除する可能性がある）
UPDATE requests SET start_time = request_time WHERE start_time IS NULL;
