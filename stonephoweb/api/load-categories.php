<?php
header('Content-Type: application/json');
$categories = ["appetizers", "pho", "vermicelli", "specialty", "rice", "beverages"];
echo json_encode([
    "success" => true,
    "data" => [ "categories" => $categories ]
], JSON_UNESCAPED_UNICODE);