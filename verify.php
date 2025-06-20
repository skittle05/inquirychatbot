<?php
session_start();
include("connection.php");
include("functions.php");

$msg = '';
$msg_type = '';

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($con, $_GET['token']);

    // Check if token exists and is valid
    $query = "SELECT * FROM users WHERE verification_token = ? AND is_verified = 0 LIMIT 1";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Update user as verified
        $update_query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?";
        $update_stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($update_stmt, "s", $token);

        if (mysqli_stmt_execute($update_stmt)) {
            $msg = "Your email has been verified successfully! You can now login.";
            $msg_type = "success";
        } else {
            $msg = "Error verifying email. Please try again.";
            $msg_type = "error";
        }
    } else {
        $msg = "Invalid or expired verification link.";
        $msg_type = "error";
    }
} else {
    $msg = "Invalid verification link.";
    $msg_type = "error";
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
                <h2 class="text-2xl font-bold text-primary">CHATBOT</h2>
                <p class="text-gray-600">Gordon College</p>
                <p class="text-gray-600 text-sm mt-2">Excellence • Character • Service</p>
            </div>

            <!-- Verification Status -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 w-full shadow-xl">
                <div class="text-center">
                    <?php if ($msg_type === 'success'): ?>
                        <div class="text-green-500 mb-4">
                            <i class="ri-checkbox-circle-line text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-primary mb-4">Email Verified!</h2>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($msg); ?></p>
                        <a href="login.php"
                            class="inline-block bg-primary text-white py-2 px-6 rounded-lg hover:bg-secondary transition-colors duration-300">
                            Go to Login
                        </a>
                    <?php else: ?>
                        <div class="text-red-500 mb-4">
                            <i class="ri-error-warning-line text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-red-500 mb-4">Verification Failed</h2>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($msg); ?></p>
                        <a href="signup.php"
                            class="inline-block bg-primary text-white py-2 px-6 rounded-lg hover:bg-secondary transition-colors duration-300">
                            Back to Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>