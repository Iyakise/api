StudentPay API Documentation

Base URL:

https://api.flobby.org


All requests should include:

Content-Type: application/json


JWT-protected endpoints also require:

Authorization: Bearer <JWT_TOKEN>

1️⃣ Signup Endpoint

URL: /auth/signup/
Method: POST
Description: Create a new user account and send a verification email.

Request Body (JSON)
{
  "fullname": "John Doe",
  "email": "john@example.com",
  "phone": "08123456789",
  "password": "secret123"
}

Response (Success, 201)
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "fullname": "John Doe",
    "email": "john@example.com",
    "phone": "8123456789",
    "account_number": "81234567890",
    "Email_sent": true
  }
}

Response (Errors)
HTTP Code	Message
400	All fields are required / Invalid email / Password too short / Invalid phone
409	Email or phone number already registered
500	Registration failed

Notes:

Phone number is normalized (removes leading zero)

Account number is auto-generated (11-digit starting with 8)

Sends email verification link

2️⃣ Login Endpoint

URL: /auth/login/
Method: POST
Description: Login user, check if email is verified, returns JWT token.

Request Body (JSON)
{
  "email": "john@example.com",
  "password": "secret123"
}

Response (Success)
{
  "success": true,
  "message": "Login successful",
  "token": "<JWT_TOKEN>"
}

Response (Email Not Verified)
{
  "success": false,
  "message": "Email not verified. Verification email resent."
}

Response (Errors)
HTTP Code	Message
400	Missing email or password / Invalid email format
401	Invalid credentials
403	Email not verified

Notes:

JWT token expires after 1 hour (JWT_EXPIRY)

Store token in LocalStorage or HttpOnly cookie

Use token to access protected endpoints

3️⃣ Email Verification Endpoint

URL: /auth/verify-email.php
Method: GET
Description: Verify user email using the token sent in the verification email.

Query Parameter
code=<verification_token>

Response (Success)
{
  "success": true,
  "message": "Email verified successfully. You can now login."
}

Response (Errors)
HTTP Code	Message
400	Invalid token
410	Token expired
404	User not found

Notes:

Token expires after a set period (e.g., 24 hours)

After verification, user can login and receive JWT

4️⃣ Check Login / JWT Validation Endpoint

URL: /isAuthenticated.php
Method: GET
Description: Verify if the user is logged in by validating JWT token.

Request Headers
Authorization: Bearer <JWT_TOKEN>

Response (Success)
{
  "success": true,
  "message": "User is logged in",
  "data": {
    "user_id": 12,
    "email": "john@example.com"
  }
}

Response (Errors)
HTTP Code	Message
401	Authorization token missing / Invalid or expired token

Notes:

Used by frontend to maintain session state

Must include token in Authorization header

5️⃣ Wallet Endpoints (Example)
Check Balance

URL: /wallet/balance.php
Method: GET
Headers:

Authorization: Bearer <JWT_TOKEN>


Response Example

{
  "success": true,
  "balance": 5000
}

Transfer to Another User

URL: /wallet/transfer.php
Method: POST
Headers:

Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json

Request Body
{
  "to_phone": "8123456789",
  "amount": 1000
}

Response (Success)
{
  "success": true,
  "message": "Transfer successful",
  "data": {
    "from_account": "81234567890",
    "to_account": "81234567891",
    "amount": 1000,
    "timestamp": "2026-01-18 14:55:22"
  }
}

Response (Errors)
HTTP Code	Message
400	Missing parameters / Invalid phone number / Insufficient balance
401	Invalid or expired token

Notes:

Transfer uses receiver’s phone number without leading zero

Only logged-in, verified users can transfer

General Notes for Students

Use Content-Type: application/json for POST requests

Store JWT in LocalStorage (easy for students) or HttpOnly cookies

Include JWT in Authorization header for protected endpoints

Handle token expiry: redirect to login if 401 Unauthorized

Account number is auto-generated; phone is normalized

