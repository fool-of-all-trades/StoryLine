-- 08_schema_upgrade.sql

ALTER TABLE users
  ADD COLUMN avatar_path text NULL;