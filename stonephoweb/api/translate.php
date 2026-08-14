<?php
require_once __DIR__ . '/common.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$langpair = isset($_GET['langpair']) ? trim($_GET['langpair']) : 'en|es';

if ($q === '') api_err('Missing query', 400);

// Allow only simple langpair values to prevent injection
if (!preg_match('/^[a-z]{2}\|[a-z]{2}$/', $langpair)) api_err('Invalid langpair', 400);

$url = 'https://api.mymemory.translated.net/get?q=' . urlencode($q) . '&langpair=' . urlencode($langpair);

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 8,
        'header' => "User-Agent: StonePhoWebAdmin/1.0\r\n",
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$raw = @file_get_contents($url, false, $ctx);
if ($raw === false) api_err('Translation service unavailable', 502);

$json = json_decode($raw, true);
if (!is_array($json)) api_err('Invalid response from translation service', 502);

api_ok($json);
