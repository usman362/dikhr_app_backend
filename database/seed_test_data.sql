-- =====================================================================
-- Community Dhikr — Test Data Seeder (SQL version)
-- Run this on the live database (phpMyAdmin / any SQL console).
-- Safe to re-run: idempotent (won't create duplicates).
--
-- Creates:
--   • 20 test users (password: "password")
--   • 4 global Ummah campaigns
--   • 2 groups with invite codes + members + group campaigns
--   • ~240 global + ~70 group dhikr contributions over last 7 days
--
-- ──────────────────────────────────────────────────────────────────
-- TEST LOGIN CREDENTIALS (for client):
--   Email:    ahmad.hassan@testdhikr.com     (group admin of Masjid Al-Noor)
--   Email:    fatima.zahra@testdhikr.com     (group admin of Sisters Circle)
--   Password: password
--
-- GROUP INVITE CODES (to test join-by-code):
--   NOOR2026    → Masjid Al-Noor Community
--   SISTER26    → Sisters Circle of Dhikr
-- ──────────────────────────────────────────────────────────────────
--
-- To WIPE test data and re-run, uncomment this block first:
-- DELETE FROM dhikr_contributions WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@testdhikr.com');
-- DELETE FROM campaigns WHERE created_by IN (SELECT id FROM users WHERE email LIKE '%@testdhikr.com');
-- DELETE FROM group_user WHERE group_id IN (SELECT id FROM `groups` WHERE created_by IN (SELECT id FROM users WHERE email LIKE '%@testdhikr.com'));
-- DELETE FROM `groups` WHERE created_by IN (SELECT id FROM users WHERE email LIKE '%@testdhikr.com');
-- DELETE FROM users WHERE email LIKE '%@testdhikr.com';
-- =====================================================================


-- ── 1. Users (20) — idempotent via unique email ──────────────────────
INSERT IGNORE INTO users (name, email, password, is_admin, created_at, updated_at) VALUES
('Ahmad Hassan',     'ahmad.hassan@testdhikr.com',     '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Fatima Zahra',     'fatima.zahra@testdhikr.com',     '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Omar Farooq',      'omar.farooq@testdhikr.com',      '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Aisha Rahman',     'aisha.rahman@testdhikr.com',     '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Yusuf Ali',        'yusuf.ali@testdhikr.com',        '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Khadijah Noor',    'khadijah.noor@testdhikr.com',    '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Ibrahim Malik',    'ibrahim.malik@testdhikr.com',    '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Maryam Siddiqui',  'maryam.siddiqui@testdhikr.com',  '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Bilal Khan',       'bilal.khan@testdhikr.com',       '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Hafsa Begum',      'hafsa.begum@testdhikr.com',      '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Zaid Ahmed',       'zaid.ahmed@testdhikr.com',       '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Sumaya Patel',     'sumaya.patel@testdhikr.com',     '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Hamza Sheikh',     'hamza.sheikh@testdhikr.com',     '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Safiya Qureshi',   'safiya.qureshi@testdhikr.com',   '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Ismail Hussain',   'ismail.hussain@testdhikr.com',   '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Ruqayyah Javed',   'ruqayyah.javed@testdhikr.com',   '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Abdullah Mirza',   'abdullah.mirza@testdhikr.com',   '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Amina Chowdhury',  'amina.chowdhury@testdhikr.com',  '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Tariq Aziz',       'tariq.aziz@testdhikr.com',       '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW()),
('Layla Mahmoud',    'layla.mahmoud@testdhikr.com',    '$2y$12$RoAICXa7XEqYtWwLXeGsReuH.ICbtFUCuuKbYYnBPfpbR3x3U1Rjm', 0, NOW(), NOW());


-- ── 2. Global (Ummah) Campaigns — idempotent via WHERE NOT EXISTS ────
INSERT INTO campaigns (group_id, title, description, target_count, starts_at, ends_at, status, created_by, created_at, updated_at)
SELECT NULL, 'Ramadan 2026 — 1 Million SubhanAllah',
       'Let us come together as an Ummah to complete 1 million SubhanAllah during this blessed month.',
       1000000, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active',
       (SELECT id FROM users WHERE email = 'ahmad.hassan@testdhikr.com'), NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM campaigns WHERE title = 'Ramadan 2026 — 1 Million SubhanAllah' AND group_id IS NULL);

INSERT INTO campaigns (group_id, title, description, target_count, starts_at, ends_at, status, created_by, created_at, updated_at)
SELECT NULL, 'Jumu''ah Dhikr Challenge',
       'A weekly challenge to increase our collective remembrance of Allah on the best day of the week.',
       100000, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active',
       (SELECT id FROM users WHERE email = 'ahmad.hassan@testdhikr.com'), NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM campaigns WHERE title = 'Jumu''ah Dhikr Challenge' AND group_id IS NULL);

