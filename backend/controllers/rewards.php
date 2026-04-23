<?php
// GET /api/rewards          list active rewards (staff — no auth needed for Android lookup)
// The Android app and customer website both call this without auth
// Staff auth is required only for POST/PUT (future admin CRUD)

$db = get_db();

if ($method === 'GET') {
    $activeOnly = ($_GET['active'] ?? 'true') !== 'false';
    if ($activeOnly) {
        $stmt = $db->query('SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required');
    } else {
        auth_required();
        $stmt = $db->query('SELECT * FROM rewards ORDER BY points_required');
    }
    json_success(['rewards' => $stmt->fetchAll()]);
}

// Admin: create / toggle reward
if ($method === 'POST') {
    auth_required();
    $body     = json_body();
    $name     = trim($body['name'] ?? '');
    $desc     = trim($body['description'] ?? '');
    $pts      = (int)($body['points_required'] ?? 0);
    $category = strtoupper(trim($body['category'] ?? 'OTHER'));
    $active   = isset($body['is_active']) ? (int)$body['is_active'] : 1;

    if (!$name || $pts <= 0) json_error('name and points_required are required');
    if (!in_array($category, ['FOOD','DRINK','DISCOUNT','OTHER'])) $category = 'OTHER';

    $nowMs = (int)(microtime(true) * 1000);
    $rid   = uuid4();
    get_db()->prepare(
        'INSERT INTO rewards (id,name,description,points_required,is_active,category,created_at)
         VALUES (?,?,?,?,?,?,?)'
    )->execute([$rid, $name, $desc, $pts, $active, $category, $nowMs]);

    $stmt = get_db()->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute([$rid]);
    json_success(['reward' => $stmt->fetch()], 201);
}

if ($method === 'PUT' && $id !== null) {
    auth_required();
    $body = json_body();
    $db->prepare('UPDATE rewards SET is_active=? WHERE id=?')
       ->execute([(int)($body['is_active'] ?? 1), $id]);
    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute([$id]);
    json_success(['reward' => $stmt->fetch()]);
}

json_error('Not found', 404);
