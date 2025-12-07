-- ========= USERS =========
INSERT INTO users (username, password_hash, role) VALUES
  ('admin',  '$2y$10$nDpNHgZG0b4E3CvBmDCmueCpgMdUssSlBfpkqfTwq9QdFvAp11lbe', 'admin'),
  ('alice',  '$2y$10$u8seQz2w5N4uu.i6s5dxYOItnNWlNyzfn.ZhgnDu50z7kJNu2Xxfy', 'user'),
  ('bob',    '$2y$10$5BbM488Z144v9oRL2ftNuuscpHz4F.7.fOhbHGTlaBakhri1tE/ce', 'user'),
  ('charlie','$2y$10$u8seQz2w5N4uu.i6s5dxYOItnNWlNyzfn.ZhgnDu50z7kJNu2Xxfy', 'user'),
  ('diana',  '$2y$10$5BbM488Z144v9oRL2ftNuuscpHz4F.7.fOhbHGTlaBakhri1tE/ce', 'user'),
  ('erin',   '$2y$10$u8seQz2w5N4uu.i6s5dxYOItnNWlNyzfn.ZhgnDu50z7kJNu2Xxfy', 'user')
ON CONFLICT (username) DO NOTHING;

-- ========= DAILY PROMPTS =========
INSERT INTO daily_prompt("date", sentence, source_book, source_author) VALUES
  ('2025-11-21', 'It was a bright cold day in April, and the clocks were striking thirteen.', '1984', 'George Orwell'),
  ('2025-11-22', 'Call me Ishmael.', 'Moby-Dick', 'Herman Melville'),
  ('2025-11-23', 'All this happened, more or less.', 'Slaughterhouse-Five', 'Kurt Vonnegut'),
  ('2025-11-24', 'It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife.', 'Pride and Prejudice', 'Jane Austen'),
  ('2025-11-25', 'All happy families are alike; each unhappy family is unhappy in its own way.', 'Anna Karenina', 'Leo Tolstoy'),
  ('2025-11-26', 'It was the best of times, it was the worst of times.', 'A Tale of Two Cities', 'Charles Dickens'),
  ('2025-11-27', 'In a hole in the ground there lived a hobbit.', 'The Hobbit', 'J.R.R. Tolkien'),
  ('2025-11-28', 'The man in black fled across the desert, and the gunslinger followed.', 'The Gunslinger', 'Stephen King'),
  ('2025-11-29', 'The sky above the port was the color of television, tuned to a dead channel.', 'Neuromancer', 'William Gibson'),
  ('2025-11-30', 'We were somewhere around Barstow on the edge of the desert when the drugs began to take hold.', 'Fear and Loathing in Las Vegas', 'Hunter S. Thompson'),
  ('2025-12-01', 'The past is a foreign country: they do things differently there.', 'The Go-Between', 'L. P. Hartley'),
  ('2025-12-02', 'Mother died today.', 'The Stranger', 'Albert Camus')
ON CONFLICT ("date") DO NOTHING;

