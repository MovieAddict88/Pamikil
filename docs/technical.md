# Technical Documentation

## Project structure

```
/ (web root)
  index.php                 Front controller + route mapping
  .htaccess                 Optional rewrite + directory hardening
  /app
    bootstrap.php           Loads config, starts session, initializes DB/Auth
    /config
      config.example.php    Copy to config.php (ignored by git)
    /lib
      Database.php          PDO wrapper
      Auth.php              Login/session/role checks
      Csrf.php              CSRF token generation/validation
      Security.php          Session hardening + helpers
      ActivityEngine.php    Coins/badges/unlocks + progress persistence
      helpers.php           url(), e(), redirect(), json_response(), ...
    /pages                  View scripts (student/parent/admin/activities)
    /templates              Layout + nav/footer
  /api
    activity_submit.php     Persist completion (coins, stars, badges)
    autosave_get.php        Load in-progress state
    autosave_save.php       Save in-progress state
    autosave_clear.php      Clear autosave
    heartbeat.php           Track time-on-task (used by parent time limits)
  /assets
    /css/style.css
    /js/app.js
    /js/activities/*.js
  /database/install.sql
  /docs/*.md
```

## Security model

- **SQL injection prevention**: PDO prepared statements everywhere.
- **XSS protection**: output escaped with `e()` (HTML entities).
- **CSRF**: all POST requests require CSRF token.
  - Forms: `Csrf::inputField()`
  - Fetch API: `X-CSRF-Token` header from meta tag
- **Password storage**: bcrypt via `password_hash()`.
- **Session security**:
  - `HttpOnly` cookies
  - session id regeneration
  - inactivity timeout (configurable)

### Recommended production improvements

- Enforce HTTPS and set secure cookies.
- Add rate limiting on login.
- Replace the “link child by email” mechanism with a **Parent Link Code**:
  - Add `users.parent_link_code` for students
  - Student dashboard shows a rotating code
  - Parent must enter this code to link

## Activity content JSON formats

All activities are rendered client-side by a dedicated JS module.

### 1) Quiz (`type = quiz`)

```json
{
  "intro": "Let's practice!",
  "questions": [
    {
      "q": "2 + 2 = ?",
      "choices": [
        {"t": "3"},
        {"t": "4", "correct": true, "explain": "2 and 2 makes 4."},
        {"t": "5"}
      ],
      "explain": "Add the two numbers."
    }
  ]
}
```

### 2) Story (`type = story`)

```json
{
  "title": "My Story",
  "pages": [
    {
      "title": "Page 1",
      "text": "Once upon a time...",
      "question": {
        "q": "What happened?",
        "choices": ["A", "B", "C"],
        "answerIndex": 1,
        "explain": "Because ..."
      }
    }
  ]
}
```

### 3) Drag & drop matching (`type = dragdrop`)

```json
{
  "prompt": "Match each animal to its sound",
  "pairs": [
    {"left": "Cat", "right": "Meow"},
    {"left": "Dog", "right": "Woof"}
  ]
}
```

### 4) Flashcards (`type = flashcards`)

```json
{
  "cards": [
    {"front": "the", "back": "the"},
    {"front": "and", "back": "and"}
  ]
}
```

### 5) Jigsaw (`type = jigsaw`)

```json
{
  "image": "/assets/img/puzzle-scene.svg",
  "difficulties": {
    "easy": {"rows": 2, "cols": 3},
    "medium": {"rows": 3, "cols": 4},
    "hard": {"rows": 4, "cols": 6}
  }
}
```

### 6) Crossword (`type = crossword`)

```json
{
  "size": 9,
  "words": [
    {"answer": "CAT", "row": 1, "col": 1, "dir": "across", "clue": "A pet"},
    {"answer": "SUN", "row": 1, "col": 1, "dir": "down", "clue": "A star"}
  ]
}
```

### 7) Image-to-word (`type = image_word`)

```json
{
  "items": [
    {"img": "/assets/img/apple.svg", "word": "apple", "alt": "Apple"}
  ]
}
```

## Adding a new activity type

1. Add a new JS module in `assets/js/activities/<type>.js`.
2. Register it using `window.Pamikil.registerActivity('<type>', { init(ctx) { ... } })`.
3. Update the allowed type lists:
   - DB enum in `database/install.sql` (activities.type)
   - `app/pages/admin/content.php` type dropdown
   - `app/pages/activities/play.php` JS file mapping

