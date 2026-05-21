BEGIN;

CREATE OR REPLACE VIEW vw_story_score AS
SELECT s.id AS story_id, COALESCE(SUM(f.value), 0) AS score
FROM stories s
LEFT JOIN flowers f ON f.story_id = s.id
GROUP BY s.id;

CREATE OR REPLACE VIEW vw_stories_with_score AS
SELECT
  s.id,
  s.public_id AS story_public_id,
  s.prompt_id,
  s.user_id,
  s.title,
  s.content,
  s.word_count,
  s.is_anonymous,
  s.visibility,
  s.created_at,
  CASE
    WHEN s.is_anonymous OR s.user_id IS NULL THEN NULL
    ELSE up.display_name
  END AS username,
  CASE
    WHEN s.is_anonymous OR s.user_id IS NULL THEN NULL
    ELSE up.public_id
  END AS user_public_id,
  COALESCE(v.score, 0) AS score
FROM stories s
LEFT JOIN vw_story_score v ON v.story_id = s.id
LEFT JOIN user_profiles up ON up.user_id = s.user_id;

CREATE OR REPLACE VIEW vw_public_stories_with_score AS
SELECT *
FROM vw_stories_with_score
WHERE visibility = 'public';

CREATE OR REPLACE VIEW vw_today_stories AS
SELECT *
FROM vw_public_stories_with_score
WHERE prompt_id = (SELECT id FROM daily_prompt WHERE "date" = CURRENT_DATE);

CREATE OR REPLACE FUNCTION fn_touch_user_profiles_updated_at()
RETURNS trigger AS $$
BEGIN
  NEW.updated_at := now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS user_profiles_touch_updated_at ON user_profiles;
CREATE TRIGGER user_profiles_touch_updated_at
BEFORE UPDATE ON user_profiles
FOR EACH ROW EXECUTE FUNCTION fn_touch_user_profiles_updated_at();

CREATE OR REPLACE FUNCTION fn_add_story(
  p_prompt_id BIGINT,
  p_user_id INTEGER,
  p_title VARCHAR,
  p_content TEXT,
  p_is_anonymous BOOLEAN DEFAULT FALSE,
  p_visibility VARCHAR DEFAULT 'public'
)
RETURNS BIGINT AS $$
DECLARE new_id BIGINT;
BEGIN
  INSERT INTO stories(prompt_id, user_id, title, content, is_anonymous, visibility)
  VALUES (p_prompt_id, p_user_id, p_title, p_content, p_is_anonymous, p_visibility)
  RETURNING id INTO new_id;

  RETURN new_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION fn_get_prompt(p_date DATE)
RETURNS TABLE(id BIGINT, "date" DATE, sentence TEXT, source_book TEXT, source_author TEXT) AS $$
BEGIN
  RETURN QUERY
  SELECT dp.id, dp."date", dp.sentence, dp.source_book, dp.source_author
  FROM daily_prompt dp
  WHERE dp."date" = p_date;
END;
$$ LANGUAGE plpgsql STABLE;

CREATE OR REPLACE FUNCTION fn_ensure_today_prompt(
  p_sentence TEXT DEFAULT NULL,
  p_source_book TEXT DEFAULT NULL,
  p_source_author TEXT DEFAULT NULL
)
RETURNS BIGINT AS $$
DECLARE new_id BIGINT;
BEGIN
  SELECT id INTO new_id FROM daily_prompt WHERE "date" = CURRENT_DATE;
  IF new_id IS NOT NULL THEN
    RETURN new_id;
  END IF;

  IF p_sentence IS NULL THEN
    RAISE EXCEPTION 'No prompt exists for today and no sentence was provided.';
  END IF;

  INSERT INTO daily_prompt("date", sentence, source_book, source_author)
  VALUES (CURRENT_DATE, p_sentence, p_source_book, p_source_author)
  RETURNING id INTO new_id;

  RETURN new_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION trg_stories_validate_and_count()
RETURNS trigger AS $$
DECLARE q TEXT;
BEGIN
  SELECT sentence INTO q FROM daily_prompt WHERE id = NEW.prompt_id;

  IF TG_OP = 'INSERT' AND NEW.user_id IS NULL THEN
    RAISE EXCEPTION 'Story owner is required.';
  END IF;

  IF q IS NULL THEN
    RAISE EXCEPTION 'The prompt % does not exist', NEW.prompt_id;
  END IF;

  IF position(lower(q) IN lower(NEW.content)) = 0 THEN
    RAISE EXCEPTION 'Content must include the prompt sentence.';
  END IF;

  NEW.word_count := COALESCE(array_length(regexp_split_to_array(trim(NEW.content), '\s+'), 1), 0);
  IF NEW.word_count > 500 THEN
    RAISE EXCEPTION 'Too many words (%/500)', NEW.word_count;
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS stories_validate_and_count ON stories;
CREATE TRIGGER stories_validate_and_count
BEFORE INSERT OR UPDATE ON stories
FOR EACH ROW EXECUTE FUNCTION trg_stories_validate_and_count();

COMMIT;
