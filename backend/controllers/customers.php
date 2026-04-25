<?php
// GET    /api/customers               list all (staff)
// GET    /api/customers?phone=...     lookup by phone (staff)
// GET    /api/customers?qr=...        lookup by QR / memberId (staff)
// GET    /api/customers/{id}          get one (staff)
// POST   /api/customers               create (staff)
// POST   /api/customers/lookup        phone lookup — public, returns limited info
// PUT    /api/customers/{id}/points   add/adjust points (staff)
// DELETE /api/customers/{id}          delete customer (staff)

// ── POST /api/customers/lookup (public, no auth) ──────────────────────────────
if ($method === 'POST' && $id === 'lookup') {
    $db       = get_db();
    $body     = json_body();
    $phone    = trim($body['phone']     ?? '');
    $memberId = trim($body['member_id'] ?? '');

    if (!$phone && !$memberId) json_error('phone or member_id is required');

    if ($phone) {
        $stmt = $db->prepare(
            'SELECT id, member_id, name, tier, points FROM customers WHERE phone = ? LIMIT 1'
        );
        $stmt->execute([$phone]);
    } else {
        $stmt = $db->prepare(
            'SELECT id, member_id, name, tier, points FROM customers WHERE member_id = ? LIMIT 1'
        );
        $stmt->execute([$memberId]);
    }

    $c = $stmt->fetch();
    if (!$c) json_error('Customer not found', 404);
    json_success(['customer' => $c]);
}

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
        // Normalize: strip non-digits, try 10-digit and 11-digit variants
        $digits = preg_replace('/\D/', '', $phone);
        $local  = ltrim($digits, '1');   // 10-digit without country code
        $long   = '1' . $local;          // 11-digit with country code
        $stmt = $db->prepare(
            'SELECT * FROM customers WHERE phone = ? OR phone = ? OR phone = ? LIMIT 1'
        );
        $stmt->execute([$digits, $local, $long]);
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

// ── PUT /api/customers/{id} — update info ─────────────────────────────────────
if ($method === 'PUT' && $id !== null && $sub === null) {
    $body  = json_body();
    $name  = trim($body['name']  ?? '');
    $phone = preg_replace('/\D/', '', trim($body['phone'] ?? ''));
    $email = isset($body['email']) ? (trim($body['email']) ?: null) : null;

    if (!$name)  json_error('Name is required');
    if (!$phone) json_error('Phone is required');

    // Verify customer exists
    $stmt = $db->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) json_error('Customer not found', 404);

    // Check phone uniqueness (excluding this customer)
    $local = ltrim($phone, '1');
    $long  = '1' . $local;
    $dup = $db->prepare('SELECT id FROM customers WHERE (phone=? OR phone=? OR phone=?) AND id != ? LIMIT 1');
    $dup->execute([$phone, $local, $long, $id]);
    if ($dup->fetch()) json_error('Phone number already registered to another customer');

    $nowMs = (int)(microtime(true) * 1000);
    $db->prepare('UPDATE customers SET name=?, phone=?, email=?, updated_at=? WHERE id=?')
       ->execute([$name, $phone, $email, $nowMs, $id]);

    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    json_success(['customer' => $stmt->fetch()]);
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
