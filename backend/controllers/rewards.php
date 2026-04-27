<?php
// GET    /api/rewards              list active rewards (no auth)
// GET    /api/rewards?active=false list all rewards (staff auth)
// POST   /api/rewards              create reward (staff auth)
// PUT    /api/rewards/{id}         update reward (staff auth)
// POST   /api/rewards/{id}/image   upload reward image (staff auth, multipart)
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
        'INSERT INTO rewards (id,name,description,points_required,is_active,category,image_url,created_at)
         VALUES (?,?,?,?,?,?,NULL,?)'
    )->execute(array($rid, $name, $desc, $pts, $active, $category, $nowMs));

    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($rid));
    json_success(array('reward' => $stmt->fetch()), 201);
}

if ($method === 'PUT' && $id !== null && $sub === null) {
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

// ── POST /api/rewards/{id}/image  (multipart form upload) ────────────────────
if ($method === 'POST' && $id !== null && $sub === 'image') {
    auth_required();

    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    $reward = $stmt->fetch();
    if (!$reward) json_error('Reward not found', 404);

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_error('Image file is required');
    }

    $file    = $_FILES['image'];
    $mime    = $file['type'];
    $allowed = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif');
    if (!in_array($mime, $allowed)) {
        json_error('Only JPEG, PNG, WebP, or GIF images are allowed');
    }

    $extMap = array('image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $ext    = isset($extMap[$mime]) ? $extMap[$mime] : 'jpg';

    // Enforce max 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        json_error('Image must be under 5 MB');
    }

    $uploadDir = __DIR__ . '/../uploads/rewards/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Delete old image file if it exists
    if (!empty($reward['image_url'])) {
        $oldFile = __DIR__ . '/../uploads/rewards/' . basename($reward['image_url']);
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $filename = $id . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        json_error('Failed to save image', 500);
    }

    // Build absolute URL
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $imageUrl = $scheme . '://' . $host . '/loyalteapp/backend/uploads/rewards/' . $filename;

    $db->prepare('UPDATE rewards SET image_url = ? WHERE id = ?')
       ->execute(array($imageUrl, $id));

    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    json_success(array('reward' => $stmt->fetch()));
}

if ($method === 'DELETE' && $id !== null) {
    auth_required();
    $stmt = $db->prepare('SELECT * FROM rewards WHERE id = ?');
    $stmt->execute(array($id));
    $reward = $stmt->fetch();
    if (!$reward) json_error('Reward not found', 404);

    // Delete image file if present
    if (!empty($reward['image_url'])) {
        $oldFile = __DIR__ . '/../uploads/rewards/' . basename($reward['image_url']);
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $db->prepare('DELETE FROM rewards WHERE id = ?')->execute(array($id));
    json_success(array('message' => 'Reward deleted'));
}

json_error('Not found', 404);
