<?php
session_start();
include("connection.php");
include("functions.php");

// Check if user is logged in
$user_data = check_login($con);

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        // Verify current password using password_verify
        if (password_verify($current_password, $user_data['password'])) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database using prepared statement
                $user_id = $user_data['user_id'];
                $query = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($con, $query);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Password updated successfully! You will be redirected to the login page shortly.";
                    // Destroy the session for security
                    session_destroy();
                    // Add JavaScript to redirect after showing the success message
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000);
                    </script>";
                } else {
                    $error_msg = "Error updating password. Please try again.";
                }
            } else {
                $error_msg = "New passwords do not match!";
            }
        } else {
            $error_msg = "Current password is incorrect!";
        }
    } else {
        $error_msg = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gordon College - Change Password</title>
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
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Header Section -->
    <header class="w-full bg-white py-4 px-6 shadow-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <img
                src="https://public.readdy.ai/ai/img_res/ac1878a8-ff12-4671-b0ca-67598e0772d2.jpeg"
                alt="Gordon College Logo"
                class="h-20 w-auto object-contain" />
            <div class="flex-1 text-center school-header">
                <h1 class="text-2xl font-bold text-primary mt-1">GORDON COLLEGE</h1>
                <p class="text-sm text-gray-600">Change Password</p>
            </div>
            <img
                src="https://public.readdy.ai/ai/img_res/c54f0d16-bfb3-449c-90c0-14393e0d4149.jpeg"
                alt="Gordon College Computer Studies Logo"
                class="h-20 w-auto object-contain" />
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-md shadow-xl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-primary">Change Password</h2>
                <p class="text-gray-600">Update your account password</p>
            </div>

            <form method="post" class="space-y-6">
                <?php if (isset($error_msg)): ?>
                    <div class="bg-red-50 text-red-500 p-3 rounded-lg text-sm">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_msg)): ?>
                    <div class="bg-green-50 text-green-500 p-3 rounded-lg text-sm">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="ri-lock-line"></i>
                        </span>
                        <input type="password"
                            id="current_password"
                            name="current_password"
                            class="input-field w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none"
                            placeholder="Enter current password"
                            required>
                        <button type="button"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                            onclick="togglePassword('current_password', 'currentToggleIcon')">
                            <i class="ri-eye-line" id="currentToggleIcon"></i>
                        </button>
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
                            required>
                        <button type="button"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                            onclick="togglePassword('new_password', 'newToggleIcon')">
                            <i class="ri-eye-line" id="newToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="ri-lock-line"></i>
                        </span>
                        <input type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="input-field w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none"
                            placeholder="Confirm new password"
                            required>
                        <button type="button"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                            onclick="togglePassword('confirm_password', 'confirmToggleIcon')">
                            <i class="ri-eye-line" id="confirmToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-primary text-white py-2 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
                    <i class="ri-save-line"></i>
                    <span>Update Password</span>
                </button>

                <div class="text-center">
                    <a href="login.php" class="text-primary hover:text-secondary font-medium">
                        <i class="ri-arrow-left-line"></i> Back to login
                    </a>
                </div>
            </form>
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
    </script>
</body>

</html>