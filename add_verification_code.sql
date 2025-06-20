ALTER TABLE users 
ADD COLUMN verification_code VARCHAR(6) DEFAULT NULL,
ADD COLUMN verification_code_expiry DATETIME DEFAULT NULL; 