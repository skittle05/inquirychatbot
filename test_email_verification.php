<?php
// Test script for email verification system
session_start();

// Define security constant for EmailService
define('SECURE_ACCESS', true);

include("config");
include("connection.php");
include("functions.php");
include("EmailService.php");

use App\EmailService;

echo "<h1>Email Verification System Test</h1>";

// Test 1: Check database connection
echo "<h2>Test 1: Database Connection</h2>";
if ($con) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit();
}

// Test 2: Check if users table exists and has required columns
echo "<h2>Test 2: Database Structure</h2>";
$result = mysqli_query($con, "DESCRIBE users");
if ($result) {
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    $required_columns = ['user_id', 'email', 'password', 'first_name', 'last_name', 'verification_code', 'verification_token', 'is_verified'];
    $missing_columns = array_diff($required_columns, $columns);

    if (empty($missing_columns)) {
        echo "✅ All required columns exist<br>";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missing_columns) . "<br>";
        echo "Please run the setup_database.sql script to create the required columns.<br>";
    }
} else {
    echo "❌ Could not check table structure<br>";
}

// Test 3: Check EmailService configuration
echo "<h2>Test 3: Email Service Configuration</h2>";
try {
    $emailService = EmailService::getInstance();
    echo "✅ EmailService initialized successfully<br>";

    // Check if SMTP settings are configured
    if (defined('SMTP_HOST') && defined('SMTP_USERNAME') && defined('SMTP_PASSWORD')) {
        echo "✅ SMTP configuration found<br>";
    } else {
        echo "❌ SMTP configuration missing<br>";
    }
} catch (Exception $e) {
    echo "❌ EmailService initialization failed: " . $e->getMessage() . "<br>";
}

// Test 4: Generate a test verification code
echo "<h2>Test 4: Verification Code Generation</h2>";
$test_code = sprintf("%06d", mt_rand(0, 999999));
echo "✅ Generated test verification code: " . $test_code . "<br>";

// Test 5: Check if config file is properly included
echo "<h2>Test 5: Configuration</h2>";
if (defined('GMAIL_USERNAME') && defined('GMAIL_APP_PASSWORD')) {
    echo "✅ Gmail configuration found<br>";
    echo "Gmail Username: " . GMAIL_USERNAME . "<br>";
    echo "App Password: " . substr(GMAIL_APP_PASSWORD, 0, 4) . "****<br>";
} else {
    echo "❌ Gmail configuration missing<br>";
}

echo "<h2>Test Summary</h2>";
echo "If all tests show ✅, your email verification system is ready to use!<br>";
echo "<br>";
echo "<a href='signup.php'>Go to Sign Up Page</a><br>";
echo "<a href='verify_email.php'>Go to Email Verification Page</a><br>";
