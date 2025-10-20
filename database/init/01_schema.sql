CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS users (
  id            BIGSERIAL PRIMARY KEY,
  username      VARCHAR(40) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role          TEXT NOT NULL CHECK (role IN ('admin','user')),
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS daily_prompt (
  id            BIGSERIAL PRIMARY KEY,
  "date"        DATE UNIQUE NOT NULL,
  sentence      TEXT NOT NULL,
  source_book   TEXT,
  source_author TEXT,
  source_id     TEXT,
  fetched_at    TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE IF NOT EXISTS stories (
  id            BIGSERIAL PRIMARY KEY,
  prompt_id     BIGINT NOT NULL REFERENCES daily_prompt(id) ON DELETE CASCADE,
  user_id       BIGINT REFERENCES users(id) ON DELETE SET NULL,
  device_token  VARCHAR(64),
  ip_hash       VARCHAR(64),
  title         VARCHAR(100),
  content       TEXT NOT NULL,
  is_anonymous  BOOLEAN NOT NULL DEFAULT FALSE,
  word_count    INT NOT NULL DEFAULT 0,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT uq_story_user_per_day UNIQUE (prompt_id, user_id),
  CONSTRAINT uq_story_device_per_day UNIQUE (prompt_id, device_token)
);

CREATE INDEX IF NOT EXISTS idx_stories_prompt_created
  ON stories(prompt_id, created_at DESC);

-- FLOWERS (likes)
CREATE TABLE IF NOT EXISTS flowers (
  id         BIGSERIAL PRIMARY KEY,
  story_id   BIGINT NOT NULL REFERENCES stories(id) ON DELETE CASCADE,
  user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  value      SMALLINT NOT NULL DEFAULT 1 CHECK (value IN (1)),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT uq_flower UNIQUE (story_id, user_id)
);

-- WIDOK: story score
CREATE OR REPLACE VIEW vw_story_score AS
SELECT s.id AS story_id, COALESCE(SUM(f.value), 0) AS score
FROM stories s
LEFT JOIN flowers f ON f.story_id = s.id
GROUP BY s.id;

-- FUNCTION + TRIGGER: quote validation + word count
CREATE OR REPLACE FUNCTION trg_stories_validate_and_count()
RETURNS trigger AS $$
DECLARE q TEXT;
BEGIN
  SELECT sentence INTO q FROM daily_prompt WHERE id = NEW.prompt_id;

  IF q IS NULL THEN
    RAISE EXCEPTION 'The sentence % does not exist', NEW.prompt_id;
  END IF;

  IF position(q IN NEW.content) = 0 THEN
    RAISE EXCEPTION 'Content must include the prompt sentence: "%"', q;
  END IF;

  NEW.word_count := array_length(regexp_split_to_array(trim(NEW.content), '\s+'), 1);
  IF NEW.word_count > 500 THEN
    RAISE EXCEPTION 'Too many words (%/500)', NEW.word_count;
  END IF;

  RETURN NEW;
END; $$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS stories_validate_and_count ON stories;
CREATE TRIGGER stories_validate_and_count
BEFORE INSERT OR UPDATE ON stories
FOR EACH ROW EXECUTE FUNCTION trg_stories_validate_and_count();