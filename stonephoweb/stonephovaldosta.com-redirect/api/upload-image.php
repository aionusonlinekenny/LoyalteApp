<?php
// Ultra simple upload API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$uploadDir = '../uploads/menu-items/';

// Create directory if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['image'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error: ' . $file['error']]);
    exit();
}

// Generate filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'menu-item-' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode([
        'success' => true,
        'imageUrl' => '/uploads/menu-items/' . $filename,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['error' => 'Failed to save file']);
}
?>