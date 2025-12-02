-- 依頼テーブルに待ち合わせ場所と時間範囲フィールドを追加
ALTER TABLE requests 
ADD COLUMN meeting_place TEXT NULL COMMENT '待ち合わせ場所（外出依頼の場合のみ）' AFTER destination_address,
ADD COLUMN start_time TIME NULL COMMENT '開始時刻' AFTER request_time,
ADD COLUMN end_time TIME NULL COMMENT '終了時刻' AFTER start_time;

-- 既存のrequest_timeをstart_timeにコピー（後で削除する可能性がある）
UPDATE requests SET start_time = request_time WHERE start_time IS NULL;

