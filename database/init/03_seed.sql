-- ===== TEST DATA =====

-- Users
INSERT INTO users (username, password_hash, role) VALUES
  ('admin', '$2y$10$nDpNHgZG0b4E3CvBmDCmueCpgMdUssSlBfpkqfTwq9QdFvAp11lbe', 'admin')
ON CONFLICT (username) DO NOTHING;

INSERT INTO users (username, password_hash, role) VALUES
  ('alice', '$2y$10$u8seQz2w5N4uu.i6s5dxYOItnNWlNyzfn.ZhgnDu50z7kJNu2Xxfy', 'user'),
  ('bob',   '$2y$10$5BbM488Z144v9oRL2ftNuuscpHz4F.7.fOhbHGTlaBakhri1tE/ce', 'user')
ON CONFLICT (username) DO NOTHING;

-- Quotes
INSERT INTO daily_prompt ("date", sentence, source_book, source_author)
VALUES
  (CURRENT_DATE - INTERVAL '1 day', 'It was a bright cold day in April, and the clocks were striking thirteen.', 'Nineteen Eighty-Four', 'George Orwell')
ON CONFLICT ("date") DO NOTHING;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM daily_prompt WHERE "date" = CURRENT_DATE) THEN
    INSERT INTO daily_prompt("date", sentence, source_book, source_author)
    VALUES (CURRENT_DATE, 'Write your story here and do not forget this exact sentence.', 'StoryLine', 'System');
  END IF;
END; $$;

-- Stories
WITH y AS (
  SELECT id AS prompt_id FROM daily_prompt WHERE "date" = CURRENT_DATE - INTERVAL '1 day'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous)
SELECT y.prompt_id, u.id, 'Shadows of April',
'It was a bright cold day in April, and the clocks were striking thirteen. I remember the air tasting like tin as I crossed the empty square...',
FALSE
FROM y JOIN users u ON u.username = 'alice'
ON CONFLICT DO NOTHING;

WITH y AS (
  SELECT id AS prompt_id FROM daily_prompt WHERE "date" = CURRENT_DATE - INTERVAL '1 day'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous)
SELECT y.prompt_id, u.id, 'Thirteen',
'…and, yes—It was a bright cold day in April, and the clocks were striking thirteen. That''s when the terminal woke up.',
TRUE
FROM y JOIN users u ON u.username = 'bob'
ON CONFLICT DO NOTHING;

-- Some flowers
INSERT INTO flowers (story_id, user_id, value)
SELECT s.id, u.id, 1
FROM stories s
JOIN users u ON u.username IN ('admin','alice')
WHERE s.title = 'Thirteen'
ON CONFLICT DO NOTHING;

INSERT INTO flowers (story_id, user_id, value)
SELECT s.id, u.id, 1
FROM stories s
JOIN users u ON u.username IN ('admin')
WHERE s.title = 'Shadows of April'
ON CONFLICT DO NOTHING;