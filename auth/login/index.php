<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__) . "/inc/db.php";
require_once dirname(__DIR__) . "/inc/function.php"; // sendEmail()
require_once dirname(__DIR__) . "/inc/jwt.php";


try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $data = json_decode(file_get_contents("php://input"), true);

    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        throw new Exception("Email and password are required");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    // Fetch user
    $stmt = $pdo->prepare(
        "SELECT id, fullname, email, password, verified
         FROM users
         WHERE email = :email
         LIMIT 1"
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception("Invalid login credentials");
    }

    // 🚫 Email not verified → resend verification
    if (!$user['verified']) {

        // Invalidate old tokens
        $pdo->prepare(
            "DELETE FROM email_verifications WHERE user_id = :uid"
        )->execute(['uid' => $user['id']]);

        // Generate new token
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Save token
        $pdo->prepare(
            "INSERT INTO email_verifications (user_id, token, expires_at)
             VALUES (:uid, :token, :expires)"
        )->execute([
            'uid'     => $user['id'],
            'token'   => $token,
            'expires' => $expiresAt
        ]);

            $appName = __SITE_NAME__;
    		$root    = __BASE_URL__;

        $verificationLink = __BASE_URL__ . "/verify.html?code=" . $token;

        // Prepare email
        $subject = "Verify Your StudentPay Account";

        $body = file_get_contents(dirname(__DIR__) . "/emails/verify_email.html");
        $body = str_replace(
            ['{{fullname}}', '{{verification_link}}'],
            [$user['fullname'], $verificationLink],
            $body
        );

        // sendEmail($user['email'], $subject, $body);
$emailSent = sendEmail(
        $user['email'],
        $user['fullname'],
        $appName,
        $subject,
        strip_tags($body),
        $body
    );

        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Your email is not verified. A new verification link has been sent to your email.",
            "email_sent" => $emailSent
        ]);
        exit;
    }

    // ✅ LOGIN SUCCESS
    $token = generateJWT([
	    'user_id' => $user['id'],
	    'email'   => $user['email']
	]);

	echo json_encode([
	    "success" => true,
	    "message" => "Login successful",
	    "token"   => $token
	]);


} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
