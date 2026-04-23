<?php
header('Content-Type: application/json; charset=utf-8');
$file = __DIR__ . '/../data/hours.json';
if (!file_exists($file)) {
    $sample = [
        "days" => ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
        "open" => "09:00",
        "close" => "21:00"
    ];
    file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
echo file_get_contents($file);