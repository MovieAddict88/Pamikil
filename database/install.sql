-- Pamikil Learning Platform
-- Vanilla PHP (7.4+) + MySQL (5.7+)
--
-- Relationship diagram (high level)
--
-- users (students/parents/admins)
--   |\
--   | \__ parent_child (parent_id -> users.id, child_id -> users.id)
--   |
--   \__ activity_progress (user_id -> users.id) -> activities
--         |\
--         | \__ coins_ledger (user_id -> users.id)
--         | \__ user_badges (user_id -> users.id) -> badges
--         | \__ activity_autosave
--         | \__ student_daily_usage
--         \__ user_activity_unlocks
--
-- activities -> categories
--   |\
--   | \__ activity_age_groups
--   | \__ activity_tags -> tags
--   \__ activity_prerequisites (unlock mechanism)
--
-- users (student) -> user_avatar -> avatar_items
-- users (student) -> user_avatar_items -> avatar_items
--
-- admins -> admin_logs
-- students -> certificates

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create database manually in your hosting panel, then select it:
-- USE pamikil;

DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS student_daily_usage;
DROP TABLE IF EXISTS parent_controls;
DROP TABLE IF EXISTS user_avatar_items;
DROP TABLE IF EXISTS user_avatar;
DROP TABLE IF EXISTS avatar_items;
DROP TABLE IF EXISTS coins_ledger;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS activity_autosave;
DROP TABLE IF EXISTS activity_progress;
DROP TABLE IF EXISTS user_activity_unlocks;
DROP TABLE IF EXISTS activity_prerequisites;
DROP TABLE IF EXISTS activity_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS activity_age_groups;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS parent_child;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('student','parent','admin') NOT NULL,
  username VARCHAR(24) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(64) NULL,
  age_group ENUM('3-5','6-8','9-12') NULL,
  coins INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parent_child (
  parent_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (parent_id, child_id),
  KEY idx_pc_child (child_id),
  CONSTRAINT fk_pc_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  type ENUM('quiz','story','dragdrop','flashcards','jigsaw','crossword','image_word') NOT NULL,
  title VARCHAR(140) NOT NULL,
  description VARCHAR(255) NULL,
  difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
  content_json LONGTEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_activities_category (category_id),
  KEY idx_activities_type (type),
  KEY idx_activities_difficulty (difficulty),
  CONSTRAINT fk_activities_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_age_groups (
  activity_id INT UNSIGNED NOT NULL,
  age_group ENUM('3-5','6-8','9-12') NOT NULL,
  PRIMARY KEY (activity_id, age_group),
  KEY idx_aag_age (age_group),
  CONSTRAINT fk_aag_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tag VARCHAR(24) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tags_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_tags (
  activity_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (activity_id, tag_id),
  KEY idx_at_tag (tag_id),
  CONSTRAINT fk_at_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_at_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_prerequisites (
  activity_id INT UNSIGNED NOT NULL,
  prereq_activity_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (activity_id, prereq_activity_id),
  KEY idx_ap_prereq (prereq_activity_id),
  CONSTRAINT fk_ap_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_ap_prereq FOREIGN KEY (prereq_activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_activity_unlocks (
  user_id INT UNSIGNED NOT NULL,
  activity_id INT UNSIGNED NOT NULL,
  unlocked_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, activity_id),
  KEY idx_uau_activity (activity_id),
  CONSTRAINT fk_uau_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_uau_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_progress (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  activity_id INT UNSIGNED NOT NULL,
  status ENUM('in_progress','completed') NOT NULL DEFAULT 'completed',
  score_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stars TINYINT UNSIGNED NOT NULL DEFAULT 1,
  coins_earned INT NOT NULL DEFAULT 0,
  attempts INT UNSIGNED NOT NULL DEFAULT 1,
  started_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  updated_at DATETIME NOT NULL,
  details_json TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_progress_user_activity (user_id, activity_id),
  KEY idx_progress_activity (activity_id),
  KEY idx_progress_user (user_id),
  KEY idx_progress_completed_at (completed_at),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_autosave (
  user_id INT UNSIGNED NOT NULL,
  activity_id INT UNSIGNED NOT NULL,
  state_json LONGTEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, activity_id),
  KEY idx_autosave_updated (updated_at),
  CONSTRAINT fk_autosave_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_autosave_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL,
  name VARCHAR(64) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(8) NOT NULL,
  coin_reward INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_badges_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
  user_id INT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  earned_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, badge_id),
  KEY idx_user_badges_badge (badge_id),
  CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coins_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  delta INT NOT NULL,
  reason VARCHAR(120) NOT NULL,
  ref_type VARCHAR(32) NULL,
  ref_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ledger_user_time (user_id, created_at),
  KEY idx_ledger_time (created_at),
  CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avatar_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slot ENUM('base','hat','shirt','pet','bg') NOT NULL,
  name VARCHAR(64) NOT NULL,
  cost_coins INT NOT NULL DEFAULT 0,
  asset_path VARCHAR(255) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_avatar_slot (slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_avatar (
  user_id INT UNSIGNED NOT NULL,
  base_item_id INT UNSIGNED NULL,
  hat_item_id INT UNSIGNED NULL,
  shirt_item_id INT UNSIGNED NULL,
  pet_item_id INT UNSIGNED NULL,
  bg_item_id INT UNSIGNED NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_user_avatar_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_avatar_base FOREIGN KEY (base_item_id) REFERENCES avatar_items(id),
  CONSTRAINT fk_user_avatar_hat FOREIGN KEY (hat_item_id) REFERENCES avatar_items(id),
  CONSTRAINT fk_user_avatar_shirt FOREIGN KEY (shirt_item_id) REFERENCES avatar_items(id),
  CONSTRAINT fk_user_avatar_pet FOREIGN KEY (pet_item_id) REFERENCES avatar_items(id),
  CONSTRAINT fk_user_avatar_bg FOREIGN KEY (bg_item_id) REFERENCES avatar_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_avatar_items (
  user_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  owned_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, item_id),
  KEY idx_uai_item (item_id),
  CONSTRAINT fk_uai_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_uai_item FOREIGN KEY (item_id) REFERENCES avatar_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parent_controls (
  parent_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  daily_minutes_limit INT NOT NULL DEFAULT 0,
  max_age_group ENUM('3-5','6-8','9-12') NULL,
  allowed_categories_json TEXT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (parent_id, child_id),
  KEY idx_pc_child2 (child_id),
  CONSTRAINT fk_controls_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_controls_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_daily_usage (
  user_id INT UNSIGNED NOT NULL,
  usage_date DATE NOT NULL,
  minutes_used INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, usage_date),
  KEY idx_usage_date (usage_date),
  CONSTRAINT fk_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(140) NOT NULL,
  certificate_code VARCHAR(24) NOT NULL,
  issued_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cert_code (certificate_code),
  KEY idx_cert_user (user_id),
  CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  session_id VARCHAR(128) NOT NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,
  ended_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_sessions_user (user_id),
  KEY idx_sessions_last_seen (last_seen_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(64) NOT NULL,
  details VARCHAR(255) NULL,
  ip_address VARCHAR(64) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_admin_logs_admin (admin_id),
  KEY idx_admin_logs_time (created_at),
  CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data --------------------------------------------------------------

INSERT INTO categories (name, sort_order) VALUES
  ('Math', 1),
  ('Science', 2),
  ('Language Arts', 3),
  ('Social Studies', 4),
  ('Art', 5);

INSERT INTO avatar_items (slot, name, cost_coins, asset_path, is_default, created_at) VALUES
  ('bg', 'Starry Sky', 0, 'assets/img/avatar/bg-stars.svg', 1, NOW()),
  ('bg', 'Green Field', 25, 'assets/img/avatar/bg-green.svg', 0, NOW()),
  ('base', 'Robot Buddy', 0, 'assets/img/avatar/base-robot.svg', 1, NOW()),
  ('base', 'Bear Friend', 50, 'assets/img/avatar/base-bear.svg', 0, NOW()),
  ('hat', 'Red Cap', 20, 'assets/img/avatar/hat-cap.svg', 0, NOW()),
  ('hat', 'Golden Crown', 120, 'assets/img/avatar/hat-crown.svg', 0, NOW()),
  ('shirt', 'Blue T-Shirt', 15, 'assets/img/avatar/shirt-tee.svg', 0, NOW()),
  ('shirt', 'Purple Hoodie', 60, 'assets/img/avatar/shirt-hoodie.svg', 0, NOW()),
  ('pet', 'Bird Pal', 40, 'assets/img/avatar/pet-bird.svg', 0, NOW()),
  ('pet', 'Fish Friend', 35, 'assets/img/avatar/pet-fish.svg', 0, NOW());

INSERT INTO badges (code, name, description, icon, coin_reward, created_at) VALUES
  ('FIRST_STEPS', 'First Steps', 'Complete your first activity.', '🐣', 10, NOW()),
  ('TEN_ACTIVITIES', 'On a Roll', 'Complete 10 activities.', '🏅', 25, NOW()),
  ('FIFTY_ACTIVITIES', 'Learning Legend', 'Complete 50 activities.', '🏆', 100, NOW()),
  ('PERFECT_SCORE', 'Perfect!', 'Get 100% on an activity.', '💯', 20, NOW()),
  ('NEAR_PERFECT_5', 'Star Student', 'Score 95%+ on 5 activities.', '⭐', 30, NOW()),
  ('COIN_COLLECTOR_100', 'Coin Collector', 'Reach 100 total coins.', '🪙', 15, NOW()),
  ('COIN_COLLECTOR_500', 'Coin Vault', 'Reach 500 total coins.', '💰', 50, NOW()),
  ('QUIZ_WIZARD', 'Quiz Wizard', 'Complete 5 quizzes.', '🧠', 20, NOW()),
  ('STORY_EXPLORER', 'Story Explorer', 'Complete 3 stories.', '📖', 20, NOW()),
  ('PUZZLE_BUILDER', 'Puzzle Builder', 'Complete 3 jigsaw puzzles.', '🧩', 20, NOW()),
  ('WORD_MATCHER', 'Word Matcher', 'Complete 5 image-to-word games.', '🔤', 20, NOW()),
  ('STREAK_7_DAYS', '7-Day Streak', 'Complete at least one activity per day for 7 days.', '🔥', 40, NOW()),
  ('EARLY_BIRD', 'Early Bird', 'Complete an activity in the morning.', '🌅', 10, NOW()),
  ('NIGHT_OWL', 'Night Owl', 'Complete an activity late at night.', '🌙', 10, NOW()),
  ('ALL_ROUNDER', 'All-Rounder', 'Complete at least 1 activity of each type.', '🌈', 60, NOW());

-- Sample users (passwords):
-- admin / admin123!
-- parent / parent123!
-- student / student123!
INSERT INTO users (role, username, email, password_hash, display_name, age_group, coins, is_active, created_at) VALUES
  ('admin', 'admin', 'admin@example.com', '$2b$12$1cXDc6levgpXU3dyQInoyO1j5kb0h049d/pe5FjwawBFOCd2O3I3q', 'Administrator', NULL, 0, 1, NOW()),
  ('parent', 'parent', 'parent@example.com', '$2b$12$mL/llBuI.3Z0MW1UYdoQtuxPQs.NMqXGjRyNGUdKeli8Z365AfQTS', 'Parent', NULL, 0, 1, NOW()),
  ('student', 'student', 'student@example.com', '$2b$12$k70hjDXCqTD06Ze7ypyWx.H5BG6vq4sKeDEO82T32w9Jm/GeKfFAG', 'Student', '6-8', 25, 1, NOW());

INSERT INTO parent_child (parent_id, child_id, created_at) VALUES
  (2, 3, NOW());

INSERT INTO parent_controls (parent_id, child_id, daily_minutes_limit, max_age_group, allowed_categories_json, updated_at)
VALUES (2, 3, 45, NULL, NULL, NOW());

-- Default avatar selection + default owned items
INSERT INTO user_avatar (user_id, base_item_id, hat_item_id, shirt_item_id, pet_item_id, bg_item_id)
VALUES (3,
  (SELECT id FROM avatar_items WHERE slot='base' AND is_default=1 LIMIT 1),
  NULL, NULL, NULL,
  (SELECT id FROM avatar_items WHERE slot='bg' AND is_default=1 LIMIT 1)
);

INSERT INTO user_avatar_items (user_id, item_id, owned_at)
SELECT 3, id, NOW() FROM avatar_items WHERE is_default = 1;

-- Sample activities ------------------------------------------------------

-- 1) Quiz (Math)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (1, 'quiz', 'Add it Up!', 'Quick addition questions with instant feedback.', 1,
   '{
      "intro":"Let\'s practice adding!",
      "questions":[
        {"q":"2 + 2 = ?","choices":[{"t":"3"},{"t":"4","correct":true,"explain":"2 and 2 makes 4."},{"t":"5"}],"explain":"Add the two numbers."},
        {"q":"5 + 1 = ?","choices":[{"t":"6","correct":true,"explain":"5 and 1 makes 6."},{"t":"7"},{"t":"4"}]},
        {"q":"3 + 4 = ?","choices":[{"t":"7","correct":true,"explain":"3 and 4 makes 7."},{"t":"6"},{"t":"8"}]}
      ]
    }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (1, '6-8');

INSERT INTO tags (tag) VALUES ('math'),('addition'),('numbers'),('reading'),('animals'),('sounds'),('sight-words'),('puzzle'),('crossword'),('vocabulary'),('fruits')
ON DUPLICATE KEY UPDATE tag=VALUES(tag);

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 1, id FROM tags WHERE tag IN ('math','addition','numbers');

-- 2) Story (Language Arts)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (3, 'story', 'The Lost Kitten', 'Read a short story and answer questions.', 1,
   '{
     "title":"The Lost Kitten",
     "pages":[
       {"title":"Page 1","text":"Mia found a small kitten near the park. The kitten looked hungry.",
        "question":{"q":"How did the kitten look?","choices":["Happy","Hungry","Sleepy"],"answerIndex":1,"explain":"The story says the kitten looked hungry."}},
       {"title":"Page 2","text":"Mia gave the kitten some water and asked neighbors if they knew its home.",
        "question":{"q":"What did Mia give the kitten?","choices":["A toy","Water","A hat"],"answerIndex":1}},
       {"title":"Page 3","text":"Soon, Mia found the kitten\'s family and everyone smiled."}
     ]
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (2, '3-5'), (2, '6-8');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 2, id FROM tags WHERE tag IN ('reading','animals');

-- 3) Drag & Drop (Science - Animal sounds)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (2, 'dragdrop', 'Match the Animal Sounds', 'Match each animal to its sound.', 1,
   '{
     "prompt":"Match the animal with the sound it makes.",
     "pairs":[
       {"left":"Cat","right":"Meow"},
       {"left":"Dog","right":"Woof"},
       {"left":"Cow","right":"Moo"},
       {"left":"Duck","right":"Quack"}
     ]
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (3, '3-5');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 3, id FROM tags WHERE tag IN ('animals','sounds');

-- 4) Flashcards (Language Arts)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (3, 'flashcards', 'Sight Words (Set 1)', 'Flip cards to practice common words.', 2,
   '{
     "cards":[
       {"front":"the","back":"the"},
       {"front":"and","back":"and"},
       {"front":"to","back":"to"},
       {"front":"you","back":"you"},
       {"front":"me","back":"me"}
     ]
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (4, '6-8');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 4, id FROM tags WHERE tag IN ('sight-words','reading');

-- 5) Jigsaw (Art)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (5, 'jigsaw', 'Build the Scene!', 'A jigsaw puzzle with 3 difficulty levels.', 2,
   '{
     "image":"/assets/img/puzzle-scene.svg",
     "difficulties":{
       "easy":{"rows":2,"cols":3},
       "medium":{"rows":3,"cols":4},
       "hard":{"rows":4,"cols":6}
     }
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (5, '3-5'), (5, '6-8');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 5, id FROM tags WHERE tag IN ('puzzle');

-- 6) Crossword (Language Arts)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (3, 'crossword', 'Word Fun Crossword', 'Fill in the crossword. Use hints if you get stuck.', 3,
   '{
     "size":9,
     "words":[
       {"answer":"CAT","row":1,"col":1,"dir":"across","clue":"A pet that says meow"},
       {"answer":"SUN","row":1,"col":1,"dir":"down","clue":"A star in our sky"},
       {"answer":"APPLE","row":4,"col":2,"dir":"across","clue":"A fruit that can be red"}
     ]
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (6, '9-12'), (6, '6-8');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 6, id FROM tags WHERE tag IN ('crossword','vocabulary');

-- 7) Image-to-word matching (Language Arts)
INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at) VALUES
  (3, 'image_word', 'Fruit Match', 'Match each fruit picture to the correct word. Tap words to hear them.', 1,
   '{
     "items":[
       {"img":"/assets/img/apple.svg","word":"apple","alt":"Apple"},
       {"img":"/assets/img/sun.svg","word":"sun","alt":"Sun"},
       {"img":"/assets/img/cat.svg","word":"cat","alt":"Cat"}
     ]
   }',
   NOW(), NOW());

INSERT INTO activity_age_groups (activity_id, age_group) VALUES
  (7, '3-5'), (7, '6-8');

INSERT INTO activity_tags (activity_id, tag_id)
SELECT 7, id FROM tags WHERE tag IN ('vocabulary','fruits');

-- Unlock chain
INSERT INTO activity_prerequisites (activity_id, prereq_activity_id) VALUES
  (4, 1),
  (5, 3),
  (6, 4)
ON DUPLICATE KEY UPDATE prereq_activity_id = VALUES(prereq_activity_id);

-- Sample progress for the sample student
INSERT INTO activity_progress (user_id, activity_id, status, score_percent, stars, coins_earned, attempts, started_at, completed_at, updated_at, details_json)
VALUES (3, 1, 'completed', 100, 5, 25, 1, NOW(), NOW(), NOW(), '{"seed":"sample"}')
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO coins_ledger (user_id, delta, reason, ref_type, ref_id, created_at)
VALUES (3, 25, 'Seed: completed activity', 'activity', 1, NOW());
