-- Karaoke Application Database Schema
-- MySQL 8.0+ compatible

-- Create database
CREATE DATABASE IF NOT EXISTS karaoke_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE karaoke_app;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Songs table
CREATE TABLE songs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    song_number INT UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    artist VARCHAR(255) NOT NULL,
    album VARCHAR(255) DEFAULT NULL,
    duration INT DEFAULT NULL, -- in seconds
    language VARCHAR(50) DEFAULT NULL,
    genre VARCHAR(100) DEFAULT NULL,
    year_released YEAR DEFAULT NULL,
    youtube_video_id VARCHAR(50) DEFAULT NULL,
    youtube_playlist_id VARCHAR(50) DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    embed_code TEXT DEFAULT NULL,
    external_url VARCHAR(500) DEFAULT NULL,
    lyrics TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_song_number (song_number),
    INDEX idx_title (title),
    INDEX idx_artist (artist),
    INDEX idx_genre (genre),
    FULLTEXT idx_search (title, artist, lyrics)
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    color VARCHAR(20) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sort_order (sort_order)
);

-- Song-Category relationship
CREATE TABLE song_categories (
    song_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (song_id, category_id),
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Rooms table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    room_code VARCHAR(10) UNIQUE NOT NULL,
    host_id INT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    max_participants INT DEFAULT 20,
    current_participants INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON DEFAULT NULL, -- room settings like autoplay, scoring, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id),
    INDEX idx_room_code (room_code),
    INDEX idx_host_id (host_id)
);

-- Room participants
CREATE TABLE room_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('host', 'participant', 'moderator') DEFAULT 'participant',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_active_participant (room_id, user_id),
    INDEX idx_room_active (room_id, is_active)
);

-- Song queue
CREATE TABLE song_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    song_id INT NOT NULL,
    user_id INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    played_at TIMESTAMP NULL,
    status ENUM('queued', 'playing', 'completed', 'skipped') DEFAULT 'queued',
    position INT NOT NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_room_status (room_id, status),
    INDEX idx_position (room_id, position)
);

-- Scoring system
CREATE TABLE scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT DEFAULT NULL, -- NULL for solo mode
    song_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL,
    pitch_accuracy DECIMAL(5,2) DEFAULT NULL,
    timing_accuracy DECIMAL(5,2) DEFAULT NULL,
    notes_hit INT DEFAULT NULL,
    notes_missed INT DEFAULT NULL,
    duration_sung INT DEFAULT NULL,
    max_score INT DEFAULT 1000,
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN max_score > 0 THEN (score / max_score) * 100 
            ELSE 0 
        END
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_room_score (room_id, score DESC),
    INDEX idx_user_score (user_id, score DESC),
    INDEX idx_song_score (song_id, score DESC)
);

-- Chat messages
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'system', 'reaction') DEFAULT 'text',
    reaction_data JSON DEFAULT NULL, -- for emoji reactions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_room_time (room_id, created_at)
);

-- User sessions for real-time features
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT DEFAULT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_active (user_id, is_active)
);

-- Playlists table
CREATE TABLE playlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    cover_image VARCHAR(255) DEFAULT NULL,
    song_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_created_by (created_by),
    INDEX idx_public (is_public)
);

-- Playlist songs
CREATE TABLE playlist_songs (
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    position INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, song_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    INDEX idx_position (position)
);

-- Insert sample data
INSERT INTO users (username, email, password_hash, display_name, is_admin) VALUES
('admin', 'admin@karaoke.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karaoke Admin', TRUE),
('demo', 'demo@karaoke.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', FALSE),
('user1', 'user1@karaoke.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', FALSE);

INSERT INTO categories (name, description, icon, color, sort_order) VALUES
('Pop', 'Popular music hits', 'music', '#FF6B6B', 1),
('Rock', 'Classic and modern rock', 'guitar', '#4ECDC4', 2),
('Country', 'Country music classics', 'flag', '#45B7D1', 3),
('R&B/Soul', 'Rhythm and blues', 'heart', '#96CEB4', 4),
('Jazz', 'Jazz standards', 'piano', '#FECA57', 5),
('Musical Theater', 'Broadway and musical theater', 'theater', '#FF9FF3', 6),
('Classic Rock', 'Classic rock anthems', 'star', '#54A0FF', 7),
('Hip-Hop', 'Hip-hop and rap', 'mic', '#5F27CD', 8);

INSERT INTO songs (song_number, title, artist, album, duration, language, genre, year_released, lyrics) VALUES
(1, 'Bohemian Rhapsody', 'Queen', 'A Night at the Opera', 355, 'English', 'Rock', 1975, 'Is this the real life? Is this just fantasy?'),
(2, 'Sweet Caroline', 'Neil Diamond', 'Brother Love', 201, 'English', 'Pop', 1969, 'Sweet Caroline, ba ba ba'),
(3, 'Don''t Stop Believin''', 'Journey', 'Escape', 251, 'English', 'Rock', 1981, 'Just a small town girl'),
(4, 'I Will Survive', 'Gloria Gaynor', 'Love Tracks', 197, 'English', 'Disco', 1978, 'At first I was afraid'),
(5, 'Hotel California', 'Eagles', 'Hotel California', 391, 'English', 'Rock', 1976, 'On a dark desert highway'),
(6, 'My Way', 'Frank Sinatra', 'My Way', 275, 'English', 'Jazz', 1969, 'And now the end is near'),
(7, 'Total Eclipse of the Heart', 'Bonnie Tyler', 'Faster Than the Speed of Night', 448, 'English', 'Pop', 1983, 'Turn around'),
(8, 'Dancing Queen', 'ABBA', 'Arrival', 234, 'English', 'Pop', 1976, 'You can dance'),
(9, 'Africa', 'Toto', 'Toto IV', 285, 'English', 'Rock', 1982, 'I bless the rains'),
(10, 'I Want It That Way', 'Backstreet Boys', 'Millennium', 217, 'English', 'Pop', 1999, 'Tell me why');

-- Update song count for categories (many-to-many relationship would need to be established)
-- For now, this is sample data to get started

-- Insert sample room
INSERT INTO rooms (name, description, room_code, host_id, is_public, max_participants) VALUES
('Demo Karaoke Room', 'A sample karaoke room for testing', 'DEMO123', 1, TRUE, 10);

-- Insert sample playlist
INSERT INTO playlists (name, description, created_by, is_public, song_count) VALUES
('Classic Hits', 'Timeless karaoke favorites', 1, TRUE, 10),
('Party Mix', 'High energy songs for parties', 1, TRUE, 8);

-- Add some playlist songs
INSERT INTO playlist_songs (playlist_id, song_id, position) VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 3), (1, 4, 4),
(2, 3, 1), (2, 5, 2), (2, 7, 3), (2, 8, 4);