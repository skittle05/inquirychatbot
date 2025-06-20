<?php
session_start();

// Define security constant for EmailService
define('SECURE_ACCESS', true);

include("connection.php");
include("functions.php");
include("EmailService.php");

use App\EmailService;

$error_msg = '';
$success_msg = '';
$show_form = true;

// Check if user has pending verification
if (!isset($_SESSION['pending_verification'])) {
    header("Location: signup.php");
    exit();
}

$pending_user = $_SESSION['pending_verification'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['verification_code'])) {
        $verification_code = mysqli_real_escape_string($con, $_POST['verification_code']);

        // Check if verification code matches
        $query = "SELECT * FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0 LIMIT 1";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "ss", $pending_user['email'], $verification_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            // Update user as verified
            $update_query = "UPDATE users SET is_verified = 1, verification_code = NULL, verification_token = NULL WHERE email = ?";
            $update_stmt = mysqli_prepare($con, $update_query);
            mysqli_stmt_bind_param($update_stmt, "s", $pending_user['email']);

            if (mysqli_stmt_execute($update_stmt)) {
                $success_msg = "Email verified successfully! You can now login to your account.";
                $show_form = false;
                // Clear the session
                unset($_SESSION['pending_verification']);
            } else {
                $error_msg = "Error verifying email. Please try again.";
            }
        } else {
            $error_msg = "Invalid verification code. Please check your email and try again.";
        }
    } elseif (isset($_POST['resend_code'])) {
        // Resend verification code
        try {
            $emailService = EmailService::getInstance();

            // Generate new verification code
            $new_verification_code = sprintf("%06d", mt_rand(0, 999999));

            // Update the verification code in database
            $update_query = "UPDATE users SET verification_code = ? WHERE email = ? AND is_verified = 0";
            $update_stmt = mysqli_prepare($con, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ss", $new_verification_code, $pending_user['email']);

            if (mysqli_stmt_execute($update_stmt)) {
                // Send new verification email
                if ($emailService->sendVerificationEmail($pending_user['email'], $pending_user['user_name'], $new_verification_code)) {
                    $success_msg = "New verification code has been sent to your email.";
                } else {
                    $error_msg = "Failed to send new verification code. Please try again.";
                }
            } else {
                $error_msg = "Error updating verification code. Please try again.";
            }
        } catch (Exception $e) {
            $error_msg = "Error sending verification code: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gordon College - Email Verification</title>
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
            background-color: #f9f9f9;
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
            width: 180px;
            height: 180px;
            margin: 0 auto;
        }

        .robot-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-text {
            color: #1a237e;
            font-size: 2rem;
            font-weight: bold;
            margin-top: 1rem;
            letter-spacing: 1px;
        }

        .logo-subtext {
            color: #4a90e2;
            font-size: 1.1rem;
            margin-top: 0.25rem;
        }

        .verification-input {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
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
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <!-- Logo Container -->
            <div class="logo-container">
                <div class="robot-image">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDAgMjQwIj4KICAgIDxkZWZzPgogICAgICAgIDxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMwMDAwODA7c3RvcC1vcGFjaXR5OjEiIC8+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzAwMDAzMjtzdG9wLW9wYWNpdHk6MSIgLz4KICAgICAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPC9kZWZzPgogICAgPGNpcmNsZSBjeD0iMTIwIiBjeT0iMTIwIiByPSIxMjAiIGZpbGw9InVybCgjZ3JhZCkiLz4KICAgIDxwYXRoIGZpbGw9IndoaXRlIiBkPSJNNjAgNjBoMTIwdjMwSDYweiIvPgogICAgPGNpcmNsZSBjeD0iOTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8Y2lyY2xlIGN4PSIxNTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8cGF0aCBmaWxsPSJ3aGl0ZSIgZD0iTTYwIDE1MGgxMjBsMC0yMEg2MHoiLz4KPC9zdmc+"
                        alt="ChatBot Logo">
                </div>
                <h2 class="logo-text">CHATBOT</h2>
                <p class="logo-subtext">Gordon College</p>
                <p class="text-gray-600 text-sm mt-2">"YOUR GUIDE, INFO INSIDE"</p>
            </div>

            <!-- Verification Form -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 w-full shadow-xl">
                <?php if ($show_form): ?>
                    <div class="text-center mb-8">
                        <div class="text-primary mb-4">
                            <i class="ri-mail-check-line text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-primary">Email Verification</h2>
                        <p class="text-gray-600 mt-2">Please enter the 6-digit code sent to</p>
                        <p class="text-primary font-medium"><?php echo htmlspecialchars($pending_user['email']); ?></p>
                    </div>

                    <?php if (isset($error_msg) && !empty($error_msg)): ?>
                        <div class="bg-red-50 text-red-500 p-3 rounded-lg text-sm mb-6">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_msg) && !empty($success_msg)): ?>
                        <div class="bg-green-50 text-green-600 p-3 rounded-lg text-sm mb-6">
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="space-y-6">
                        <div>
                            <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-3 text-center">
                                Verification Code
                            </label>
                            <div class="flex justify-center">
                                <input type="text"
                                    id="verification_code"
                                    name="verification_code"
                                    class="verification-input w-48 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                                    placeholder="000000"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    required
                                    autocomplete="off">
                            </div>
                            <p class="text-xs text-gray-500 mt-2 text-center">Enter the 6-digit code from your email</p>
                        </div>

                        <button type="submit"
                            class="w-full bg-primary text-white py-3 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
                            <i class="ri-check-line"></i>
                            <span>Verify Email</span>
                        </button>

                        <div class="text-center">
                            <p class="text-gray-600 text-sm mb-4">
                                Didn't receive the code?
                            </p>
                            <form method="post" class="inline">
                                <button type="submit" name="resend_code"
                                    class="text-primary hover:text-secondary font-medium text-sm">
                                    <i class="ri-refresh-line"></i>
                                    Resend Code
                                </button>
                            </form>
                        </div>

                        <div class="text-center pt-4 border-t border-gray-200">
                            <a href="signup.php" class="text-gray-500 hover:text-primary text-sm">
                                <i class="ri-arrow-left-line"></i>
                                Back to Sign Up
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Success Message -->
                    <div class="text-center">
                        <div class="text-green-500 mb-4">
                            <i class="ri-checkbox-circle-line text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-primary mb-4">Email Verified!</h2>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($success_msg); ?></p>
                        <a href="login.php"
                            class="inline-block bg-primary text-white py-3 px-8 rounded-lg hover:bg-secondary transition-colors duration-300">
                            <i class="ri-login-box-line"></i>
                            Go to Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Auto-focus on verification code input
        document.addEventListener('DOMContentLoaded', function() {
            const verificationInput = document.getElementById('verification_code');
            if (verificationInput) {
                verificationInput.focus();
            }
        });

        // Auto-format verification code input
        document.getElementById('verification_code')?.addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');

            // Limit to 6 digits
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });
    </script>
</body>

</html>