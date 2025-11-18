-- 06_schema_upgrade.sql

ALTER TABLE users
  ADD COLUMN favorite_quote_sentence text,
  ADD COLUMN favorite_quote_book text,
  ADD COLUMN favorite_quote_author text;
