<?php

define('JWT_SECRET', 'STUDENTPAY_SUPER_SECRET_2026'); // change in production
define('JWT_EXPIRY', 3600); // 1 hour

function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT(array $payload)
{
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;

    $base64Header  = base64UrlEncode(json_encode($header));
    $base64Payload = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac(
        'sha256',
        $base64Header . '.' . $base64Payload,
        JWT_SECRET,
        true
    );

    $base64Signature = base64UrlEncode($signature);

    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

function verifyJWT($token)
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    [$base64Header, $base64Payload, $base64Signature] = $parts;

    $signature = base64UrlEncode(
        hash_hmac(
            'sha256',
            $base64Header . '.' . $base64Payload,
            JWT_SECRET,
            true
        )
    );

    if (!hash_equals($signature, $base64Signature)) {
        return false;
    }

    $payload = json_decode(base64UrlDecode($base64Payload), true);

    if (!$payload || $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}
