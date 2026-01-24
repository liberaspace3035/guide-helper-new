-- 完了したマッチングとその依頼を確認するクエリ

-- 1. すべてのマッチングとそのステータス、関連する依頼IDを確認
SELECT 
    m.id as matching_id,
    m.request_id,
    m.status as matching_status,
    m.completed_at,
    r.id as request_id,
    r.user_id,
    r.status as request_status,
    r.request_date,
    r.request_time,
    r.service_content
FROM matchings m
INNER JOIN requests r ON m.request_id = r.id
ORDER BY m.created_at DESC;

-- 2. 完了（completed）ステータスのマッチングを確認
SELECT 
    m.id as matching_id,
    m.request_id,
    m.status as matching_status,
    m.completed_at,
    r.id as request_id,
    r.user_id,
    r.status as request_status,
    r.created_at as request_created_at
FROM matchings m
INNER JOIN requests r ON m.request_id = r.id
WHERE m.status = 'completed'
ORDER BY m.completed_at DESC;

-- 3. 報告書が承認（approved）されているマッチングを確認
SELECT 
    rp.id as report_id,
    rp.matching_id,
    rp.request_id,
    rp.status as report_status,
    rp.approved_at,
    m.status as matching_status,
    m.completed_at,
    req.id as request_id,
    req.user_id,
    req.created_at as request_created_at
FROM reports rp
INNER JOIN matchings m ON rp.matching_id = m.id
INNER JOIN requests req ON rp.request_id = req.id
WHERE rp.status = 'approved'
ORDER BY rp.approved_at DESC;

-- 4. GuideAcceptanceとマッチングの状態を確認
SELECT 
    ga.id as acceptance_id,
    ga.request_id,
    ga.guide_id,
    ga.status as acceptance_status,
    ga.admin_decision,
    ga.user_selected,
    m.id as matching_id,
    m.status as matching_status,
    m.completed_at,
    req.user_id,
    req.created_at as request_created_at
FROM guide_acceptances ga
INNER JOIN requests req ON ga.request_id = req.id
LEFT JOIN matchings m ON m.request_id = ga.request_id AND m.guide_id = ga.guide_id
ORDER BY ga.created_at DESC;

-- 5. 特定のユーザーの完了したマッチングを確認
-- このクエリのuser_idを実際のユーザーIDに置き換えてください
SELECT 
    m.id as matching_id,
    m.request_id,
    m.status as matching_status,
    m.completed_at,
    r.id as request_id,
    r.user_id,
    r.status as request_status,
    r.service_content,
    r.request_date,
    rp.id as report_id,
    rp.status as report_status,
    rp.approved_at
FROM matchings m
INNER JOIN requests r ON m.request_id = r.id
LEFT JOIN reports rp ON rp.matching_id = m.id
WHERE r.user_id = ? -- ここにユーザーIDを指定
  AND m.status = 'completed'
ORDER BY m.completed_at DESC;



