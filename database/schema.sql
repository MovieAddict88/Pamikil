-- Karaoke Application Database Schema
-- MySQL 5.7+ compatible

CREATE DATABASE IF NOT EXISTS karaoke_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE karaoke_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#0066cc',
    icon VARCHAR(50) DEFAULT 'music',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Songs table
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    artist VARCHAR(255) NOT NULL,
    song_number VARCHAR(20),
    category_id INT,
    duration INT DEFAULT 0,
    video_type ENUM('youtube', 'upload', 'embed', 'url') DEFAULT 'youtube',
    video_id VARCHAR(100),
    video_url VARCHAR(500),
    embed_code TEXT,
    thumbnail_url VARCHAR(500),
    lyrics TEXT,
    language VARCHAR(10) DEFAULT 'en',
    bpm INT,
    key_signature VARCHAR(10),
    play_count INT DEFAULT 0,
    last_played TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_song_number (song_number),
    INDEX idx_artist (artist),
    INDEX idx_title (title),
    INDEX idx_video_id (video_id),
    INDEX idx_play_count (play_count),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    created_by INT NOT NULL,
    is_private BOOLEAN DEFAULT FALSE,
    max_participants INT DEFAULT 10,
    status ENUM('waiting', 'active', 'completed') DEFAULT 'waiting',
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room participants table
CREATE TABLE IF NOT EXISTS room_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    role ENUM('participant', 'host') DEFAULT 'participant',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (room_id, user_id, left_at),
    INDEX idx_room_id (room_id),
    INDEX idx_user_id (user_id),
    INDEX idx_left_at (left_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue table
CREATE TABLE IF NOT EXISTS queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    song_id INT NOT NULL,
    requested_by INT NOT NULL,
    position INT NOT NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    status ENUM('waiting', 'playing', 'completed', 'skipped') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_status (status),
    INDEX idx_position (position),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scores table
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    room_id INT,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    current_score DECIMAL(5,2) DEFAULT 0,
    final_score DECIMAL(5,2),
    pitch_accuracy DECIMAL(5,4) DEFAULT 0,
    timing_accuracy DECIMAL(5,4) DEFAULT 0,
    volume_stability DECIMAL(5,4) DEFAULT 0,
    note_completion DECIMAL(5,4) DEFAULT 0,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_song_id (song_id),
    INDEX idx_room_id (room_id),
    INDEX idx_final_score (final_score),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlists table
CREATE TABLE IF NOT EXISTS playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlist songs junction table
CREATE TABLE IF NOT EXISTS playlist_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    position INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playlist_song (playlist_id, song_id),
    INDEX idx_playlist_id (playlist_id),
    INDEX idx_song_id (song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'system', 'reaction') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO categories (name, description, color, icon) VALUES
('Pop', 'Popular music hits', '#FF6B6B', 'music'),
('Rock', 'Rock and roll classics', '#4ECDC4', 'guitar'),
('Country', 'Country music favorites', '#45B7D1', 'tree'),
('R&B', 'Rhythm and Blues', '#96CEB4', 'heart'),
('Hip Hop', 'Hip hop and rap hits', '#FFEAA7', 'microphone'),
('Oldies', 'Classic oldies', '#DDA0DD', 'clock'),
('Jazz', 'Jazz standards', '#98D8C8', 'saxophone'),
('Latin', 'Latin music', '#F7DC6F', 'globe'),
('Karaoke Classics', 'Popular karaoke songs', '#BB8FCE', 'star');

-- Insert sample songs
INSERT INTO songs (title, artist, song_number, category_id, duration, video_type, video_id, language) VALUES
('I Will Survive', 'Gloria Gaynor', '1001', 9, 198, 'youtube', 'ZBRJOTCT5nU', 'en'),
('Sweet Caroline', 'Neil Diamond', '1002', 9, 201, 'youtube', '1vhFNfFu1t4', 'en'),
('Don\'t Stop Believin\'', 'Journey', '1003', 9, 250, 'youtube', '1k8craCGpgs', 'en'),
('Bohemian Rhapsody', 'Queen', '1004', 9, 355, 'youtube', 'fJ9rUzIMcZQ', 'en'),
('Like a Prayer', 'Madonna', '1005', 9, 345, 'youtube', '79fzeNUqQbQ', 'en'),
('Wonderwall', 'Oasis', '1006', 9, 258, 'youtube', 'bx1Bh8ZvH84', 'en'),
('Livin\' on a Prayer', 'Bon Jovi', '1007', 9, 252, 'youtube', 'vN7dFYOVK7U', 'en'),
('Total Eclipse of the Heart', 'Bonnie Tyler', '1008', 9, 347, 'youtube', 'lcOxhH8N3Bo', 'en'),
('Ain\'t No Mountain High Enough', 'Marvin Gaye & Tammi Terrell', '1009', 9, 149, 'youtube', 'eAfyFTzZDMM', 'en'),
('My Way', 'Frank Sinatra', '1010', 9, 298, 'youtube', '6E2hYDIFDI0', 'en');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@karaoke.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_songs_search ON songs (title, artist, song_number);
CREATE INDEX idx_queue_room_status ON queue (room_id, status);
CREATE INDEX idx_scores_user_date ON scores (user_id, created_at);
CREATE INDEX idx_scores_song_score ON scores (song_id, final_score);
CREATE INDEX idx_rooms_code_status ON rooms (code, status);