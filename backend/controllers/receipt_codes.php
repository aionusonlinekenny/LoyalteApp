<?php
// GET  /api/receipt_codes              list all codes (staff)
// POST /api/receipt_codes              generate a new code (staff)
// POST /api/receipt_codes/claim        claim a code — no auth, uses session phone (customer website)

$db = get_db();

// ── Customer claim (no staff auth needed) ─────────────────────────────────────
if ($method === 'POST' && $id === 'claim') {
    $body       = json_body();
    $code       = trim(strtolower($body['code'] ?? ''));
    $customerId = trim($body['customer_id'] ?? '');

    if (!$code || !$customerId) {
        json_error('code and customer_id are required');
    }

    $nowMs = (int)(microtime(true) * 1000);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM receipt_codes WHERE code = ? FOR UPDATE');
        $stmt->execute([$code]);
        $rc = $stmt->fetch();

        if (!$rc)                       { $db->rollBack(); json_error('Mã không tồn tại', 404); }
        if ($rc['claimed_by'] !== null) { $db->rollBack(); json_error('Mã này đã được sử dụng'); }
        if ($rc['expires_at'] < $nowMs) { $db->rollBack(); json_error('Mã đã hết hạn'); }

        // Verify customer exists
        $stmtC = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
        $stmtC->execute([$customerId]);
        $customer = $stmtC->fetch();
        if (!$customer) { $db->rollBack(); json_error('Khách hàng không tồn tại', 404); }

        $pts       = (int)$rc['points'];
        $newPoints = $customer['points'] + $pts;
        $tier      = tier_from_points($newPoints);

        // Update customer points
        $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
           ->execute([$newPoints, $tier, $nowMs, $customerId]);

        // Mark code as claimed
        $db->prepare('UPDATE receipt_codes SET claimed_by=?, claimed_at=? WHERE id=?')
           ->execute([$customerId, $nowMs, $rc['id']]);

        // Log transaction
        $db->prepare(
            'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
             VALUES (?,?,\'EARNED\',?,?,?)'
        )->execute([uuid4(), $customerId, $pts, 'Receipt code: ' . strtoupper($rc['code']), $nowMs]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Lỗi hệ thống: ' . $e->getMessage(), 500);
    }

    json_success(['points_added' => $pts, 'new_points' => $newPoints, 'tier' => $tier]);
}

// ── Staff: list codes ─────────────────────────────────────────────────────────
if ($method === 'GET' && $id === null) {
    auth_required();
    $stmt = $db->query(
        'SELECT r.*, c.name AS claimed_by_name
         FROM receipt_codes r
         LEFT JOIN customers c ON c.id = r.claimed_by
         ORDER BY r.created_at DESC LIMIT 100'
    );
    json_success(['codes' => $stmt->fetchAll()]);
}

// ── Staff: generate code ──────────────────────────────────────────────────────
if ($method === 'POST' && $id === null) {
    $staff = auth_required();
    $body  = json_body();

    $points    = (int)($body['points'] ?? 0);
    $expiryDays = (int)($body['expiry_days'] ?? 28);
    $note      = trim($body['note'] ?? '');

    if ($points <= 0) json_error('points must be > 0');

    $nowMs     = (int)(microtime(true) * 1000);
    $expiresAt = $nowMs + ($expiryDays * 86400000);
    $code      = generate_receipt_code();
    $rid       = uuid4();

    $db->prepare(
        'INSERT INTO receipt_codes (id, code, points, expires_at, created_by, created_at, note)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$rid, $code, $points, $expiresAt, $staff['id'], $nowMs, $note ?: null]);

    $stmt = $db->prepare('SELECT * FROM receipt_codes WHERE id = ?');
    $stmt->execute([$rid]);
    json_success(['receipt_code' => $stmt->fetch()], 201);
}

json_error('Not found', 404);

// ─── Word-based code generator ────────────────────────────────────────────────
function generate_receipt_code(): string {
    $words = [
        'apple','brave','cloud','dance','eagle','flame','grape','happy','ivory','jazzy',
        'kite','lemon','maple','noble','ocean','pearl','queen','river','storm','tiger',
        'ultra','vivid','waltz','xenon','yacht','zebra','amber','blaze','coral','drift',
        'ember','frost','glide','honey','india','jewel','karma','laser','lunar','magic',
        'nexus','olive','prism','quest','radar','solar','tempo','ultra','vapor','windy',
    ];
    $db = get_db();
    do {
        $code = $words[array_rand($words)] . ' '
              . $words[array_rand($words)] . ' '
              . $words[array_rand($words)];
        $exists = $db->prepare('SELECT id FROM receipt_codes WHERE code = ?');
        $exists->execute([$code]);
    } while ($exists->fetch());
    return $code;
}