-- ========= STORIES =========
-- 2025-11-21 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-21'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Thirteen O''Clock',
' It was a bright cold day in April, and the clocks were striking thirteen. It felt like the whole world had slipped sideways, and Alice decided that if the clocks could break the rules, maybe she could too.',
  FALSE,
  '2025-11-21 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-21 — Bob
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-21'
),
u AS (
  SELECT id FROM users WHERE username = 'bob'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Thirteen O''Clock',
  ' It was a bright cold day in April, and the clocks were striking thirteen. Bob glanced at his watch, once again confirming the impossible time. He chuckled to himself, wondering if the universe was playing a joke on him.',
  FALSE,
  '2025-11-21 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-21 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-21'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Thirteen O''Clock',
  ' It was a bright cold day in April, and the clocks were striking thirteen. Shut up, I want to sleep.',
  FALSE,
  '2025-11-21 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-22 — Bob
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-22'
),
u AS (
  SELECT id FROM users WHERE username = 'bob'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Another Ishmael',
  ' Call me Ishmael. That was the name I gave them, anyway, when the ship''s lights flickered and the sea turned the color of static.',
  TRUE,
  '2025-11-22 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-22 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-22'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Another Ishmael',
  ' Call me Ishmael. No. Said Hamlet.',
  TRUE,
  '2025-11-22 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-22 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-22'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Another Ishmael part 2',
  ' Call me Ishmael. Ishmael was actually my code name back in the day when I was part of that secret project. But that is a story for another time.',
  TRUE,
  '2025-11-22 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-23 — Charlie
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-23'
),
u AS (
  SELECT id FROM users WHERE username = 'charlie'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'More or Less',
  ' All this happened, more or less. The logs disagreed, the cameras glitched, and yet the feeling that something had shifted was painfully real.',
  FALSE,
  '2025-11-23 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-23 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-23'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'More or Less',
  'All this happened, more or less. Somewhat. I mean maybe. Sorta. Dunno. The logs disagreed.',
  FALSE,
  '2025-11-23 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-23 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-23'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Less or More',
  'All this happened, more or less. Or maybe less. Definitely less. Definitely not more. Definitely not happening again.',
  FALSE,
  '2025-11-23 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-24 — Diana
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-24'
),
u AS (
  SELECT id FROM users WHERE username = 'diana'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Truth Universally Acknowledged',
  ' It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife. Diana copy-pasted it into her essay generator, just to see what the algorithm would do with something so famously human.',
  FALSE,
  '2025-11-24 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-24 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-24'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Truth Universally Acknowledged',
  ' It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife. Really? Why? Said the AI. Humans are strange.',
  FALSE,
  '2025-11-24 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-24 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-24'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Truth Universally Denied',
  ' It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife. Said no one ever.',
  FALSE,
  '2025-11-24 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-25 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-25'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Families of Code',
  ' All happy families are alike; each unhappy family is unhappy in its own way. In her debugger, every failing test case felt like another unhappy branch of the same family tree.',
  FALSE,
  '2025-11-25 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-25 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-25'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Families of Code',
  ' All happy families are alike; each unhappy family is unhappy in its own way. In her debugger- in her what now- every failing test case felt like another unhappy branch of the same family tree.',
  FALSE,
  '2025-11-25 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-26 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-26'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Worst of Times, Best of Logs',
  'It was the best of times, it was the worst of times. The commit history proved it: every disaster in production had birthed a tiny improvement no one noticed.',
  FALSE,
  '2025-11-26 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-27 — Bob
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-27'
),
u AS (
  SELECT id FROM users WHERE username = 'bob'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Hole in the Ground',
  'In a hole in the ground there lived a hobbit. The hobbit in question, however, preferred to call it an off-grid co-working space.',
  TRUE,
  '2025-11-27 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-27 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-27'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Hobbit story',
  'In a hole in the ground there lived a hobbit. Let him drink his tea in peace.',
  TRUE,
  '2025-11-27 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-28 — Charlie
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-28'
),
u AS (
  SELECT id FROM users WHERE username = 'charlie'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Gunslinger.exe',
  'The man in black fled across the desert, and the gunslinger followed. In the logs, the man in black was only an IP address, but the pursuit felt just as real.',
  FALSE,
  '2025-11-28 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-28 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-28'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Gunslinger.sh',
  'The man in black fled across the desert, and the gunslinger followed. Actually, it was just a shell script automating the chase, but the adrenaline was real enough.',
  FALSE,
  '2025-11-28 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-11-28 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-28'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Gunslinger.exe',
  'The man in black fled across the desert, and the gunslinger followed. But then, the gunshots revealed he was only an IP address in the logs. The gunshots were real enough, though. Stop shotting me.',
  FALSE,
  '2025-11-28 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-29 — Diana
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-29'
),
u AS (
  SELECT id FROM users WHERE username = 'diana'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Dead Channel',
  'The sky above the port was the color of television, tuned to a dead channel. She minimized the terminal, wondering when exactly the real world had started to look more artificial than the screen.',
  FALSE,
  '2025-11-29 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-11-30 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-11-30'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Barstow Logs',
  'We were somewhere around Barstow on the edge of the desert when the drugs began to take hold. Somewhere between the first bug report and the last diff, she realized the project had taken hold of her life.',
  TRUE,
  '2025-11-30 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-12-01 — Erin
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-12-01'
),
u AS (
  SELECT id FROM users WHERE username = 'erin'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Foreign Country',
  'The past is a foreign country: they do things differently there. What the hell was going on then? She scrolled through her old code repositories, feeling like an anthropologist studying a lost civilization.',
  FALSE,
  '2025-12-01 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-12-01 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-12-01'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Foreign Country',
  'The past is a foreign country: they do things differently there. Her childhood chat logs read like messages from another planet, written by someone who barely resembled her.',
  FALSE,
  '2025-12-01 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-12-01 — Bob
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-12-01'
),
u AS (
  SELECT id FROM users WHERE username = 'bob'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Foreign Country',
  'The past is a foreign country: they do things differently there. Back then, people still used keyboards and screens instead of neural interfaces. Weird, right?',
  FALSE,
  '2025-12-01 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;


-- 2025-12-02 — Bob
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-12-02'
),
u AS (
  SELECT id FROM users WHERE username = 'bob'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Mother Died Today',
  'Mother died today. The notification popped up between calendar reminders and CI/CD alerts, cruelly ordinary in its formatting.',
  TRUE,
  '2025-12-02 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;

-- 2025-12-02 — Alice
WITH p AS (
  SELECT id, sentence FROM daily_prompt WHERE "date" = '2025-12-02'
),
u AS (
  SELECT id FROM users WHERE username = 'alice'
)
INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, created_at)
SELECT
  p.id,
  u.id,
  'Mother Died Today, again',
  'Mother died today. I wish she would have stopped doing that. It is unsettling.',
  TRUE,
  '2025-12-02 19:35:41.279561+00'
FROM p, u
ON CONFLICT DO NOTHING;



-- ========= FLOWERS =========

INSERT INTO flowers (story_id, user_id, value)
SELECT s.id, u.id, 1
FROM stories s
JOIN users u ON u.username IN ('admin', 'charlie', 'diana')
WHERE s.title IN ('Thirteen O''Clock','Mother Died Today','Dead Channel', 
'Worst of Times, Best of Logs', 'Gunslinger.exe', 
  'Truth Universally Acknowledged', 'Families of Code')
ON CONFLICT DO NOTHING;

INSERT INTO flowers (story_id, user_id, value)
SELECT s.id, u.id, 1
FROM stories s
JOIN users u ON u.username IN ('alice','bob, charlie', 'erin')
WHERE s.title IN ('Foreign Country','Barstow Logs', 'Hole in the Ground', 
  'Truth Universally Acknowledged', 'Families of Code', 'Another Ishmael', 
  'Gunslinger.sh', 'More or Less', 'Thirteen O''Clock','Another Ishmael part 2')
ON CONFLICT DO NOTHING;

INSERT INTO flowers (story_id, user_id, value)
SELECT s.id, u.id, 1
FROM stories s
JOIN users u ON u.username IN ('diana','erin', 'bob', 'admin')
WHERE s.title IN ('Less or More', 'Foreign Country','Barstow Logs', 'Worst of Times, Best of Logs',
'Another Ishmael part 2', 'Gunslinger.exe', 'Another Ishmael', 'More or Less')
ON CONFLICT DO NOTHING;