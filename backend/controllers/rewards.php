<?php
// GET    /api/rewards              list active rewards (no auth)
// GET    /api/rewards?active=false list all rewards (staff auth)
// POST   /api/rewards              create reward (staff auth)
// PUT    /api/rewards/{id}         update reward (staff auth)
// DELETE /api/rewards/{id}         delete reward (staff auth)

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

if ($method === 'POST' && $id === null) {
    auth_required();
    $body     = json_body();
    $name     = trim($body['name'] ?? '');
    $desc     = trim($body['description'] ?? '');
    $pts      = (int)($body['points_required'] ?? 0);
    $category = strtoupper(trim($body['category'] ?? 'OTHER'));
    $active   = isset($body['is_active']) ? (int)$body['is_active'] : 1;

    if (!$name || $pts <= 0) json_error('name and points_required are required');
    if (!in_array($category, array('FOOD','DRINK','DISCOUNT','OTHER'))) $category = 'OTHER';

    $nowMs = (int)(microtime(true) * 1000);
    $rid   = uuid4();
    $db->prepare(
        'INSERT INTO rewards (id,name,description,points_required,is_active,category,created_at)
         VALUES (?,?,?,?,?,?,?)'
    )->execute(array($rid, $name, $desc, $pts, $active, $category, $nowMs));

    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($rid));
    json_success(array('reward' => $stmt->fetch()), 201);
}

if ($method === 'PUT' && $id !== null) {
    auth_required();
    $body = json_body();

    $stmt = $db->prepare('SELECT id FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    if (!$stmt->fetch()) json_error('Reward not found', 404);

    $sets   = array();
    $params = array();

    if (array_key_exists('name', $body)) {
        $sets[] = 'name = ?';
        $params[] = trim($body['name']);
    }
    if (array_key_exists('description', $body)) {
        $sets[] = 'description = ?';
        $params[] = trim($body['description']);
    }
    if (array_key_exists('points_required', $body)) {
        $sets[] = 'points_required = ?';
        $params[] = (int)$body['points_required'];
    }
    if (array_key_exists('category', $body)) {
        $cat = strtoupper(trim($body['category']));
        if (!in_array($cat, array('FOOD','DRINK','DISCOUNT','OTHER'))) $cat = 'OTHER';
        $sets[] = 'category = ?';
        $params[] = $cat;
    }
    if (array_key_exists('is_active', $body)) {
        $sets[] = 'is_active = ?';
        $params[] = (int)$body['is_active'];
    }

    if (!empty($sets)) {
        $params[] = $id;
        $db->prepare('UPDATE rewards SET ' . implode(', ', $sets) . ' WHERE id = ?')
           ->execute($params);
    }

    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    json_success(array('reward' => $stmt->fetch()));
}

if ($method === 'DELETE' && $id !== null) {
    auth_required();
    $stmt = $db->prepare('SELECT id FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    if (!$stmt->fetch()) json_error('Reward not found', 404);

    $db->prepare('DELETE FROM rewards WHERE id = ?')->execute(array($id));
    json_success(array('message' => 'Reward deleted'));
}

json_error('Not found', 404);
