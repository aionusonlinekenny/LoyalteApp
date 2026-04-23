<?php
// GET  /api/transactions?customer_id=...   list transactions for a customer (staff)
// POST /api/transactions                   add earned points (staff)

auth_required();

$db = get_db();

if ($method === 'GET') {
    $customerId = $_GET['customer_id'] ?? null;
    if (!$customerId) json_error('customer_id is required');

    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);

    $stmt = $db->prepare(
        'SELECT * FROM loyalty_transactions WHERE customer_id = ?
         ORDER BY created_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->execute([$customerId, $limit, $offset]);
    json_success(['transactions' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body       = json_body();
    $customerId = $body['customer_id'] ?? '';
    $points     = (int)($body['points'] ?? 0);
    $desc       = trim($body['description'] ?? 'Purchase reward');

    if (!$customerId || $points <= 0) json_error('customer_id and positive points are required');

    $nowMs = (int)(microtime(true) * 1000);
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
        $stmt->execute([$customerId]);
        $c = $stmt->fetch();
        if (!$c) { $db->rollBack(); json_error('Customer not found', 404); }

        $newPoints = $c['points'] + $points;
        $tier      = tier_from_points($newPoints);

        $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
           ->execute([$newPoints, $tier, $nowMs, $customerId]);

        $txId = uuid4();
        $db->prepare(
            'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
             VALUES (?,?,\'EARNED\',?,?,?)'
        )->execute([$txId, $customerId, $points, $desc, $nowMs]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Transaction failed', 500);
    }

    $stmt = $db->prepare('SELECT * FROM loyalty_transactions WHERE id = ?');
    $stmt->execute([$txId]);
    json_success(['transaction' => $stmt->fetch()], 201);
}

json_error('Not found', 404);
