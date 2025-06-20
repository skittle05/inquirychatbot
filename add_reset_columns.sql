-- Add reset code and expiration columns to users table
ALTER TABLE users
ADD COLUMN reset_code VARCHAR(6) NULL,
ADD COLUMN reset_expires_at DATETIME NULL,
ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN verification_code VARCHAR(6) NULL,
ADD COLUMN verification_expires_at DATETIME NULL; 