<?php
header("Content-Type: application/json");

require_once dirname(__DIR__) . "/inc/db.php";

$pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$token = $_GET['code'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid verification token"
    ]);
    exit;
}

// Fetch token
$stmt = $pdo->prepare(
    "SELECT ev.*, u.email_verified
     FROM email_verifications ev
     JOIN users u ON u.id = ev.user_id
     WHERE ev.token = :token
     LIMIT 1"
);
$stmt->execute(['token' => $token]);
$record = $stmt->fetch();

if (!$record) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Verification link not found"
    ]);
    exit;
}

// Already verified
if ($record['verified_at']) {
    echo json_encode([
        "success" => true,
        "message" => "Email already verified"
    ]);
    exit;
}

// Expired token
if (strtotime($record['expires_at']) < time()) {
    http_response_code(410);
    echo json_encode([
        "success" => false,
        "message" => "Verification link has expired"
    ]);
    exit;
}

$pdo->beginTransaction();

// Mark email as verified
$pdo->prepare(
    "UPDATE users SET verified = 1 WHERE id = :id"
)->execute(['id' => $record['user_id']]);

// Mark token as used
$pdo->prepare(
    "UPDATE email_verifications 
     SET verified_at = NOW() 
     WHERE id = :id"
)->execute(['id' => $record['id']]);

$pdo->commit();

echo json_encode([
    "success" => true,
    "message" => "Email verified successfully"
]);
