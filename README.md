# Gordon College Chatbot Login System

This project is a login and user management system for the Gordon College Chatbot. It includes features like user registration, login, password change, and email verification.

## Features

*   **User Registration:** New users can register for an account.
*   **User Login:** Registered users can log in to their accounts.
*   **Password Change:** Logged-in users can change their password.
*   **Email Verification:** New users need to verify their email address before they can log in.
*   **Password Reset:** Users who have forgotten their password can reset it via email.

## File Structure

*   `index.php`: The main dashboard page for logged-in users.
*   `login.php`: The login page.
*   `signup.php`: The user registration page.
*   `logout.php`: The script to log out the user.
*   `change_password.php`: The page to change the user's password.
*   `ForgotPassword.php`: The page to request a password reset.
*   `ResetPassword.php`: The page to reset the password.
*   `verify_email.php`: The page to verify the user's email address.
*   `connection.php`: The script to connect to the database.
*   `functions.php`: A file containing various helper functions.
*   `config.php`: The configuration file for the database and email settings.
*   `EmailService.php`: A service for sending emails.
*   `database.sql`: The SQL script to set up the database.

## Setup

1.  **Database:**
    *   Create a new database in your MySQL server.
    *   Import the `database.sql` file to set up the required tables.
2.  **Configuration:**
    *   Rename `config.php.example` to `config.php`.
    *   Open `config.php` and update the database credentials and email settings.
3.  **Dependencies:**
    *   This project uses PHPMailer for sending emails. Make sure you have it installed and configured correctly.

## Recent Changes

*   **Fixed a bug in the change password functionality:** The `mysqli_stmt_bind_param` function was using incorrect type hints, which has now been corrected.
*   **Improved the user experience:** The success message on the change password page has been made more user-friendly, and a "Back to login" link has been added.
*   **Added a `.gitignore` file:** To prevent sensitive files from being committed to version control.

## Running the Project

1.  **Start your web server:** Make sure you have a web server (e.g., Apache, Nginx) running.
2.  **Place the project in your web server's root directory:** Copy the project files to the `htdocs` or `www` directory of your web server.
3.  **Access the project in your browser:** Open your web browser and navigate to `http://localhost/your-project-directory`.

## Testing

To test the password change functionality, you can run the `test_change_password.php` script from your terminal:

```bash
php test_change_password.php
```

**Note:** This is a placeholder test file. For a real-world application, you should use a testing framework like PHPUnit to write more comprehensive tests. 