-- 05_schema_upgrade.sql

ALTER TABLE stories
  ADD COLUMN guest_name varchar(60);