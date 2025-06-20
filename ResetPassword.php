<?php
ob_start(); // Start output buffering
session_start();
define('SECURE_ACCESS', true);

require_once 'connection.php';
require_once 'functions.php';
require_once 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: ForgotPassword.php");
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $reset_code = isset($_POST['reset_code']) ? trim(mysqli_real_escape_string($con, $_POST['reset_code'])) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    error_log("Reset attempt for email: " . $email);
    error_log("Reset code length: " . strlen($reset_code));
    error_log("New password length: " . strlen($new_password));

    if (!empty($reset_code) && !empty($new_password) && !empty($confirm_password)) {
        if (strlen($new_password) < 6) {
            $error_msg = "Password must be at least 6 characters long!";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "Passwords do not match!";
        } else {
            try {
                // First verify the reset code
                $verify_query = "SELECT * FROM users WHERE email = ? AND reset_code = ? LIMIT 1";
                $verify_stmt = mysqli_prepare($con, $verify_query);
                if (!$verify_stmt) {
                    throw new Exception("Failed to prepare verification statement: " . mysqli_error($con));
                }

                mysqli_stmt_bind_param($verify_stmt, "ss", $email, $reset_code);
                if (!mysqli_stmt_execute($verify_stmt)) {
                    throw new Exception("Failed to execute verification: " . mysqli_stmt_error($verify_stmt));
                }

                $verify_result = mysqli_stmt_get_result($verify_stmt);
                error_log("Found matching reset code: " . (mysqli_num_rows($verify_result) > 0 ? "Yes" : "No"));

                if (mysqli_num_rows($verify_result) > 0) {
                    $user_data = mysqli_fetch_assoc($verify_result);

                    // Generate new password hash with specific cost
                    $options = ['cost' => 10];
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, $options);
                    error_log("Generated new password hash: " . substr($hashed_password, 0, 20) . "...");

                    // Verify the hash works before saving
                    $verify_hash = password_verify($new_password, $hashed_password);
                    error_log("Initial hash verification: " . ($verify_hash ? "Success" : "Failed"));

                    if (!$verify_hash) {
                        throw new Exception("Password hash verification failed");
                    }

                    // Begin transaction
                    mysqli_begin_transaction($con);

                    try {
                        // Update the password
                        $update_query = "UPDATE users SET 
                            password = ?,
                            reset_code = NULL,
                            reset_expires_at = NULL
                            WHERE email = ?";

                        $update_stmt = mysqli_prepare($con, $update_query);
                        if (!$update_stmt) {
                            throw new Exception("Failed to prepare update statement");
                        }

                        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $email);
                        if (!mysqli_stmt_execute($update_stmt)) {
                            throw new Exception("Failed to update password: " . mysqli_stmt_error($update_stmt));
                        }

                        $affected_rows = mysqli_affected_rows($con);
                        error_log("Rows affected by update: " . $affected_rows);

                        if ($affected_rows !== 1) {
                            throw new Exception("Password update failed - affected rows: " . $affected_rows);
                        }

                        // Verify the update
                        $verify_update_query = "SELECT * FROM users WHERE email = ? LIMIT 1";
                        $verify_update_stmt = mysqli_prepare($con, $verify_update_query);
                        mysqli_stmt_bind_param($verify_update_stmt, "s", $email);
                        mysqli_stmt_execute($verify_update_stmt);
                        $verify_update_result = mysqli_stmt_get_result($verify_update_stmt);
                        $updated_user = mysqli_fetch_assoc($verify_update_result);

                        if (!$updated_user) {
                            throw new Exception("Failed to retrieve updated user data");
                        }

                        // Final verification of the new password
                        $final_verify = password_verify($new_password, $updated_user['password']);
                        error_log("Final password verification: " . ($final_verify ? "Success" : "Failed"));
                        error_log("Stored hash in DB: " . substr($updated_user['password'], 0, 20) . "...");

                        if (!$final_verify) {
                            throw new Exception("Final password verification failed");
                        }

                        // Commit transaction
                        mysqli_commit($con);

                        // Log successful password update
                        error_log("Password successfully updated for email: " . $email);

                        // Clear all session data
                        session_unset();
                        session_destroy();
                        session_start();

                        // Set success message
                        $success_msg = "Password has been reset successfully! Please login with your new password.";
                    } catch (Exception $e) {
                        mysqli_rollback($con);
                        error_log("Transaction failed: " . $e->getMessage());
                        throw new Exception("Failed to update password: " . $e->getMessage());
                    }
                } else {
                    // Check if code exists but expired
                    $check_query = "SELECT reset_code FROM users WHERE email = ? LIMIT 1";
                    $check_stmt = mysqli_prepare($con, $check_query);
                    mysqli_stmt_bind_param($check_stmt, "s", $email);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    $user_check = mysqli_fetch_assoc($check_result);

                    if ($user_check) {
                        error_log("Stored reset code: " . $user_check['reset_code']);
                        error_log("Provided reset code: " . $reset_code);

                        if ($user_check['reset_code'] !== $reset_code) {
                            $error_msg = "Invalid reset code. Please try again.";
                        } else {
                            $error_msg = "Reset code has expired. Please request a new one.";
                        }
                    } else {
                        $error_msg = "Email not found.";
                    }
                }
            } catch (Exception $e) {
                error_log("Reset Password Error: " . $e->getMessage());
                $error_msg = "An error occurred: " . $e->getMessage();
            }
        }
    } else {
        $error_msg = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Gordon College - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h2 class="text-2xl font-semibold text-primary">Reset Password</h2>
                    <p class="text-gray-600">Enter the code sent to your email</p>
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 w-full shadow-xl">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-primary">Reset Password</h2>
                    <p class="text-gray-600">Enter the code sent to your email</p>
                </div>

                <form method="post" class="space-y-6" id="resetForm">
                    <?php if (isset($error_msg)): ?>
                        <div class="bg-red-50 text-red-500 p-4 rounded-lg text-sm flex items-center">
                            <i class="ri-error-warning-line mr-2"></i>
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_msg)): ?>
                        <div class="bg-green-50 text-green-500 p-4 rounded-lg text-sm flex items-center">
                            <i class="ri-checkbox-circle-line mr-2"></i>
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label for="reset_code" class="block text-sm font-medium text-gray-700 mb-1">Reset Code</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                <i class="ri-key-2-line"></i>
                            </span>
                            <input type="text"
                                id="reset_code"
                                name="reset_code"
                                class="input-field w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="Enter 6-digit reset code"
                                required
                                pattern="[0-9]{6}"
                                maxlength="6">
                        </div>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                <i class="ri-lock-line"></i>
                            </span>
                            <input type="password"
                                id="new_password"
                                name="new_password"
                                class="input-field w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="Enter new password"
                                required
                                minlength="6">
                            <button type="button"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                                onclick="togglePassword('new_password', 'toggleIcon1')">
                                <i class="ri-eye-line" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                <i class="ri-lock-line"></i>
                            </span>
                            <input type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="input-field w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="Confirm new password"
                                required
                                minlength="6">
                            <button type="button"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                                onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                <i class="ri-eye-line" id="toggleIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary text-white py-2 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
                        <i class="ri-lock-unlock-line"></i>
                        <span>Reset Password</span>
                    </button>

                    <div class="text-center">
                        <a href="login.php" class="text-primary hover:text-secondary font-medium inline-flex items-center">
                            <i class="ri-arrow-left-line mr-1"></i> Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('ri-eye-line', 'ri-eye-off-line');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('ri-eye-off-line', 'ri-eye-line');
            }
        }

        // Auto-format reset code input
        document.getElementById('reset_code').addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
        });

        <?php if (isset($success_msg)): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo $success_msg; ?>',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#006400',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>