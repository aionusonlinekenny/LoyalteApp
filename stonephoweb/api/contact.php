<?php
header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['name']) || empty($data['email']) || empty($data['message'])) {
    echo json_encode(["success" => false, "message" => "Missing contact data"]);
    exit;
}
echo json_encode(["success" => true, "message" => "Message sent successfully"]);
