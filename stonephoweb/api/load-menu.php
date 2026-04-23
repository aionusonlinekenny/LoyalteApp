<?php
header('Content-Type: application/json');

$itemsFile = __DIR__ . '/../data/menu-items.json';
$categoriesFile = __DIR__ . '/../data/menu-categories.json';

$itemsData = json_decode(file_get_contents($itemsFile), true);
$categoriesData = json_decode(file_get_contents($categoriesFile), true);

if (!$itemsData || !$categoriesData) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON in menu files"
    ]);
    exit;
}

$items = isset($itemsData["items"]) ? $itemsData["items"] : $itemsData;
$categories = isset($categoriesData["categories"]) ? $categoriesData["categories"] : $categoriesData;

echo json_encode([
    "success" => true,
    "data" => [
        "menuItems" => $items,
        "categories" => $categories
    ],
    "lastUpdated" => date("Y-m-d H:i:s")
], JSON_UNESCAPED_UNICODE);
