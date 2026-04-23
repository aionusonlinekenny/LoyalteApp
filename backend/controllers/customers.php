<?php
// GET    /api/customers               list all (staff)
// GET    /api/customers?phone=...     lookup by phone (staff)
// GET    /api/customers?qr=...        lookup by QR / memberId (staff)
// GET    /api/customers/{id}          get one (staff or customer self-lookup via phone session)
// POST   /api/customers               create (staff)
// PUT    /api/customers/{id}/points   add/adjust points (staff)
// POST   /api/customers/lookup        phone lookup for customer website (no auth required)

auth_required();

$db = get_db();

// ── GET single customer ───────────────────────────────────────────────────────
if ($method === 'GET' && $id !== null && $sub === null) {
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) json_error('Customer not found', 404);
    json_success(['customer' => $c]);
}

// ── GET list / search ─────────────────────────────────────────────────────────
if ($method === 'GET' && $id === null) {
    $phone = $_GET['phone'] ?? null;
    $qr    = $_GET['qr']    ?? null;

    if ($phone) {
        $stmt = $db->prepare('SELECT * FROM customers WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $c = $stmt->fetch();
        if (!$c) json_error('Customer not found', 404);
        json_success(['customer' => $c]);
    }

    if ($qr) {
        $stmt = $db->prepare('SELECT * FROM customers WHERE qr_code = ? OR member_id = ? LIMIT 1');
        $stmt->execute([$qr, $qr]);
        $c = $stmt->fetch();
        if (!$c) json_error('Customer not found', 404);
        json_success(['customer' => $c]);
    }

    $stmt = $db->query('SELECT * FROM customers ORDER BY name');
    json_success(['customers' => $stmt->fetchAll()]);
}

// ── POST create customer ──────────────────────────────────────────────────────
if ($method === 'POST' && $id === null) {
    $body  = json_body();
    $name  = trim($body['name']  ?? '');
    $phone = trim($body['phone'] ?? '');
    $email = trim($body['email'] ?? '') ?: null;

    if (!$name || !$phone) json_error('Name and phone are required');

    // Next member_id
    $row      = $db->query("SELECT MAX(CAST(SUBSTRING(member_id, 5) AS UNSIGNED)) AS mx FROM customers")->fetch();
    $seq      = (int)($row['mx'] ?? 0) + 1;
    $memberId = sprintf('LYL-%06d', $seq);
    $nowMs    = (int)(microtime(true) * 1000);
    $cid      = uuid4();

    $db->prepare(
        'INSERT INTO customers (id,member_id,name,phone,email,tier,points,qr_code,created_at,updated_at)
         VALUES (?,?,?,?,?,\'BRONZE\',0,?,?,?)'
    )->execute([$cid, $memberId, $name, $phone, $email, $memberId, $nowMs, $nowMs]);

    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$cid]);
    json_success(['customer' => $stmt->fetch()], 201);
}

// ── DELETE /api/customers/{id} ────────────────────────────────────────────────
if ($method === 'DELETE' && $id !== null) {
    $stmt = $db->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) json_error('Customer not found', 404);

    $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    json_success(['message' => 'Customer deleted']);
}

// ── PUT /api/customers/{id}/points ────────────────────────────────────────────
if ($method === 'PUT' && $id !== null && $sub === 'points') {
    $body   = json_body();
    $delta  = (int)($body['points'] ?? 0);
    $desc   = trim($body['description'] ?? 'Staff adjustment');
    $type   = $delta >= 0 ? 'EARNED' : 'ADJUSTED';
    $nowMs  = (int)(microtime(true) * 1000);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $c = $stmt->fetch();
        if (!$c) { $db->rollBack(); json_error('Customer not found', 404); }

        $newPoints = max(0, $c['points'] + $delta);
        $tier      = tier_from_points($newPoints);

        $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
           ->execute([$newPoints, $tier, $nowMs, $id]);

        $db->prepare(
            'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
             VALUES (?,?,?,?,?,?)'
        )->execute([uuid4(), $id, $type, $delta, $desc, $nowMs]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Transaction failed', 500);
    }

    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    json_success(['customer' => $stmt->fetch()]);
}

json_error('Not found', 404);
