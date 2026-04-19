<?php
require_once __DIR__ . '/auth.php';
logout_customer();
header('Location: index.php');
exit;
