-- 09_schema_upgrade.sql

CREATE INDEX IF NOT EXISTS idx_stories_user ON stories(user_id);
CREATE INDEX IF NOT EXISTS idx_flowers_story ON flowers(story_id);
CREATE INDEX IF NOT EXISTS idx_flowers_user ON flowers(user_id);
CREATE INDEX IF NOT EXISTS idx_password_reset_user ON password_reset_tokens(user_id);

ALTER TABLE users ALTER COLUMN public_id SET NOT NULL;

