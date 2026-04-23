<?php
header('Content-Type: application/json; charset=utf-8');
$file = __DIR__ . '/../data/about.json';

if (!file_exists($file)) {
    $sample = [
        "content" => "Chao mung ban.",
        "lastUpdated" => date('Y-m-d H:i:s')
    ];
    file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
echo file_get_contents($file);
