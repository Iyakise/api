<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

// header("Access-Control-Allow-Origin: http://localhost:63342");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST, OPTIONS");
// header("Content-Type: application/json");

// // Handle preflight
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

require_once dirname(__DIR__) . "/inc/db.php";
require_once dirname(__DIR__) . "/inc/function.php";

    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);


// ---------------- HELPER FUNCTIONS ----------------

function normalizePhone($phone)
{
    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) < 10) {
        return false;
    }

    if (str_starts_with($phone, '0')) {
        $phone = substr($phone, 1);
    }

    return $phone;
}

function generateAccountNumber($pdo)
{
    do {
        $account = '8' . random_int(1000000000, 9999999999);

        $check = $pdo->prepare(
            "SELECT id FROM accounts WHERE account_number = :acc LIMIT 1"
        );
        $check->execute(['acc' => $account]);

    } while ($check->fetch());

    return $account;
}


// ---------------- SIGNUP LOGIC ----------------

try {

    $input = json_decode(file_get_contents("php://input"), true);

    if (
        empty($input['fullname']) ||
        empty($input['email']) ||
        empty($input['phone']) ||
        empty($input['password'])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "All fields are required"
        ]);
        exit;
    }

    $fullname = trim($input['fullname']);
    $email    = strtolower(trim($input['email']));
    $phone    = trim($input['phone']);
    $password = $input['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email address"
        ]);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least 6 characters"
        ]);
        exit;
    }

    $phoneNormalized = normalizePhone($phone);
    if (!$phoneNormalized) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid phone number"
        ]);
        exit;
    }

    // Check duplicate email or phone
    $check = $pdo->prepare(
        "SELECT id FROM users 
         WHERE email = :email 
         OR phone_normalized = :phone 
         LIMIT 1"
    );
    $check->execute([
        'email' => $email,
        'phone' => $phoneNormalized
    ]);

    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Email or phone number already registered"
        ]);
        exit;
    }

    // ---------------- DB TRANSACTION ----------------
    $pdo->beginTransaction();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare(
        "INSERT INTO users 
        (fullname, email, phone, phone_normalized, password)
        VALUES (:fullname, :email, :phone, :phone_normalized, :password)"
    );
    $stmt->execute([
        'fullname'         => $fullname,
        'email'            => $email,
        'phone'            => $phone,
        'phone_normalized' => $phoneNormalized,
        'password'         => $hashedPassword
    ]);

    $userId = $pdo->lastInsertId();

    // Generate account number
    $accountNumber = generateAccountNumber($pdo);

    // Create account
    $acct = $pdo->prepare(
        "INSERT INTO accounts (user_id, account_number)
         VALUES (:user_id, :account_number)"
    );
    $acct->execute([
        'user_id'        => $userId,
        'account_number' => $accountNumber
    ]);

    $pdo->commit();


	// Generate email verification token
	$verificationToken = generateVerificationToken();
	$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

	// Store verification token
	$verifyStmt = $pdo->prepare(
	    "INSERT INTO email_verifications (user_id, token, expires_at)
	     VALUES (:user_id, :token, :expires_at)"
	);
	$verifyStmt->execute([
	    'user_id'    => $userId,
	    'token'      => $verificationToken,
	    'expires_at' => $expiresAt
	]);







    //send mail here
    $appName = __SITE_NAME__;
    $root    = __BASE_URL__;
    $verificationLink = $root  . "verify.html?code=" .$verificationToken;
    $subject = "Welcome to StudentPay – Please Verify Your Email";

    $body = <<<EMAIL
<div style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="background:#f4f4f4;padding:15px;text-align:center;">
        <h2>{$appName}</h2>
        <img src="{$root}auth/inc/logo.png" height="50">
    </div>

	<p>Hello {$fullname},</p>

	<p>Welcome to StudentPay 🎉</p>
	<p>We’re excited to have you on board.</p>

	<p>Your account has been successfully created, and you can now enjoy fast, secure, and seamless wallet transactions — just like a modern digital bank.</p>

	<p>🔐 One More Step: VeNew!Click to editrify Your Email</p>

	<p>To keep your account safe and activate all features, please confirm your email address by clicking the button below:</p>

	<p>👉 Verify My Email
	{$verificationLink}</p>

	<p>If the button doesn’t work, copy and paste this link into your browser:
	{$verificationLink}</p>

	<p>💡 Why Email Verification Matters</p>

	<p>Protects your account from unauthorized access</p>

	<p>Ensures you receive important transaction alerts</p>

	<p>Enables full access to transfers and wallet features</p>

	<p>If you did not create this account, please ignore this email — no action will be taken.</p>

	<p>Welcome once again to StudentPay.</p>
	<p>We’re glad to have you with us 🚀</p>

	<p>Warm regards,<br>
	The StudentPay Team<br>
	Secure • Simple • Smart Payments</p>

</div>
EMAIL;

    $emailSent = sendEmail(
        $email,
        $fullname,
        $appName,
        $subject,
        strip_tags($body),
        $body
    );

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully",
        "data" => [
            "fullname"       => $fullname,
            "email"          => $email,
            "phone"          => $phoneNormalized,
            "account_number" => $accountNumber,
            "Email_sent"     => $emailSent
        ]
    ]);

} catch (Throwable $e) {
	error_log($e);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration failed " . $e
        // For debugging only:
        // "error" => $e->getMessage()
    ]);
}
