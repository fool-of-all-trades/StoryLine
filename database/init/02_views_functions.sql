-- 1) View: story score
CREATE OR REPLACE VIEW vw_story_score AS
SELECT s.id AS story_id, COALESCE(SUM(f.value), 0) AS score
FROM stories s
LEFT JOIN flowers f ON f.story_id = s.id
GROUP BY s.id;

-- 2) View: stories with score (easier to fetch lists)
CREATE OR REPLACE VIEW vw_stories_with_score AS
SELECT
  s.id,
  s.prompt_id,
  s.user_id,
  s.title,
  s.content,
  s.word_count,
  s.is_anonymous,
  s.created_at,
  COALESCE(v.score, 0) AS score
FROM stories s
LEFT JOIN vw_story_score v ON v.story_id = s.id;

-- 3) View: today's stories
CREATE OR REPLACE VIEW vw_today_stories AS
SELECT *
FROM vw_stories_with_score
WHERE prompt_id = (SELECT id FROM daily_prompt WHERE "date" = CURRENT_DATE);


-- 4) Function: safe addition of a story for a prompt and user (1/day)
-- Assumes content contains the exact quote (trigger in 01_schema.sql checks this)
CREATE OR REPLACE FUNCTION fn_add_story(
  p_prompt_id BIGINT,
  p_user_id BIGINT,
  p_title VARCHAR,
  p_content TEXT,
  p_is_anonymous BOOLEAN DEFAULT FALSE
)
RETURNS BIGINT AS $$
DECLARE new_id BIGINT;
BEGIN
  INSERT INTO stories(prompt_id, user_id, title, content, is_anonymous)
  VALUES (p_prompt_id, p_user_id, p_title, p_content, p_is_anonymous)
  RETURNING id INTO new_id;

  RETURN new_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- 5) Function: get quote for a given date
CREATE OR REPLACE FUNCTION fn_get_prompt(p_date DATE)
RETURNS TABLE(id BIGINT, "date" DATE, sentence TEXT, source_book TEXT, source_author TEXT) AS $$
BEGIN
  RETURN QUERY
  SELECT dp.id, dp."date", dp.sentence, dp.source_book, dp.source_author
  FROM daily_prompt dp
  WHERE dp."date" = p_date;
END;
$$ LANGUAGE plpgsql STABLE;

-- 6) Function: ensure today's prompt exists.
-- If exists -> returns existing.
-- If not -> if p_sentence provided, creates and returns; else raises exception.
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
    RAISE EXCEPTION 'Brak promptu na dziś i brak p_sentence do utworzenia.';
  END IF;

  INSERT INTO daily_prompt("date", sentence, source_book, source_author)
  VALUES (CURRENT_DATE, p_sentence, p_source_book, p_source_author)
  RETURNING id INTO new_id;

  RETURN new_id;
END;
$$ LANGUAGE plpgsql VOLATILE;