<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
$file = __DIR__ . "/../data/menu-categories.json";
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data["categories"])) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit;
}

$categories = $data["categories"];

file_put_contents($file, json_encode($categories, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo json_encode(["success" => true, "message" => "Categories saved successfully"]);