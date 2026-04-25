<?php
// GET  /api/receipt_codes              list all codes (staff)
// POST /api/receipt_codes              generate a new code (staff)
// POST /api/receipt_codes/claim        claim a code — no auth, uses session phone (customer website)

$db = get_db();

// ── POST /api/receipt_codes/claim_payment  (public — claim by Payment ID) ─────
if ($method === 'POST' && $id === 'claim_payment') {
    $body      = json_body();
    $phone     = preg_replace('/\D/', '', isset($body['phone'])      ? $body['phone']      : '');
    $paymentId = strtoupper(trim(       isset($body['payment_id'])   ? $body['payment_id'] : ''));

    if (strlen($phone) < 10)  json_error('Số điện thoại không hợp lệ');
    if (!$paymentId)           json_error('payment_id là bắt buộc');

    $nowMs   = (int)(microtime(true) * 1000);
    $phone10 = ltrim($phone, '1');
    $phone11 = '1' . $phone10;

    // Find customer by phone (try all 3 variants)
    $stmt = $db->prepare('SELECT * FROM customers WHERE phone=? OR phone=? OR phone=? LIMIT 1');
    $stmt->execute(array($phone, $phone10, $phone11));
    $customer = $stmt->fetch();
    if (!$customer) json_error('Số điện thoại chưa đăng ký. Vui lòng đăng ký tại quầy để tích điểm.');

    // Load Clover config
    $cfgRows      = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
    $cfg          = $cfgRows ?: array();
    $accessToken  = isset($cfg['access_token'])   ? $cfg['access_token']   : '';
    $mId          = isset($cfg['merchant_id'])     ? $cfg['merchant_id']    : '';
    $env          = isset($cfg['environment'])     ? $cfg['environment']    : 'sandbox';
    $ptsPerDollar = max(1, (int)(isset($cfg['points_per_dollar']) ? $cfg['points_per_dollar'] : 1));

    $amountCents = 0;
    $merchantId  = $mId;

    $db->beginTransaction();
    try {
        // Check logs by payment_id OR order_id (QR on receipt may encode either)
        $logStmt = $db->prepare(
            'SELECT * FROM clover_payment_logs WHERE payment_id=? OR order_id=? FOR UPDATE'
        );
        $logStmt->execute(array($paymentId, $paymentId));
        $logRow = $logStmt->fetch();

        // Already claimed → abort
        if ($logRow && (int)$logRow['points_awarded'] > 0 && $logRow['customer_id'] !== null) {
            $db->rollBack();
            json_error('Receipt này đã được dùng để nhận điểm rồi.');
        }

        // Resolve canonical payment_id (log may store real payment_id even if we searched by order_id)
        $resolvedPaymentId = $logRow ? $logRow['payment_id'] : $paymentId;

        if ($logRow && (int)$logRow['amount_cents'] > 0) {
            // Amount cached in our logs — use it directly
            $amountCents = (int)$logRow['amount_cents'];
            $merchantId  = $logRow['merchant_id'] ?: $mId;
        } else {
            // Fetch from Clover API
            if (!$accessToken || !$mId) {
                $db->rollBack();
                json_error('Hệ thống chưa kết nối Clover. Vui lòng liên hệ nhân viên.');
            }
            $base    = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
            $headers = array('Authorization: Bearer ' . $accessToken, 'Accept: application/json');

            // ① Try as Payment ID
            $ch = curl_init($base . "/v3/merchants/{$mId}/payments/{$paymentId}");
            curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_HTTPHEADER=>$headers));
            $apiBody = curl_exec($ch);
            $apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($apiCode === 200 && $apiBody) {
                $payment     = json_decode($apiBody, true);
                $amountCents = (int)(isset($payment['amount']) ? $payment['amount'] : 0);
                $resolvedPaymentId = $paymentId;
            } else {
                // ② Fallback: treat scanned value as Order ID
                $ch = curl_init($base . "/v3/merchants/{$mId}/orders/{$paymentId}/payments");
                curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_HTTPHEADER=>$headers));
                $apiBody = curl_exec($ch);
                $apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($apiCode === 200 && $apiBody) {
                    $elements = json_decode($apiBody, true);
                    $payments = isset($elements['elements']) ? $elements['elements'] : array();
                    foreach ($payments as $p) {
                        if ((int)(isset($p['amount']) ? $p['amount'] : 0) > 0) {
                            $amountCents       = (int)$p['amount'];
                            $resolvedPaymentId = isset($p['id']) ? $p['id'] : $paymentId;
                            break;
                        }
                    }
                }

                if ($amountCents <= 0) {
                    $db->rollBack();
                    json_error('Không tìm thấy thông tin thanh toán. Vui lòng liên hệ nhân viên.');
                }
            }
        }

        if ($amountCents <= 0) {
            $db->rollBack();
            json_error('Thanh toán có giá trị $0 hoặc không hợp lệ.');
        }

        $pts    = intdiv($amountCents, 100) * $ptsPerDollar;
        if ($pts <= 0) {
            $db->rollBack();
            json_error('Giá trị thanh toán quá nhỏ để nhận điểm (tối thiểu $1).');
        }

        // Lock customer row and award points
        $lockStmt = $db->prepare('SELECT points FROM customers WHERE id=? FOR UPDATE');
        $lockStmt->execute(array($customer['id']));
        $locked   = $lockStmt->fetch();
        $newPts   = (int)$locked['points'] + $pts;
        $tier     = tier_from_points($newPts);

        $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
           ->execute(array($newPts, $tier, $nowMs, $customer['id']));

        $db->prepare(
            'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
             VALUES (?,?,\'EARNED\',?,?,?)'
        )->execute(array(uuid4(), $customer['id'], $pts, 'Receipt claim: ' . $resolvedPaymentId, $nowMs));

        // Record in clover_payment_logs using the resolved payment_id
        if ($logRow) {
            $db->prepare(
                "UPDATE clover_payment_logs
                 SET customer_id=?,phone=?,amount_cents=?,points_awarded=?,status='processed',note='Website receipt claim'
                 WHERE payment_id=?"
            )->execute(array($customer['id'], $phone, $amountCents, $pts, $logRow['payment_id']));
        } else {
            $db->prepare(
                "INSERT INTO clover_payment_logs
                 (payment_id,merchant_id,order_id,customer_id,phone,amount_cents,points_awarded,status,note,created_at)
                 VALUES (?,?,?,?,?,?,?,'processed','Website receipt claim',?)"
            )->execute(array(
                $resolvedPaymentId, $merchantId,
                ($resolvedPaymentId !== $paymentId) ? $paymentId : null,  // store original scan as order_id if different
                $customer['id'], $phone, $amountCents, $pts, $nowMs
            ));
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Không thể cộng điểm. Vui lòng thử lại.', 500);
    }

    json_success(array(
        'points_added' => $pts,
        'new_points'   => $newPts,
        'tier'         => $tier,
        'amount'       => number_format($amountCents / 100, 2),
    ));
}

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