INSERT INTO campaigns (group_id, title, description, target_count, starts_at, ends_at, status, created_by, created_at, updated_at)
SELECT NULL, '100K Astaghfirullah',
       'Seek forgiveness together — 100,000 Astaghfirullah as a community.',
       100000, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active',
       (SELECT id FROM users WHERE email = 'ahmad.hassan@testdhikr.com'), NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM campaigns WHERE title = '100K Astaghfirullah' AND group_id IS NULL);

INSERT INTO campaigns (group_id, title, description, target_count, starts_at, ends_at, status, created_by, created_at, updated_at)
SELECT NULL, 'Global La ilaha illallah',
       'Unite the Ummah in the declaration of Tawheed — half a million La ilaha illallah.',
       500000, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active',
       (SELECT id FROM users WHERE email = 'ahmad.hassan@testdhikr.com'), NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM campaigns WHERE title = 'Global La ilaha illallah' AND group_id IS NULL);


-- ── 3. Groups (2) with invite codes — idempotent via WHERE NOT EXISTS ─
INSERT INTO `groups` (name, description, created_by, invite_code, created_at, updated_at)
SELECT 'Masjid Al-Noor Community',
       'Daily dhikr circle for the Al-Noor congregation.',
       (SELECT id FROM users WHERE email = 'ahmad.hassan@testdhikr.com'),
       'NOOR2026', NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE name = 'Masjid Al-Noor Community');

INSERT INTO `groups` (name, description, created_by, invite_code, created_at, updated_at)
SELECT 'Sisters Circle of Dhikr',
       'A supportive space for sisters to track and encourage dhikr together.',
       (SELECT id FROM users WHERE email = 'fatima.zahra@testdhikr.com'),
       'SISTER26', NOW(), NOW()
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE name = 'Sisters Circle of Dhikr');


-- ── 4. Group Memberships — idempotent via unique (group_id, user_id) ──
INSERT IGNORE INTO group_user (group_id, user_id, role, created_at, updated_at)
SELECT g.id, u.id,
       CASE WHEN u.email = 'ahmad.hassan@testdhikr.com' THEN 'admin' ELSE 'member' END,
       NOW(), NOW()
FROM `groups` g
JOIN users u ON u.email IN (
  'ahmad.hassan@testdhikr.com','fatima.zahra@testdhikr.com','omar.farooq@testdhikr.com',
  'aisha.rahman@testdhikr.com','yusuf.ali@testdhikr.com','khadijah.noor@testdhikr.com',
  'ibrahim.malik@testdhikr.com','maryam.siddiqui@testdhikr.com','bilal.khan@testdhikr.com',
  'hafsa.begum@testdhikr.com'
)
WHERE g.name = 'Masjid Al-Noor Community';

INSERT IGNORE INTO group_user (group_id, user_id, role, created_at, updated_at)
SELECT g.id, u.id,
       CASE WHEN u.email = 'fatima.zahra@testdhikr.com' THEN 'admin' ELSE 'member' END,
       NOW(), NOW()
