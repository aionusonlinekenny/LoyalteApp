<?php
header('Content-Type: application/json; charset=utf-8');
$file = __DIR__ . '/../data/events.json';

if (!file_exists($file)) {
    $sample = [
        [
            "id" => "event1",
            "title" => "Grand Opening",
            "date" => "2025-09-01",
            "description" => "Join us for our grand opening celebration!"
        ]
    ];
    file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
echo file_get_contents($file);
