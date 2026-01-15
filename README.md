# Pamikil Learning Platform (Vanilla PHP + MySQL)

A production-ready, child-friendly interactive learning platform built with **pure PHP 7.4+**, **MySQL 5.7+**, **HTML/CSS/vanilla JS**.

It is designed to run on:
- Free shared hosting (e.g. **InfinityFree**)
- Paid LAMP hosting

## Features

- **7 interactive activity types** (no frameworks)
  - Multiple-choice quizzes
  - Interactive stories with comprehension questions
  - Drag-and-drop (tap-to-match on mobile) matching games
  - Flashcards with CSS flip animation
  - Jigsaw puzzles (6 / 12 / 24 pieces)
  - Crossword puzzles with progressive hints
  - Image-to-word matching with audio pronunciation (Web Speech API)

- **Content Management System (Admin)**
  - Subjects: Math, Science, Language Arts, Social Studies, Art
  - Age groups: 3–5, 6–8, 9–12
  - Tagging + search
  - Progressive unlock (prerequisite chain)

- **Gamification**
  - Coins + coin ledger
  - 15+ badges
  - Weekly leaderboard
  - Printable certificates
  - Avatar customization with unlockable items

- **User roles**
  - Student dashboard + progress
  - Parent monitoring + time limits + restrictions
  - Admin analytics + content creation

- **Security**
  - Prepared statements (PDO)
  - CSRF protection
  - XSS-safe output escaping
  - Session timeout + session id rotation

## Quick start (local / paid hosting)

1. Create a MySQL database.
2. Import `database/install.sql`.
3. Copy `app/config/config.example.php` to `app/config/config.php` and set DB credentials.
4. Upload the project to your web root.
5. Visit `/`.

Sample accounts (from `database/install.sql`):
- Admin: `admin` / `admin123!`
- Parent: `parent` / `parent123!`
- Student: `student` / `student123!`

## Documentation

- `docs/deployment.md` – setup for InfinityFree + paid hosting
- `docs/admin-manual.md` – admin CMS guide
- `docs/parent-guide.md` – parent monitoring + controls
- `docs/technical.md` – architecture, security notes, activity JSON formats

