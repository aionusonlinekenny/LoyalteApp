<?php
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['name']) || empty($data['date']) || empty($data['time']) || empty($data['people'])) {
    echo json_encode(["success" => false, "message" => "Missing reservation data"]);
    exit;
}

// TODO: Save reservation somewhere (DB or JSON file)

echo json_encode(["success" => true, "message" => "Reservation received"]);
