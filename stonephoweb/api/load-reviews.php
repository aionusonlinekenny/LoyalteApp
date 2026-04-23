<?php
header("Content-Type: application/json; charset=UTF-8");

$file = __DIR__ . "/../data/reviews.json";

if (!file_exists($file)) {
    echo json_encode(["success" => false, "error" => "reviews.json not found"]);
    exit;
}

$data = json_decode(file_get_contents($file), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);
