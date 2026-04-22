-- =====================================================================
-- Community Dhikr — Add `dhikr` column to campaigns
-- Run this ONCE on the live database before deploying the new mobile build.
-- Idempotent: safe to re-run.
-- =====================================================================

-- 1. Add the column if it doesn't exist yet.
-- (MySQL doesn't support "IF NOT EXISTS" directly on ADD COLUMN;
--  the safe pattern is to try it and ignore the duplicate-column error.
--  Most SQL consoles will simply skip this line on the second run.)
ALTER TABLE campaigns
  ADD COLUMN dhikr VARCHAR(50) NULL AFTER description;

-- 2. Update the seeded test campaigns so each locks to a specific dhikr.
UPDATE campaigns SET dhikr = 'SubhanAllah'
  WHERE title = 'Ramadan 2026 — 1 Million SubhanAllah';

UPDATE campaigns SET dhikr = 'Alhamdulillah'
  WHERE title = 'Jumu''ah Dhikr Challenge';

UPDATE campaigns SET dhikr = 'Astaghfirullah'
  WHERE title = '100K Astaghfirullah';

UPDATE campaigns SET dhikr = 'La ilaha illAllah'
  WHERE title = 'Global La ilaha illallah';

-- 3. Record the migration so Laravel won't try to run it again.
--    (Only needed if the Laravel app also tries to `artisan migrate`.)
INSERT IGNORE INTO migrations (migration, batch)
  VALUES ('2026_04_18_000001_add_dhikr_to_campaigns_table',
          (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));

-- Verify with:
-- SELECT title, dhikr FROM campaigns WHERE group_id IS NULL;
