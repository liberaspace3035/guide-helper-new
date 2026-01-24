-- =====================================================
-- getPendingAcceptances() メソッドで取得しているデータを確認するSQLクエリ
-- =====================================================

-- 1. 最もシンプルな確認: pending状態のガイド承諾データを全て確認
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.status,
    ga.admin_decision,
    ga.user_selected,
    ga.created_at,
    ga.updated_at
FROM guide_acceptances ga
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;

-- 2. リクエスト情報も含めて確認（このメソッドが取得しているデータに近い）
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.status,
    ga.admin_decision,
    ga.user_selected,
    r.request_type,
    r.masked_address,
    r.request_date,
    r.request_time,
    u.name AS user_name,
    g.name AS guide_name,
    ga.created_at
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;

-- 3. より詳細な情報を含む確認（全カラム表示）
SELECT 
    ga.*,
    r.request_type,
    r.masked_address,
    r.request_date,
    r.request_time,
    r.service_content,
    r.status AS request_status,
    u.id AS user_id,
    u.name AS user_name,
    u.email AS user_email,
    g.name AS guide_name,
    g.email AS guide_email
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;

-- 4. データ件数の確認
SELECT 
    COUNT(*) AS pending_count
FROM guide_acceptances
WHERE status = 'pending';

-- 5. ステータス別の集計
SELECT 
    status,
    admin_decision,
    COUNT(*) AS count
FROM guide_acceptances
GROUP BY status, admin_decision
ORDER BY status, admin_decision;

-- 6. テーブル構造の確認
DESCRIBE guide_acceptances;
DESCRIBE requests;
DESCRIBE users;

-- 7. 最新の10件を確認
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.status,
    ga.admin_decision,
    r.request_type,
    r.masked_address,
    u.name AS user_name,
    g.name AS guide_name,
    ga.created_at
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC
LIMIT 10;






