CREATE EXTENSION IF NOT EXISTS "pgcrypto";

ALTER TABLE users
  ADD COLUMN public_id uuid UNIQUE DEFAULT gen_random_uuid();

UPDATE users SET public_id = gen_random_uuid() WHERE public_id IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_public_id ON users (public_id);