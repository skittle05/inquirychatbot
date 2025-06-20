<?php

namespace App;

// Prevent direct access to this file
if (!defined('SECURE_ACCESS')) {
    exit('Direct access to this file is not allowed.');
}

// Include PHPMailer classes
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Define security constant before including config
require_once __DIR__ . '/config.php';

class EmailService
{
    private static $instance = null;
    private $mailer;
    private $debug_output = '';
    private $last_error = null;

    private function __construct()
    {
        $this->initializeMailer();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
            $this->mailer->Debugoutput = function ($str, $level) {
                $this->debug_output .= date('Y-m-d H:i:s') . " [Level $level] " . trim($str) . "\n";
            };

            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->Timeout = 30; // Set timeout to 30 seconds

            // Set default charset and encoding
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';

            // Default sender
            $this->mailer->setFrom(SMTP_USERNAME, 'Gordon College Chatbot');
            $this->mailer->isHTML(true);

            // Log successful initialization
            error_log("EmailService initialized successfully with SMTP host: " . SMTP_HOST);
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            error_log("Mailer initialization error: " . $e->getMessage());
            throw new Exception("Email service initialization failed: " . $e->getMessage());
        }
    }

    public function sendVerificationEmail($to_email, $user_name, $verification_code)
    {
        try {
            $this->debug_output = ''; // Clear debug output
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = 'Email Verification - Gordon College';

            // HTML email body
            $html_body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background-color: #006400; padding: 20px; text-align: center;">
                    <img src="' . SITE_URL . '/images/logo.png" alt="Gordon College Logo" style="max-width: 100px;">
                    <h1 style="color: white; margin-top: 10px;">Email Verification</h1>
                </div>
                <div style="padding: 20px; background-color: #f9f9f9;">
                    <p>Dear ' . htmlspecialchars($user_name) . ',</p>
                    <p>Thank you for registering with Gordon College. To complete your registration, please use this verification code:</p>
                    <div style="background-color: #eee; padding: 15px; text-align: center; margin: 20px 0;">
                        <h2 style="color: #006400; letter-spacing: 5px; margin: 0;">' . $verification_code . '</h2>
                    </div>
                    <p>This code will expire in 24 hours. If you did not create an account, please ignore this email.</p>
                    <p>For security reasons, please do not share this code with anyone.</p>
                    <hr style="border: 1px solid #eee; margin: 20px 0;">
                    <p style="font-size: 12px; color: #666;">
                        This is an automated message, please do not reply to this email.<br>
                        If you need assistance, please contact our support team.
                    </p>
                </div>
                <div style="background-color: #006400; color: white; text-align: center; padding: 10px; font-size: 12px;">
                    &copy; ' . date('Y') . ' Gordon College. All rights reserved.
                </div>
            </div>';

            // Plain text version
            $text_body = "Dear " . $user_name . ",\n\n" .
                "Thank you for registering with Gordon College. To complete your registration, please use this verification code:\n\n" .
                $verification_code . "\n\n" .
                "This code will expire in 24 hours. If you did not create an account, please ignore this email.\n" .
                "For security reasons, please do not share this code with anyone.\n\n" .
                "This is an automated message, please do not reply to this email.\n" .
                "If you need assistance, please contact our support team.\n\n" .
                "© " . date('Y') . " Gordon College. All rights reserved.";

            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;

            $success = $this->mailer->send();

            if (!$success) {
                error_log("Email sending failed. Debug output: " . $this->debug_output);
                throw new Exception("Failed to send email. SMTP Debug: " . $this->debug_output);
            }

            return true;
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage() . "\nDebug output: " . $this->debug_output);
            throw new Exception("Failed to send verification email. Please check your email configuration. Error: " . $e->getMessage());
        }
    }

    public function sendPasswordResetEmail($to_email, $user_name, $verification_code)
    {
        try {
            // Clear previous state
            $this->debug_output = '';
            $this->last_error = null;
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Log attempt
            error_log("Attempting to send password reset email to: " . $to_email);

            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = 'Password Reset Code - Gordon College Chatbot';

            // HTML email body
            $html_body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background-color: #006400; padding: 20px; text-align: center;">
                    <h1 style="color: white; margin: 0;">Password Reset</h1>
                </div>
                <div style="padding: 20px; background-color: #f9f9f9;">
                    <p>Dear ' . htmlspecialchars($user_name) . ',</p>
                    <p>You have requested to reset your password for your Gordon College Chatbot account.</p>
                    <p>Your verification code is:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <div style="background-color: #eee; padding: 15px; text-align: center; margin: 20px 0;">
                            <h2 style="color: #006400; letter-spacing: 5px; margin: 0; font-size: 32px;">' . $verification_code . '</h2>
                        </div>
                    </div>
                    <p>This code will expire in ' . (PASSWORD_RESET_EXPIRY / 60) . ' minutes.</p>
                    <p>If you did not request this password reset, please ignore this email.</p>
                    <p>For security reasons, please do not share this code with anyone.</p>
                    <hr style="border: 1px solid #eee; margin: 20px 0;">
                    <p style="font-size: 12px; color: #666;">
                        This is an automated message, please do not reply to this email.<br>
                        If you need assistance, please contact our support team.
                    </p>
                </div>
                <div style="background-color: #006400; color: white; text-align: center; padding: 10px; font-size: 12px;">
                    &copy; ' . date('Y') . ' Gordon College Chatbot. All rights reserved.
                </div>
            </div>';

            // Plain text version
            $text_body = "Dear " . $user_name . ",\n\n" .
                "You have requested to reset your password for your Gordon College Chatbot account.\n\n" .
                "Your verification code is: " . $verification_code . "\n\n" .
                "This code will expire in " . (PASSWORD_RESET_EXPIRY / 60) . " minutes.\n\n" .
                "If you did not request this password reset, please ignore this email.\n" .
                "For security reasons, please do not share this code with anyone.\n\n" .
                "This is an automated message, please do not reply to this email.\n" .
                "If you need assistance, please contact our support team.\n\n" .
                "© " . date('Y') . " Gordon College Chatbot. All rights reserved.";

            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;

            // Attempt to send
            $success = $this->mailer->send();

            if (!$success) {
                $this->last_error = $this->mailer->ErrorInfo;
                error_log("Failed to send email. SMTP Debug Output:\n" . $this->debug_output);
                throw new Exception("Failed to send email: " . $this->last_error);
            }

            // Log success
            error_log("Successfully sent password reset email to: " . $to_email);
            return true;
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            error_log("Failed to send password reset email: " . $e->getMessage() . "\nDebug output:\n" . $this->debug_output);
            throw new Exception("Failed to send password reset email. Please try again later.");
        }
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    public function getDebugOutput()
    {
        return $this->debug_output;
    }
}