FROM `groups` g
JOIN users u ON u.email IN (
  'fatima.zahra@testdhikr.com','aisha.rahman@testdhikr.com','yusuf.ali@testdhikr.com',
  'khadijah.noor@testdhikr.com','ibrahim.malik@testdhikr.com','maryam.siddiqui@testdhikr.com',
  'bilal.khan@testdhikr.com','hafsa.begum@testdhikr.com'
)
WHERE g.name = 'Sisters Circle of Dhikr';


-- ── 5. Group Campaigns (2) — idempotent via WHERE NOT EXISTS ─────────
INSERT INTO campaigns (group_id, title, description, target_count, starts_at, ends_at, status, created_by, created_at, updated_at)
SELECT g.id,
       CONCAT(g.name, ' Daily Dhikr'),
       CONCAT('Daily dhikr goal for ', g.name),
       10000,
       DATE_SUB(NOW(), INTERVAL 7 DAY),
       DATE_ADD(NOW(), INTERVAL 30 DAY),
       'active',
       g.created_by, NOW(), NOW()
FROM `groups` g
WHERE g.name IN ('Masjid Al-Noor Community', 'Sisters Circle of Dhikr')
  AND NOT EXISTS (
    SELECT 1 FROM campaigns c
    WHERE c.group_id = g.id AND c.title = CONCAT(g.name, ' Daily Dhikr')
  );


-- ── 6. Dhikr Contributions ───────────────────────────────────────────
-- Only seed contributions if none yet exist for the test campaigns
-- (prevents duplicate piles on re-run).

-- Global contributions: 20 users × 4 campaigns × 3 rounds = 240 rows
INSERT INTO dhikr_contributions (user_id, campaign_id, count, created_at, updated_at)
SELECT u.id, c.id,
       ELT(FLOOR(1 + RAND() * 8), 33, 33, 33, 33, 99, 100, 500, 1000),
       DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 10080) MINUTE),
       NOW()
FROM users u
CROSS JOIN campaigns c
CROSS JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3) x
WHERE u.email LIKE '%@testdhikr.com'
  AND c.group_id IS NULL
  AND c.title IN (
    'Ramadan 2026 — 1 Million SubhanAllah',
    'Jumu''ah Dhikr Challenge',
    '100K Astaghfirullah',
    'Global La ilaha illallah'
  )
  AND NOT EXISTS (
    SELECT 1 FROM dhikr_contributions dc WHERE dc.campaign_id = c.id LIMIT 1
  );

-- Group contributions: each group's members × 4 rounds
INSERT INTO dhikr_contributions (user_id, campaign_id, count, created_at, updated_at)
SELECT gu.user_id, c.id,
       ELT(FLOOR(1 + RAND() * 8), 33, 33, 33, 33, 99, 100, 500, 1000),
       DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 10080) MINUTE),
       NOW()
FROM campaigns c
JOIN group_user gu ON gu.group_id = c.group_id
CROSS JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) x
WHERE c.group_id IS NOT NULL
  AND c.title IN ('Masjid Al-Noor Community Daily Dhikr', 'Sisters Circle of Dhikr Daily Dhikr')
  AND NOT EXISTS (
    SELECT 1 FROM dhikr_contributions dc WHERE dc.campaign_id = c.id LIMIT 1
  );


-- ── 7. Clear cached global stats so the app shows fresh totals ───────
DELETE FROM cache WHERE `key` LIKE '%global_stats%';


-- ── Done! Verify with: ───────────────────────────────────────────────
-- SELECT COUNT(*) AS test_users     FROM users WHERE email LIKE '%@testdhikr.com';
-- SELECT COUNT(*) AS global_camps   FROM campaigns WHERE group_id IS NULL AND status='active';
-- SELECT COUNT(*) AS group_camps    FROM campaigns WHERE group_id IS NOT NULL AND status='active';
-- SELECT COUNT(*) AS contributions  FROM dhikr_contributions;
-- SELECT name, invite_code FROM `groups` WHERE invite_code IN ('NOOR2026','SISTER26');
