<?php
header('Content-Type: application/json; charset=utf-8');
$file = __DIR__ . '/../data/location.json';
if (!file_exists($file)) {
    $sample = [
        "address" => "1525 Baytree Rd, Valdosta, GA 31602",
        "mapEmbedUrl" => "https://www.google.com/maps/embed?...StonePho..."
    ];
    file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
echo file_get_contents($file);
