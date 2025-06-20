USE login_sample_db;

-- Check if columns exist and add them if they don't
SET @dbname = 'login_sample_db';
SET @tablename = 'users';

-- Check if reset_token column exists
SET @exists = 0;
SELECT COUNT(*) INTO @exists
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'reset_token';

-- Add reset_token if it doesn't exist
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL',
    'SELECT "reset_token column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if reset_token_expiry column exists
SET @exists = 0;
SELECT COUNT(*) INTO @exists
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'reset_token_expiry';

-- Add reset_token_expiry if it doesn't exist
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL',
    'SELECT "reset_token_expiry column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 