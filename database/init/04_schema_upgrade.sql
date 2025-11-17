-- 04_schema_upgrade.sql

ALTER TABLE users
  ADD COLUMN email TEXT,
  ADD COLUMN email_verified_at TIMESTAMPTZ;

-- Gotta fill in some email addresses for existing users before we can make the column NOT NULL and UNIQUE
UPDATE users
SET email = username || '@example.com'
WHERE email IS NULL;

ALTER TABLE users
  ALTER COLUMN email SET NOT NULL;

ALTER TABLE users
  ADD CONSTRAINT uq_users_email UNIQUE(email);
