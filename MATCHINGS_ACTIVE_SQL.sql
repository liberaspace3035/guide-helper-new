-- =====================================================
-- 進行中のマッチング一覧を確認するSQLクエリ
-- 進行中 = status = 'matched' または 'in_progress'
-- =====================================================

-- 1. 最もシンプルな確認: 進行中のマッチングの基本情報
SELECT 
    id,
    request_id,
    user_id,
    guide_id,
    status,
    matched_at,
    completed_at,
    created_at,
    updated_at
FROM matchings
WHERE status IN ('matched', 'in_progress')
ORDER BY matched_at DESC;

-- 2. ユーザー名とガイド名を含めた確認（詳細版）
SELECT 
    m.id,
    m.request_id,
    m.user_id,
    u.name AS user_name,
    u.email AS user_email,
    m.guide_id,
    g.name AS guide_name,
    g.email AS guide_email,
    m.status,
    m.matched_at,
    m.completed_at,
    m.created_at,
    m.updated_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
WHERE m.status IN ('matched', 'in_progress')
ORDER BY m.matched_at DESC;

-- 3. 依頼情報も含めた詳細な確認（最も推奨）
SELECT 
    m.id AS matching_id,
    m.request_id,
    m.user_id,
    u.name AS user_name,
    u.email AS user_email,
    m.guide_id,
    g.name AS guide_name,
    g.email AS guide_email,
    m.status AS matching_status,
    r.request_type,
    r.masked_address,
    r.request_date,
    r.request_time,
    r.status AS request_status,
    m.matched_at,
    m.completed_at,
    m.created_at,
    m.updated_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status IN ('matched', 'in_progress')
ORDER BY m.matched_at DESC;

-- 4. ステータス別の集計
SELECT 
    status,
    COUNT(*) AS count
FROM matchings
GROUP BY status
ORDER BY 
    CASE status
        WHEN 'matched' THEN 1
        WHEN 'in_progress' THEN 2
        WHEN 'completed' THEN 3
        WHEN 'cancelled' THEN 4
    END;

-- 5. 進行中のマッチングの件数確認
SELECT 
    COUNT(*) AS active_count,
    SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) AS matched_count,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count
FROM matchings
WHERE status IN ('matched', 'in_progress');

-- 6. ユーザー別の進行中マッチング数
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    COUNT(*) AS active_matching_count
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
WHERE m.status IN ('matched', 'in_progress')
GROUP BY u.id, u.name
ORDER BY active_matching_count DESC;

-- 7. ガイド別の進行中マッチング数
SELECT 
    g.id AS guide_id,
    g.name AS guide_name,
    COUNT(*) AS active_matching_count
FROM matchings m
INNER JOIN users g ON m.guide_id = g.id
WHERE m.status IN ('matched', 'in_progress')
GROUP BY g.id, g.name
ORDER BY active_matching_count DESC;

-- 8. 最近のマッチング（直近10件）
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status IN ('matched', 'in_progress')
ORDER BY m.matched_at DESC
LIMIT 10;

-- 9. チャットメッセージ数も含めた確認
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at,
    COUNT(cm.id) AS message_count
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
LEFT JOIN chat_messages cm ON m.id = cm.matching_id
WHERE m.status IN ('matched', 'in_progress')
GROUP BY m.id, m.request_id, u.name, g.name, m.status, r.request_type, r.request_date, r.request_time, m.matched_at
ORDER BY m.matched_at DESC;

-- 10. 報告書の状態も含めた確認（進行中のマッチング）
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at,
    rp.status AS report_status,
    rp.id AS report_id
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
LEFT JOIN reports rp ON m.id = rp.matching_id
WHERE m.status IN ('matched', 'in_progress')
ORDER BY m.matched_at DESC;

-- 11. テーブル構造の確認
DESCRIBE matchings;

-- 12. 特定ユーザーの進行中マッチング（ユーザーIDを指定）
-- 例: ユーザーID = 1 の場合
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status IN ('matched', 'in_progress')
  AND m.user_id = 1  -- ユーザーIDを指定
ORDER BY m.matched_at DESC;

-- 13. 特定ガイドの進行中マッチング（ガイドIDを指定）
-- 例: ガイドID = 2 の場合
SELECT 
    m.id AS matching_id,
    m.request_id,
    u.name AS user_name,
    g.name AS guide_name,
    m.status AS matching_status,
    r.request_type,
    r.request_date,
    r.request_time,
    m.matched_at
FROM matchings m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN users g ON m.guide_id = g.id
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status IN ('matched', 'in_progress')
  AND m.guide_id = 2  -- ガイドIDを指定
ORDER BY m.matched_at DESC;

