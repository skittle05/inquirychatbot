# Email Verification System for Gordon College Chatbot

## Overview
This implementation provides a complete email-based 6-digit authentication code verification system for user registration. The system includes:

- **Signup Page** (`signup.php`) - Collects user information and sends verification email
- **Email Verification Page** (`verify_email.php`) - Handles 6-digit code verification
- **Email Service** (`EmailService.php`) - Manages email sending using PHPMailer
- **Database Structure** - Updated to support verification codes and tokens

## Features

### ✅ Implemented Features
1. **6-Digit Verification Code Generation** - Random 6-digit codes sent via email
2. **Email Sending** - Uses PHPMailer with Gmail SMTP
3. **Verification Page** - Modern UI for code entry with auto-focus
4. **Resend Functionality** - Users can request new verification codes
5. **Session Management** - Secure session handling for pending verifications
6. **Error Handling** - Comprehensive error messages and validation
7. **Database Security** - Prepared statements to prevent SQL injection
8. **Responsive Design** - Modern UI with Tailwind CSS

### 🔧 Technical Implementation

#### Database Structure
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    verification_code VARCHAR(6) NULL,
    verification_token VARCHAR(64) NULL,
    is_verified TINYINT(1) DEFAULT 0,
    reset_code VARCHAR(6) NULL,
    reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Email Configuration
The system uses Gmail SMTP for sending emails. Configuration is stored in `config` file:

```php
// Gmail Configuration
define('GMAIL_USERNAME', 'your-email@gmail.com');
define('GMAIL_APP_PASSWORD', 'your-16-digit-app-password');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-digit-app-password');
```

## Setup Instructions

### 1. Database Setup
Run the updated database script:
```sql
-- Execute setup_database.sql in your MySQL database
```

### 2. Email Configuration
1. Go to your Google Account settings: https://myaccount.google.com/
2. Enable 2-Step Verification if not already enabled
3. Go to Security → App passwords: https://myaccount.google.com/apppasswords
4. Select "Other" from the dropdown and enter "Gordon College Chatbot"
5. Click "Generate" and copy the 16-digit app password
6. Update the `config` file with your Gmail credentials

### 3. PHPMailer Installation
The system uses PHPMailer which should be installed in the `PHPMailer/` directory.

### 4. File Permissions
Ensure the web server has read/write permissions for:
- `error.log` (for error logging)
- Session directory (for session management)

## Usage Flow

### 1. User Registration
1. User visits `signup.php`
2. Fills out registration form (name, email, password)
3. System generates 6-digit verification code
4. Verification email is sent to user's email address
5. User is redirected to `verify_email.php`

### 2. Email Verification
1. User receives email with 6-digit code
2. User enters code on `verify_email.php`
3. System validates code against database
4. If valid, user account is marked as verified
5. User is redirected to login page

### 3. Resend Code
1. If user doesn't receive email, they can click "Resend Code"
2. System generates new 6-digit code
3. New verification email is sent
4. Old code becomes invalid

## Security Features

### ✅ Security Measures Implemented
1. **SQL Injection Prevention** - All database queries use prepared statements
2. **XSS Prevention** - All output is properly escaped using `htmlspecialchars()`
3. **CSRF Protection** - Session-based verification
4. **Password Hashing** - Passwords are hashed using `password_hash()`
5. **Secure Token Generation** - Uses `random_bytes()` for token generation
6. **Email Validation** - Proper email format validation
7. **Session Security** - Secure session handling

### 🔒 Additional Security Recommendations
1. **HTTPS** - Use HTTPS in production
2. **Rate Limiting** - Implement rate limiting for email sending
3. **Code Expiration** - Set expiration time for verification codes
4. **Logging** - Monitor failed verification attempts

## File Structure

```
├── signup.php              # Registration form with email verification
├── verify_email.php        # 6-digit code verification page
├── EmailService.php        # Email service using PHPMailer
├── config                  # Configuration file (email, database)
├── connection.php          # Database connection
├── functions.php           # Utility functions
├── setup_database.sql      # Database structure
├── test_email_verification.php  # Test script
└── PHPMailer/             # PHPMailer library
```

## Testing

### Run the Test Script
Visit `test_email_verification.php` to verify:
- Database connection
- Table structure
- Email service configuration
- Verification code generation

### Manual Testing
1. Register a new account at `signup.php`
2. Check your email for verification code
3. Enter the code at `verify_email.php`
4. Verify account activation

## Troubleshooting

### Common Issues

#### 1. Email Not Sending
- Check Gmail app password configuration
- Verify SMTP settings in `config` file
- Check error logs in `error.log`

#### 2. Database Connection Issues
- Verify database credentials in `config` file
- Ensure MySQL service is running
- Check database name matches in `connection.php`

#### 3. Verification Code Not Working
- Check if user exists in database
- Verify verification code format (6 digits)
- Check if account is already verified

### Error Logs
Check `error.log` for detailed error messages and debugging information.

## Customization

### Email Template
Modify the email template in `EmailService.php`:
```php
public function sendVerificationEmail($to_email, $user_name, $verification_code)
{
    // Customize HTML and text email templates here
}
```

### UI Styling
The system uses Tailwind CSS. Modify the styling in the respective PHP files.

### Verification Code Format
Change the code generation in `signup.php`:
```php
$verification_code = sprintf("%06d", mt_rand(0, 999999)); // 6 digits
```

## API Integration

The system is ready for API integration. Key endpoints:
- `signup.php` - POST registration data
- `verify_email.php` - POST verification code
- Session-based authentication

## Support

For issues or questions:
1. Check the error logs
2. Run the test script
3. Verify configuration settings
4. Check database structure

---

**Note**: This implementation provides a production-ready email verification system with modern security practices and user experience considerations. 