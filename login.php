<?php

session_start();

include("connection.php");
include("functions.php");


if ($_SERVER['REQUEST_METHOD'] == "POST") {
  //something was posted
  $email = mysqli_real_escape_string($con, trim($_POST['email']));
  $password = trim($_POST['password']);

  error_log("Login attempt - Email: " . $email);
  error_log("Login attempt - Password length: " . strlen($password));

  if (!empty($email) && !empty($password)) {
    try {
      //read from database using prepared statement
      $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
      $stmt = mysqli_prepare($con, $query);
      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);

        error_log("User found - Email: " . $email);
        error_log("Stored password hash: " . substr($user_data['password'], 0, 20) . "...");
        error_log("Hash info: " . print_r(password_get_info($user_data['password']), true));

        // Verify password
        $verify_result = password_verify($password, $user_data['password']);
        error_log("Password verification result: " . ($verify_result ? "Success" : "Failed"));

        if ($verify_result) {
          // Check if password needs rehash
          if (password_needs_rehash($user_data['password'], PASSWORD_BCRYPT, ['cost' => 10])) {
            error_log("Password needs rehash - updating hash");
            $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

            // Update the hash in the database
            $update_query = "UPDATE users SET password = ? WHERE email = ?";
            $update_stmt = mysqli_prepare($con, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ss", $new_hash, $email);
            mysqli_stmt_execute($update_stmt);
          }

          // Start fresh session
          session_regenerate_id(true);

          // Set session data
          $_SESSION['user_id'] = $user_data['id'];
          $_SESSION['email'] = $user_data['email'];

          // Clear any reset-related session data
          unset($_SESSION['reset_email']);
          unset($_SESSION['success_msg']);

          error_log("Login successful - Email: " . $email);
          header("Location: index.php");
          exit();
        } else {
          error_log("Login failed - Invalid password for email: " . $email);
          error_log("Attempted password length: " . strlen($password));
          $error_msg = "Invalid email or password!";
        }
      } else {
        error_log("Login failed - Email not found: " . $email);
        $error_msg = "Invalid email or password!";
      }
    } catch (Exception $e) {
      error_log("Login Error: " . $e->getMessage());
      $error_msg = "An error occurred. Please try again later.";
    }
  } else {
    $error_msg = "Please enter valid information!";
  }
}

// Check for success message from password reset
if (isset($_SESSION['success_msg'])) {
  $success_msg = $_SESSION['success_msg'];
  unset($_SESSION['success_msg']);
}

?>


<!DOCTYPE html>
<html>

<head>
  <title>Gordon College - Login</title>

</head>

<body>






  </style>
  <title>Gordon College - Login</title>

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

    .login-container {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
      width: 360px;
      height: 360px;
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
      font-size: 2.5rem;
      font-weight: bold;
      margin-top: 1rem;
      letter-spacing: 1px;
      line-height: 1.2;
    }

    .logo-subtext {
      color: #4a90e2;
      font-size: 1.3rem;
      margin-top: 0.5rem;
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

  <!-- Main Content -->
  <main
    class="flex-1 flex items-center justify-center p-6"
    \\\ background image \\>
    <div
      class="container mx-auto max-w-6xl">
      <div class="flex flex-col lg:flex-row items-center justify-between gap-12">
        <!-- Left side - Logo -->
        <div class="w-full lg:w-1/2 flex flex-col items-center">
          <div class="logo-container">
            <div class="robot-image">
              <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDAgMjQwIj4KICAgIDxkZWZzPgogICAgICAgIDxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMwMDAwODA7c3RvcC1vcGFjaXR5OjEiIC8+CiAgICAgICAgICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzAwMDAzMjtzdG9wLW9wYWNpdHk6MSIgLz4KICAgICAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPC9kZWZzPgogICAgPGNpcmNsZSBjeD0iMTIwIiBjeT0iMTIwIiByPSIxMjAiIGZpbGw9InVybCgjZ3JhZCkiLz4KICAgIDxwYXRoIGZpbGw9IndoaXRlIiBkPSJNNjAgNjBoMTIwdjMwSDYweiIvPgogICAgPGNpcmNsZSBjeD0iOTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8Y2lyY2xlIGN4PSIxNTAiIGN5PSIxMjAiIHI9IjE1IiBmaWxsPSIjMDBmZmZmIi8+CiAgICA8cGF0aCBmaWxsPSJ3aGl0ZSIgZD0iTTYwIDE1MGgxMjBsMC0yMEg2MHoiLz4KPC9zdmc+"
                alt="ChatBot Logo">
            </div>
            <h2 class="logo-text">Gordon College<br>InquiryBot System</h2>
            <p class="text-gray-600 text-sm mt-2">"YOUR GUIDE, INFO INSIDE"</p>
          </div>
        </div>

        <!-- Right side - Login Form -->
        <div class="w-full lg:w-1/2">
          <div class="login-container rounded-3xl p-8 md:p-12">
            <div class="text-center mb-8">
              <h1 class="text-3xl font-bold text-primary mb-2">Welcome Back!</h1>
              <p class="text-gray-600">Login to access your AI assistant</p>
            </div>

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

              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                    <i class="ri-lock-line"></i>
                  </span>
                  <input type="password"
                    id="password"
                    name="password"
                    class="input-field w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none"
                    placeholder="Enter your password"
                    required>
                  <button type="button"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
                    onclick="togglePassword()">
                    <i class="ri-eye-line" id="toggleIcon"></i>
                  </button>
                </div>
                <div class="flex justify-end mt-1">
                  <a href="ForgotPassword.php" class="text-sm text-primary hover:text-secondary">Forgot Password?</a>
                </div>
              </div>

              <button type="submit"
                class="w-full bg-primary text-white py-2 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
                <i class="ri-login-box-line"></i>
                <span>Login</span>
              </button>

              <div class="text-center">
                <p class="text-gray-600">
                  Don't have an account?
                  <a href="signup.php" class="text-primary hover:text-secondary font-medium">Sign Up</a>
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script>
      function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

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