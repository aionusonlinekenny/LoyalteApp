<?php
header("Content-Type: application/json; charset=UTF-8");

// File paths
$itemsFile = __DIR__ . "/../data/menu-items.json";
$categoriesFile = __DIR__ . "/../data/menu-categories.json";

// Read raw input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Validate input
if (!$data || !isset($data["items"]) || !isset($data["categories"])) {
    echo json_encode(["success" => false, "error" => "Invalid input data"]);
    exit;
}

try {
    // Ensure data folder exists
    $dataDir = dirname($itemsFile);
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    // Save menu items
    file_put_contents(
        $itemsFile,
        json_encode(["items" => $data["items"]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    // Save categories
    file_put_contents(
        $categoriesFile,
        json_encode(["categories" => $data["categories"]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode([
        "success" => true,
        "message" => "Menu saved successfully",
        "lastUpdated" => date("Y-m-d H:i:s")
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
