<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

require_once "api-key.php";

if (!defined('XPERT_TO_USER')
    || !defined('XPERT_TO_USERNAME')
    || !defined('XPERT_FROM_NAME')
    || !defined('XPERT_EMAIL_SUBJECT')
    || !defined('XPERT_MAIL_CONTENT_PLAN')
    || !defined('XPERT_MAIL_CONTENT_HTML')
    || !defined('SENDER_EMAIL_ADDRESS')
    || !defined('SMTP_HOST')
    || !defined('SMTP_USERNAME')
    || !defined('SMTP_PASSWORD')
    || !defined('SMTP_PORT')) {

    echo json_encode(['error' => 'Missing required environment variables']);
    http_response_code(500);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL/TLS
    $mail->Port       = 465; // Use 465 for SSL, 587 for TLS
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    // Recipients
    $mail->setFrom(SENDER_EMAIL_ADDRESS, XPERT_FROM_NAME);
    $mail->addAddress(XPERT_TO_USER, XPERT_TO_USERNAME);

    // Content
    $mail->isHTML(true);
    $mail->Subject = XPERT_EMAIL_SUBJECT;
    $mail->Body    = XPERT_MAIL_CONTENT_HTML;
    $mail->AltBody = XPERT_MAIL_CONTENT_PLAN;

    $mail->send();

    if (!defined('__SHOW_SUCCESS__')) {
        echo json_encode(['message' => 'Email sent successfully.']);
        http_response_code(201);
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => "Mailer Error: {$mail->ErrorInfo}"]);

    // Log failure
    file_put_contents('email_log.log', print_r([
        'type'      => 'PHPMAILER FAILURE',
        'error'     => $mail->ErrorInfo,
        'Exception' => $e->getMessage(),
        'http_code' => http_response_code(),
    ], true), FILE_APPEND);
}


?>



