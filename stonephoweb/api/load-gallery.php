<?php
header('Content-Type: application/json; charset=UTF-8');
$galleryFile = __DIR__ . '/../data/gallery.json';
if (!file_exists($galleryFile)) {
    echo json_encode([
        "success" => false,
        "error" => "Gallery file not found"
    ]);
    exit;
}
$data = json_decode(file_get_contents($galleryFile), true);
if (!$data || !isset($data['galleryItems'])) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid gallery JSON format"
    ]);
    exit;
}
echo json_encode([
    "success" => true,
    "data" => [
        "galleryItems" => $data['galleryItems']
    ]
], JSON_UNESCAPED_UNICODE);
