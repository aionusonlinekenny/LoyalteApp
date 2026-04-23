<?php
header('Content-Type: application/json; charset=UTF-8');

$configFile = __DIR__ . '/../data/config.json';

// Lấy dữ liệu JSON từ request
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

try {
    // Đảm bảo thư mục tồn tại
    $dataDir = dirname($configFile);
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    // Đọc config cũ
    $oldConfig = [];
    if (file_exists($configFile)) {
        $oldConfig = json_decode(file_get_contents($configFile), true);
        if (!$oldConfig) $oldConfig = [];
    }

    // Gộp dữ liệu mới vào config cũ
    $newConfig = array_merge($oldConfig, $data);

    // Ghi file
    file_put_contents(
        $configFile,
        json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode([
        "success" => true,
        "message" => "Config saved successfully",
        "lastUpdated" => date("Y-m-d H:i:s")
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
