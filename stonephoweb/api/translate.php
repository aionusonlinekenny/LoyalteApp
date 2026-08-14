<?php
require_once __DIR__ . '/common.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$langpair = isset($_GET['langpair']) ? trim($_GET['langpair']) : 'en|es';

if ($q === '') api_err('Missing query', 400);

if (!preg_match('/^[a-z]{2}\|[a-z]{2}$/', $langpair)) api_err('Invalid langpair', 400);

$url = 'https://api.mymemory.translated.net/get?q=' . urlencode($q) . '&langpair=' . urlencode($langpair);

if (!function_exists('curl_init')) api_err('curl not available on this server', 500);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_USERAGENT      => 'StonePhoWebAdmin/1.0',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
]);

$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($raw === false || $curlErr) api_err('Translation service unavailable: ' . $curlErr, 502);
if ($httpCode !== 200) api_err('Translation service returned ' . $httpCode, 502);

$json = json_decode($raw, true);
if (!is_array($json)) api_err('Invalid response from translation service', 502);

api_ok($json);
