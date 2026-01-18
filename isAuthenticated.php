<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET");

require_once dirname(__DIR__) . "/auth/auth.php"; // this checks the JWT

// If auth.php passed, $authUser contains the payload
echo json_encode([
    "success" => true,
    "message" => "User is logged in",
    "data" => [
        "user_id" => $authUser['user_id'],
        "email"   => $authUser['email']
    ]
]);
