-- Add verification code columns if they don't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS verification_code VARCHAR(6) NULL,
ADD COLUMN IF NOT EXISTS verification_code_expiry DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME NULL,
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0; 