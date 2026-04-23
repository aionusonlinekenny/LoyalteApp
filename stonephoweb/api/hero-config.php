<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/hero-config.json';

// Đọc JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($input['background'])) {
        echo json_encode(['success' => false, 'error' => 'Missing background']);
        exit;
    }

    $heroBackground = $input['background'];

    $data = [
        'heroBackground' => $heroBackground, // ✅ dùng đúng key Hero.tsx đang cần
        'lastUpdated' => date('Y-m-d H:i:s')
    ];

    if (file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to write file']);
    }
    exit;
}

// Nếu là GET thì load ra
if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'error' => 'Config not found']);
}
