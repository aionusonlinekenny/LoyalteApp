<?php
// GET  /api/redemptions?customer_id=...   list redemptions for a customer (staff)
// POST /api/redemptions                   redeem a reward — atomic (staff)

auth_required();

$db = get_db();

if ($method === 'GET') {
    $customerId = $_GET['customer_id'] ?? null;
    if (!$customerId) json_error('customer_id is required');

    $stmt = $db->prepare(
        'SELECT r.*, rw.name AS reward_name, rw.category AS reward_category
         FROM redemptions r
         JOIN rewards rw ON rw.id = r.reward_id
         WHERE r.customer_id = ?
         ORDER BY r.redeemed_at DESC'
    );
    $stmt->execute([$customerId]);
    json_success(['redemptions' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body       = json_body();
    $customerId = $body['customer_id'] ?? '';
    $rewardId   = $body['reward_id']   ?? '';

    if (!$customerId || !$rewardId) json_error('customer_id and reward_id are required');

    $nowMs = (int)(microtime(true) * 1000);
    $db->beginTransaction();
    try {
        // Lock customer row
        $stmtC = $db->prepare('SELECT id, points FROM customers WHERE id = ? FOR UPDATE');
        $stmtC->execute([$customerId]);
        $customer = $stmtC->fetch();
        if (!$customer) { $db->rollBack(); json_error('Customer not found', 404); }

        // Get reward
        $stmtR = $db->prepare('SELECT id, name, points_required, is_active FROM rewards WHERE id = ?');
        $stmtR->execute([$rewardId]);
        $reward = $stmtR->fetch();
        if (!$reward) { $db->rollBack(); json_error('Reward not found', 404); }
        if (!$reward['is_active']) { $db->rollBack(); json_error('Reward is no longer available'); }

        $cost = (int)$reward['points_required'];
        if ($customer['points'] < $cost) {
            $db->rollBack();
            json_error(sprintf(
                'Insufficient points. Need %d, have %d',
                $cost, $customer['points']
            ));
        }

        $newPoints = $customer['points'] - $cost;
        $tier      = tier_from_points($newPoints);

        // Deduct points
        $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
           ->execute([$newPoints, $tier, $nowMs, $customerId]);

        // Log transaction
        $txId = uuid4();
        $db->prepare(
            'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
             VALUES (?,?,\'REDEEMED\',?,?,?)'
        )->execute([$txId, $customerId, -$cost, 'Redeemed: ' . $reward['name'], $nowMs]);

        // Log redemption
        $redId = uuid4();
        $db->prepare(
            'INSERT INTO redemptions (id,customer_id,reward_id,points_used,redeemed_at)
             VALUES (?,?,?,?,?)'
        )->execute([$redId, $customerId, $rewardId, $cost, $nowMs]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Redemption failed: ' . $e->getMessage(), 500);
    }

    json_success([
        'new_points'     => $newPoints,
        'tier'           => $tier,
        'redemption_id'  => $redId,
        'transaction_id' => $txId
    ], 201);
}

json_error('Not found', 404);
