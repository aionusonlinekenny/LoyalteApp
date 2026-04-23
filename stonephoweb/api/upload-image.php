<?php
// CORS headers
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");
// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Upload directory
$uploadDir = __DIR__ . '/../uploads/menu/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if (!isset($_FILES['image'])) {
    echo json_encode([
        "success" => false,
        "error" => "No file uploaded"
    ]);
    exit;
}

$file = $_FILES['image'];

// Validate upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "error" => "Upload error code: " . $file['error']
    ]);
    exit;
}

// Validate type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid file type"
    ]);
    exit;
}

// Validate size (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode([
        "success" => false,
        "error" => "File too large (max 10MB)"
    ]);
    exit;
}

// Generate unique name
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . strtolower($ext);
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to save file"
    ]);
    exit;
}

// File URL
$imageUrl = "/uploads/menu/" . $filename;

echo json_encode([
    "success" => true,
    "imageUrl" => $imageUrl
]);
