<?php
// Temporary file — DELETE after webhook is verified
$file = __DIR__ . '/clover_verify.txt';
header('Content-Type: text/plain');
if (file_exists($file)) {
    echo file_get_contents($file);
} else {
    echo 'No verification code received yet. Click Send Verification Code in Clover first.';
}
