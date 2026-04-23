<?php
header('Content-Type: application/json; charset=UTF-8');

$configFile = __DIR__ . '/../data/config.json';

// Nếu file chưa tồn tại → tạo mặc định
if (!file_exists($configFile)) {
    $defaultConfig = [
        "maintenanceMode" => false,
        "heroBackground" => ""
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$data = json_decode(file_get_contents($configFile), true);

// Nếu JSON hỏng → reset
if (!$data) {
    $data = [
        "maintenanceMode" => false,
        "heroBackground" => ""
    ];
}

echo json_encode([
    "success" => true,
    "data" => $data,
    "lastUpdated" => date("Y-m-d H:i:s")
], JSON_UNESCAPED_UNICODE);
