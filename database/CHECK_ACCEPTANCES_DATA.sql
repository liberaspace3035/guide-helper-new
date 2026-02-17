-- 承諾待ちデータの確認クエリ
-- 一括承認機能のデバッグ用

-- 1. 承諾待ちデータの基本情報
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.status,
    ga.admin_decision,
    ga.user_selected,
    pg_typeof(ga.user_selected) AS user_selected_type, -- PostgreSQLの型を確認
    ga.created_at,
    r.request_type,
    u.name AS user_name,
    g.name AS guide_name
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;

-- 2. user_selectedの値の分布を確認
SELECT 
    user_selected,
    pg_typeof(user_selected) AS data_type,
    COUNT(*) AS count
FROM guide_acceptances
WHERE status = 'pending'
GROUP BY user_selected, pg_typeof(user_selected);

-- 3. user_selectedがfalse/nullのデータを確認
SELECT 
    ga.id,
    ga.request_id,
    ga.guide_id,
    ga.user_selected,
    CASE 
        WHEN ga.user_selected IS NULL THEN 'NULL'
        WHEN ga.user_selected = false THEN 'false (boolean)'
        WHEN ga.user_selected = true THEN 'true (boolean)'
        ELSE 'other'
    END AS user_selected_status,
    r.request_type,
    u.name AS user_name,
    g.name AS guide_name
FROM guide_acceptances ga
INNER JOIN requests r ON ga.request_id = r.id
INNER JOIN users u ON r.user_id = u.id
INNER JOIN users g ON ga.guide_id = g.id
WHERE ga.status = 'pending'
ORDER BY ga.created_at DESC;

-- 4. データベースの実際の値をJSON形式で確認
SELECT 
    json_agg(
        json_build_object(
            'id', ga.id,
            'request_id', ga.request_id,
            'guide_id', ga.guide_id,
            'user_selected', ga.user_selected,
            'user_selected_type', pg_typeof(ga.user_selected),
            'status', ga.status,
            'admin_decision', ga.admin_decision
        )
    ) AS acceptances_data
FROM guide_acceptances ga
WHERE ga.status = 'pending';

