-- Create users table if it doesn't exist
CREATE TABLE IF NOT EXISTS users (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_id (user_id),
    INDEX idx_verification_token (verification_token)
);

-- Add any missing columns if table already exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS user_id VARCHAR(20) NOT NULL UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS verification_code VARCHAR(6) NULL AFTER last_name,
ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL AFTER verification_code,
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0 AFTER verification_token,
ADD COLUMN IF NOT EXISTS reset_code VARCHAR(6) NULL AFTER is_verified,
ADD COLUMN IF NOT EXISTS reset_expires_at DATETIME NULL AFTER reset_code,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER reset_expires_at,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_user_id ON users(user_id);
CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token); 