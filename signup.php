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

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	//something was posted
	$email = mysqli_real_escape_string($con, $_POST['email']);
	$password = $_POST['password'];
	$confirm_password = $_POST['confirm_password'];
	$first_name = mysqli_real_escape_string($con, $_POST['first_name']);
	$last_name = mysqli_real_escape_string($con, $_POST['last_name']);

	if (!empty($email) && !empty($password) && !empty($first_name) && !empty($last_name)) {
		if ($password !== $confirm_password) {
			$error_msg = "Passwords do not match!";
		} else {
			// Check if email is valid
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				// Check if email already exists using prepared statement
				$check_query = "SELECT * FROM users WHERE email = ? LIMIT 1";
				$stmt = mysqli_prepare($con, $check_query);
				mysqli_stmt_bind_param($stmt, "s", $email);
				mysqli_stmt_execute($stmt);
				$check_result = mysqli_stmt_get_result($stmt);

				if (mysqli_num_rows($check_result) > 0) {
					$error_msg = "Email already exists!";
				} else {
					// Generate 6-digit verification code
					$verification_code = sprintf("%06d", mt_rand(0, 999999));
					$verification_token = bin2hex(random_bytes(32));

					// Hash the password
					$hashed_password = password_hash($password, PASSWORD_DEFAULT);

					//save to database using prepared statement
					$user_id = random_num(20);
					$query = "INSERT INTO users (user_id, email, password, first_name, last_name, verification_code, verification_token, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";
					$stmt = mysqli_prepare($con, $query);
					mysqli_stmt_bind_param($stmt, "sssssss", $user_id, $email, $hashed_password, $first_name, $last_name, $verification_code, $verification_token);

					if (mysqli_stmt_execute($stmt)) {
						try {
							// Send verification email
							$emailService = EmailService::getInstance();
							$user_name = $first_name . ' ' . $last_name;

							if ($emailService->sendVerificationEmail($email, $user_name, $verification_code)) {
								// Store user info in session for verification page
								$_SESSION['pending_verification'] = [
									'email' => $email,
									'user_name' => $user_name,
									'verification_token' => $verification_token
								];

								$success_msg = "Account created successfully! Please check your email for the verification code.";
							} else {
								// If email fails, delete the user record
								$delete_query = "DELETE FROM users WHERE email = ?";
								$delete_stmt = mysqli_prepare($con, $delete_query);
								mysqli_stmt_bind_param($delete_stmt, "s", $email);
								mysqli_stmt_execute($delete_stmt);

								$error_msg = "Account created but failed to send verification email. Please try again.";
							}
						} catch (Exception $e) {
							// If email fails, delete the user record
							$delete_query = "DELETE FROM users WHERE email = ?";
							$delete_stmt = mysqli_prepare($con, $delete_query);
							mysqli_stmt_bind_param($delete_stmt, "s", $email);
							mysqli_stmt_execute($delete_stmt);

							$error_msg = "Error sending verification email: " . $e->getMessage();
						}
					} else {
						$error_msg = "Error creating account. Please try again.";
					}
				}
			} else {
				$error_msg = "Please enter a valid email address!";
			}
		}
	} else {
		$error_msg = "Please enter valid information!";
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>Gordon College - Sign Up</title>

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

			<!-- Sign Up Form -->
			<div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 w-full shadow-xl">
				<div class="text-center mb-8">
					<h2 class="text-2xl font-semibold text-primary">Sign Up Account</h2>
					<p class="text-gray-600">Join the Gordon College community</p>
				</div>

				<form method="post" class="space-y-6">
					<?php if (isset($error_msg) && !empty($error_msg)): ?>
						<div class="bg-red-50 text-red-500 p-3 rounded-lg text-sm">
							<?php echo htmlspecialchars($error_msg); ?>
						</div>
					<?php endif; ?>

					<?php if (isset($success_msg) && !empty($success_msg)): ?>
						<div class="bg-green-50 text-green-600 p-3 rounded-lg text-sm">
							<?php echo htmlspecialchars($success_msg); ?>
							<div class="mt-2">
								<a href="verify_email.php" class="text-primary hover:text-secondary font-medium">Click here to verify your email</a>
							</div>
						</div>
					<?php endif; ?>

					<div>
						<label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
						<div class="relative">
							<span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
								<i class="ri-user-line"></i>
							</span>
							<input type="text"
								id="first_name"
								name="first_name"
								class="input-field w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
								placeholder="Enter your first name"
								value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
								required>
						</div>
					</div>

					<div>
						<label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
						<div class="relative">
							<span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
								<i class="ri-user-line"></i>
							</span>
							<input type="text"
								id="last_name"
								name="last_name"
								class="input-field w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
								placeholder="Enter your last name"
								value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
								required>
						</div>
					</div>

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
								value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
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
								minlength="6"
								required>
							<button type="button"
								class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
								onclick="togglePassword('password', 'toggleIcon')">
								<i class="ri-eye-line" id="toggleIcon"></i>
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
								placeholder="Confirm your password"
								minlength="6"
								required>
							<button type="button"
								class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary"
								onclick="togglePassword('confirm_password', 'toggleIconConfirm')">
								<i class="ri-eye-line" id="toggleIconConfirm"></i>
							</button>
						</div>
					</div>

					<button type="submit"
						class="w-full bg-primary text-white py-2 rounded-lg hover:bg-secondary transition-colors duration-300 flex items-center justify-center space-x-2">
						<i class="ri-user-add-line"></i>
						<span>Sign Up</span>
					</button>

					<div class="text-center">
						<p class="text-gray-600">
							Already have an account?
							<a href="login.php" class="text-primary hover:text-secondary font-medium">Login</a>
						</p>
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
	</script>
</body>

</html>