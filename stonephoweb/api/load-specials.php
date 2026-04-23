<?php
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/../data/specials.json';

if (!file_exists($file)) {
    $sample = [
        [
            "id" => "bun-thit-nuong",
            "name" => "Bún Th?t Nu?ng",
            "price" => 11.49,
            "description" => "Grilled pork vermicelli",
            "imageUrl" => "/uploads/bun-thit-nuong.jpg"
        ]
    ];
    file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

echo file_get_contents($file);
