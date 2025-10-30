ALTER TABLE stories
ADD COLUMN public_id uuid UNIQUE DEFAULT gen_random_uuid();

UPDATE stories SET public_id = gen_random_uuid() WHERE public_id IS NULL;

ALTER TABLE stories ALTER COLUMN public_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS idx_stories_public_id ON stories (public_id);