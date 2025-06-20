<?php
session_start();
include("connection.php");
include("functions.php");

// Define security constant before including config
define('SECURE_ACCESS', true);
include("config.php");

// Include PHPMailer classes
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/EmailService.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\EmailService;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = mysqli_real_escape_string($con, $_POST['email']);

    if (!empty($email)) {
        try {
            // Check if email exists
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($con, $query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($con));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
            }

            $result = mysqli_stmt_get_result($stmt);
            if ($result === false) {
                throw new Exception("Failed to get result: " . mysqli_error($con));
            }

            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                // Generate reset code
                $reset_code = sprintf("%06d", mt_rand(100000, 999999)); // 6-digit code
                $expires_at = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);

                // Begin transaction
                mysqli_begin_transaction($con);

                try {
                    // Clear any existing reset codes first
                    $clear_query = "UPDATE users SET reset_code = NULL, reset_expires_at = NULL WHERE email = ?";
                    $clear_stmt = mysqli_prepare($con, $clear_query);
                    if ($clear_stmt === false) {
                        throw new Exception("Failed to prepare clear statement");
                    }
                    mysqli_stmt_bind_param($clear_stmt, "s", $email);
                    if (!mysqli_stmt_execute($clear_stmt)) {
                        throw new Exception("Failed to clear old reset codes");
                    }

                    // Save new reset code to database
                    $update_query = "UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE email = ?";
                    $update_stmt = mysqli_prepare($con, $update_query);
                    if ($update_stmt === false) {
                        throw new Exception("Failed to prepare update statement");
                    }

                    mysqli_stmt_bind_param($update_stmt, "sss", $reset_code, $expires_at, $email);
                    if (!mysqli_stmt_execute($update_stmt)) {
                        throw new Exception("Failed to save reset code");
                    }

                    // Send reset email
                    $emailService = EmailService::getInstance();
                    $emailService->sendPasswordResetEmail($email, $user['first_name'], $reset_code);

                    // Commit transaction
                    mysqli_commit($con);

                    $_SESSION['success_msg'] = "Password reset code has been sent to your email.";
                    $_SESSION['reset_email'] = $email;

                    // Redirect to reset password page
                    header("Location: ResetPassword.php");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($con);
                    error_log("Reset process failed: " . $e->getMessage());
                    throw new Exception("Failed to process password reset request");
                }
            } else {
                // For security, show the same message even if email doesn't exist
                $_SESSION['success_msg'] = "If an account exists with this email, a reset code will be sent.";
                header("Location: login.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            $error_msg = "An error occurred while processing your request. Please try again later.";
        }
    } else {
        $error_msg = "Please enter your email address.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gordon College - forgot-password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#006400",
                        secondary: "#DAA520"
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('chatbot_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(240, 244, 255, 0.95) 0%, rgba(224, 231, 255, 0.95) 100%);
            z-index: -1;
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: #006400;
            box-shadow: 0 0 0 2px rgba(0, 100, 0, 0.1);
        }

        .robot-image {
            animation: float 6s ease-in-out infinite;
            position: relative;
            width: 240px;
            height: 240px;
            margin: 0 auto;
        }

        .robot-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            color: #1a237e;
            font-size: 2rem;
            font-weight: bold;
            margin-top: 1rem;
            letter-spacing: 1px;
            line-height: 1.2;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <!-- Logo Container -->
            <div class="text-center mb-8">
                <div class="robot-image">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDAgMjQwIj4KICAgIDxkZWZzPgogICAgICAgIDxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMwMDAwODA7c3RvcC1vcGFjaXR5OjEiIC8+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzAwMDAzMjtzdG9wLW9wYWNpdHk6MSIgLz4KICAgICAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPC9kZWZzPgogICAgPGNpcmNsZSBjeD0iMTIwIiBjeT0iMTIwIiByPSIxMjAiIGZpbGw9InVybCgjZ3JhZCkiLz4KICAgIDxwYXRoIGZpbGw9IndoaXRlIiBkPSJNNjAgNjBoMTIwdjMwSDYweiIvPgogICAgPGNpcmNsZSBjeD0iOTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8Y2lyY2xlIGN4PSIxNTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8cGF0aCBmaWxsPSJ3aGl0ZSIgZD0iTTYwIDE1MGgxMjBsMC0yMEg2MHoiLz4KPC9zdmc+"
                        alt="ChatBot Logo">
                </div>
                <h2 class="logo-text">Gordon College<br>InquiryBot System</h2>
                <p class="text-gray-600 text-sm mt-2">"YOUR GUIDE, INFO INSIDE"</p>
                <div class="mt-8">
                    <h2 class="text-2xl font-semibold text-primary">Forgot Password</h2>
                    <p class="text-gray-600">Enter your email to receive a reset code</p>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 w-full shadow-xl">
                <form method="post" class="space-y-6">
                    <?php if (isset($error_msg)): ?>
                        <div class="bg-red-50 text-red-500 p-4 rounded-lg text-sm">
                            <i class="ri-error-warning-line mr-2"></i>
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_msg)): ?>
                        <div class="bg-green-50 text-green-500 p-4 rounded-lg text-sm">
                            <i class="ri-checkbox-circle-line mr-2"></i>
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                <i class="ri-mail-line"></i>
                            </span>
                            <input type="email"
                                id="email"
                                name="email"
                                class="input-field w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="Enter your email address"
                                required>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary text-white py-2 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
                        <i class="ri-mail-send-line"></i>
                        <span>Send Reset Code</span>
                    </button>

                    <div class="text-center">
                        <a href="login.php" class="text-primary hover:text-secondary font-medium">
                            <i class="ri-arrow-left-line"></i> Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>