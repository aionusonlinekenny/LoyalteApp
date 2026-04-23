<?php
header("Content-Type: application/json; charset=UTF-8");

// File path
$galleryFile = __DIR__ . "/../data/gallery.json";

// Read raw input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Validate input
if (!$data || !isset($data["galleryItems"])) {
    echo json_encode(["success" => false, "error" => "Invalid input data"]);
    exit;
}

try {
    // Ensure data folder exists
    $dataDir = dirname($galleryFile);
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    // Save gallery items
    file_put_contents(
        $galleryFile,
        json_encode(["galleryItems" => $data["galleryItems"]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode([
        "success" => true,
        "message" => "Gallery saved successfully",
        "lastUpdated" => date("Y-m-d H:i:s")
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
