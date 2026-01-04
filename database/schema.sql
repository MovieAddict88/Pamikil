-- ============================================================
-- CHILDREN'S LEARNING PLATFORM - DATABASE SCHEMA
-- ============================================================
-- Version: 1.0
-- Created: 2024
-- Description: Complete database schema for interactive learning platform
-- Compatible with: MySQL 5.7+
-- ============================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- USERS AND AUTHENTICATION TABLES
-- ============================================================

CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'parent', 'admin') NOT NULL DEFAULT 'student',
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `avatar_image` VARCHAR(255) DEFAULT NULL,
  `parent_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'For student accounts linked to parents',
  `date_of_birth` DATE DEFAULT NULL COMMENT 'Required for age-based content',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `coins` INT(11) NOT NULL DEFAULT 0,
  `total_xp` INT(11) NOT NULL DEFAULT 0,
  `current_level` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_users_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User accounts for students, parents, and administrators';

CREATE TABLE `user_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `login_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User session tracking for security';

CREATE TABLE `password_resets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `reset_token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reset_token` (`reset_token`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset tokens';

-- ============================================================
-- CONTENT MANAGEMENT TABLES
-- ============================================================

CREATE TABLE `subjects` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '#000000',
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Subject categories for activities';

CREATE TABLE `age_groups` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `min_age` INT(11) NOT NULL,
  `max_age` INT(11) NOT NULL,
  `description` VARCHAR(255),
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_age_range` (`min_age`, `max_age`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Age groups for content filtering';

CREATE TABLE `difficulty_levels` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `level_number` INT(11) NOT NULL,
  `description` TEXT,
  `xp_reward` INT(11) NOT NULL DEFAULT 10,
  `coin_reward` INT(11) NOT NULL DEFAULT 5,
  `min_level_required` INT(11) NOT NULL DEFAULT 1,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_level_number` (`level_number`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Difficulty levels for activities';

CREATE TABLE `activities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT(11) UNSIGNED NOT NULL,
  `age_group_id` INT(11) UNSIGNED NOT NULL,
  `difficulty_id` INT(11) UNSIGNED NOT NULL,
  `activity_type` ENUM('quiz', 'story', 'dragdrop', 'flashcard', 'puzzle', 'crossword', 'matching') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `instructions` TEXT,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `content_data` LONGTEXT NOT NULL COMMENT 'JSON-encoded activity content',
  `time_limit` INT(11) DEFAULT NULL COMMENT 'Time limit in seconds, NULL for unlimited',
  `passing_score` INT(11) NOT NULL DEFAULT 70 COMMENT 'Minimum percentage to pass',
  `xp_reward` INT(11) NOT NULL DEFAULT 10,
  `coin_reward` INT(11) NOT NULL DEFAULT 5,
  `attempts_allowed` INT(11) DEFAULT NULL COMMENT 'NULL for unlimited',
  `min_level_required` INT(11) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_age_group_id` (`age_group_id`),
  KEY `idx_difficulty_id` (`difficulty_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_activities_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_activities_age_group` FOREIGN KEY (`age_group_id`) REFERENCES `age_groups` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_activities_difficulty` FOREIGN KEY (`difficulty_id`) REFERENCES `difficulty_levels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_activities_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Learning activities with various interaction types';

CREATE TABLE `activity_tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255),
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tags for categorizing activities';

CREATE TABLE `activity_tag_relations` (
  `activity_id` INT(11) UNSIGNED NOT NULL,
  `tag_id` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_tag_relations_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tag_relations_tag` FOREIGN KEY (`tag_id`) REFERENCES `activity_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Many-to-many relationship between activities and tags';

-- ============================================================
-- USER PROGRESS AND ACTIVITY TRACKING
-- ============================================================

CREATE TABLE `user_activity_progress` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `activity_id` INT(11) UNSIGNED NOT NULL,
  `attempts_count` INT(11) NOT NULL DEFAULT 0,
  `best_score` INT(11) DEFAULT NULL,
  `best_time` INT(11) DEFAULT NULL COMMENT 'Best completion time in seconds',
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `star_rating` INT(11) DEFAULT NULL COMMENT '1-5 stars based on performance',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_activity` (`user_id`, `activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_completed` (`completed`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User progress tracking for each activity';

CREATE TABLE `user_activity_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `activity_id` INT(11) UNSIGNED NOT NULL,
  `session_data` LONGTEXT NOT NULL COMMENT 'JSON-encoded session state',
  `current_question` INT(11) DEFAULT 1,
  `score` INT(11) NOT NULL DEFAULT 0,
  `time_spent` INT(11) NOT NULL DEFAULT 0 COMMENT 'Time in seconds',
  `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('in_progress', 'completed', 'abandoned') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_status` (`status`),
  KEY `idx_started_at` (`started_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sessions_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual activity session tracking for auto-save';

-- ============================================================
-- GAMIFICATION TABLES
-- ============================================================

CREATE TABLE `badges` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(255) DEFAULT NULL,
  `badge_type` ENUM('completion', 'streak', 'score', 'time', 'special') NOT NULL DEFAULT 'special',
  `requirement_data` LONGTEXT NOT NULL COMMENT 'JSON-encoded requirements',
  `coin_reward` INT(11) NOT NULL DEFAULT 0,
  `xp_reward` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_badge_type` (`badge_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Achievement badges for users';

CREATE TABLE `user_badges` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `badge_id` INT(11) UNSIGNED NOT NULL,
  `earned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_badge` (`user_id`, `badge_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_badge_id` (`badge_id`),
  CONSTRAINT `fk_user_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Badges earned by users';

CREATE TABLE `leaderboards` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `leaderboard_type` ENUM('weekly_xp', 'weekly_coins', 'total_xp', 'total_coins', 'activities_completed') NOT NULL,
  `period_start` TIMESTAMP NOT NULL,
  `period_end` TIMESTAMP NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`leaderboard_type`),
  KEY `idx_period` (`period_start`, `period_end`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leaderboard periods and types';

CREATE TABLE `leaderboard_entries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `leaderboard_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `score` INT(11) NOT NULL,
  `rank` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_leaderboard_user` (`leaderboard_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_score` (`score`),
  CONSTRAINT `fk_entries_leaderboard` FOREIGN KEY (`leaderboard_id`) REFERENCES `leaderboards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User entries on leaderboards';

CREATE TABLE `avatar_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `item_type` ENUM('hair', 'face', 'clothing', 'accessory', 'background') NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `preview_image` VARCHAR(255) DEFAULT NULL,
  `cost` INT(11) NOT NULL DEFAULT 0,
  `min_level_required` INT(11) NOT NULL DEFAULT 1,
  `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customizable avatar items';

CREATE TABLE `user_avatar_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `hair_id` INT(11) UNSIGNED DEFAULT NULL,
  `face_id` INT(11) UNSIGNED DEFAULT NULL,
  `clothing_id` INT(11) UNSIGNED DEFAULT NULL,
  `accessory_id` INT(11) UNSIGNED DEFAULT NULL,
  `background_id` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_hair_id` (`hair_id`),
  KEY `idx_face_id` (`face_id`),
  KEY `idx_clothing_id` (`clothing_id`),
  KEY `idx_accessory_id` (`accessory_id`),
  KEY `idx_background_id` (`background_id`),
  CONSTRAINT `fk_avatar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avatar_hair` FOREIGN KEY (`hair_id`) REFERENCES `avatar_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avatar_face` FOREIGN KEY (`face_id`) REFERENCES `avatar_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avatar_clothing` FOREIGN KEY (`clothing_id`) REFERENCES `avatar_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avatar_accessory` FOREIGN KEY (`accessory_id`) REFERENCES `avatar_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avatar_background` FOREIGN KEY (`background_id`) REFERENCES `avatar_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User avatar customization settings';

CREATE TABLE `user_owned_avatar_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `item_id` INT(11) UNSIGNED NOT NULL,
  `purchased_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_item` (`user_id`, `item_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_item_id` (`item_id`),
  CONSTRAINT `fk_owned_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_owned_items_item` FOREIGN KEY (`item_id`) REFERENCES `avatar_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Avatar items owned by users';

-- ============================================================
-- PARENT CONTROLS TABLES
-- ============================================================

CREATE TABLE `parent_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) UNSIGNED NOT NULL,
  `child_id` INT(11) UNSIGNED NOT NULL,
  `daily_time_limit` INT(11) DEFAULT NULL COMMENT 'Daily time limit in minutes, NULL for unlimited',
  `weekly_time_limit` INT(11) DEFAULT NULL COMMENT 'Weekly time limit in minutes, NULL for unlimited',
  `allowed_hours_start` TIME DEFAULT NULL COMMENT 'Allowed hours start (e.g., 08:00:00)',
  `allowed_hours_end` TIME DEFAULT NULL COMMENT 'Allowed hours end (e.g., 20:00:00)',
  `blocked_days` VARCHAR(20) DEFAULT NULL COMMENT 'JSON array of blocked days (0-6, Sunday-Saturday)',
  `subject_restrictions` TEXT DEFAULT NULL COMMENT 'JSON array of allowed subject IDs',
  `difficulty_max` INT(11) DEFAULT NULL COMMENT 'Maximum difficulty level allowed',
  `notification_email` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_parent_child` (`parent_id`, `child_id`),
  KEY `idx_child_id` (`child_id`),
  CONSTRAINT `fk_parent_settings_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parent_settings_child` FOREIGN KEY (`child_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parent control settings for children';

CREATE TABLE `time_tracking` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `total_minutes` INT(11) NOT NULL DEFAULT 0,
  `activities_completed` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`, `date`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_time_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily time tracking for parent monitoring';

-- ============================================================
-- ADMIN TABLES
-- ============================================================

CREATE TABLE `admin_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT(11) UNSIGNED DEFAULT NULL,
  `old_values` LONGTEXT DEFAULT NULL COMMENT 'JSON-encoded old values',
  `new_values` LONGTEXT DEFAULT NULL COMMENT 'JSON-encoded new values',
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_admin_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin action audit logs';

CREATE TABLE `system_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` LONGTEXT,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
  `description` TEXT,
  `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether setting can be accessed by non-admins',
  `updated_by` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`),
  CONSTRAINT `fk_settings_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System-wide configuration settings';

-- ============================================================
-- CERTIFICATES AND REPORTS
-- ============================================================

CREATE TABLE `certificates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `certificate_type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `achievement_data` LONGTEXT DEFAULT NULL COMMENT 'JSON-encoded achievement details',
  `certificate_code` VARCHAR(50) NOT NULL,
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_certificate_code` (`certificate_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_certificate_type` (`certificate_type`),
  CONSTRAINT `fk_certificates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Achievement certificates issued to users';

-- ============================================================
-- RELATIONSHIP DIAGRAM (as comments):
-- ============================================================
/*
users (1) ----< (N) user_sessions
users (1) ----< (N) password_resets
users (1) ----< (N) users (as parent_id)
users (1) ----< (N) user_activity_progress
users (1) ----< (N) user_activity_sessions
users (1) ----< (N) user_badges
users (1) ----< (N) leaderboard_entries
users (1) ----< (1) user_avatar_settings
users (1) ----< (N) user_owned_avatar_items
users (1) ----< (N) parent_settings (as parent_id)
users (1) ----< (N) parent_settings (as child_id)
users (1) ----< (N) time_tracking
users (1) ----< (N) admin_logs
users (1) ----< (N) system_settings (as updated_by)
users (1) ----< (N) certificates

subjects (1) ----< (N) activities
age_groups (1) ----< (N) activities
difficulty_levels (1) ----< (N) activities
users (1) ----< (N) activities (as created_by)

activity_tags (1) ----< (N) activity_tag_relations
activities (1) ----< (N) activity_tag_relations
activities (1) ----< (N) user_activity_progress
activities (1) ----< (N) user_activity_sessions

badges (1) ----< (N) user_badges
leaderboards (1) ----< (N) leaderboard_entries

avatar_items (1) ----< (N) user_avatar_settings (for each item type)
avatar_items (1) ----< (N) user_owned_avatar_items
*/

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================
-- Additional composite indexes for common queries
CREATE INDEX `idx_activities_subject_age_type` ON `activities` (`subject_id`, `age_group_id`, `activity_type`);
CREATE INDEX `idx_activities_active_featured` ON `activities` (`is_active`, `is_featured`);
CREATE INDEX `idx_progress_user_completed` ON `user_activity_progress` (`user_id`, `completed`);
CREATE INDEX `idx_sessions_user_status` ON `user_activity_sessions` (`user_id`, `status`);
CREATE INDEX `idx_time_user_date` ON `time_tracking` (`user_id`, `date`);
CREATE INDEX `idx_badges_type_active` ON `badges` (`badge_type`, `is_active`);

-- ============================================================
-- END OF SCHEMA
-- ============================================================
