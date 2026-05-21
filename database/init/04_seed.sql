BEGIN;

INSERT INTO daily_prompt("date", sentence, source_book, source_author) VALUES
  ('2026-05-15', 'It was a bright cold day in April, and the clocks were striking thirteen.', '1984', 'George Orwell'),
  ('2026-05-16', 'Call me Ishmael.', 'Moby-Dick', 'Herman Melville'),
  ('2026-05-17', 'All this happened, more or less.', 'Slaughterhouse-Five', 'Kurt Vonnegut'),
  ('2026-05-18', 'It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife.', 'Pride and Prejudice', 'Jane Austen'),
  ('2026-05-19', 'All happy families are alike; each unhappy family is unhappy in its own way.', 'Anna Karenina', 'Leo Tolstoy')
ON CONFLICT ("date") DO NOTHING;

COMMIT;
