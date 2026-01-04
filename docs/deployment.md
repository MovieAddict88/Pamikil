# Deployment Guide

This project is designed for **shared hosting** and **paid LAMP hosting**.

## Requirements checklist

- PHP **7.4+**
  - Extensions: `pdo`, `pdo_mysql`, `json`, `mbstring` (recommended)
- MySQL **5.7+** (or MariaDB 10.3+)
- Apache (recommended) with `.htaccess` support
  - `mod_rewrite` (optional but recommended)

## 1) Database setup

1. Create a database (e.g. `pamikil`).
2. Import:
   - `database/install.sql`

This creates the schema and inserts sample data (users, activities, badges).

## 2) Application config

1. Copy:
   - `app/config/config.example.php` → `app/config/config.php`
2. Edit DB credentials:

```php
'db' => [
  'host' => 'sqlXXX.yourhost.com',
  'name' => 'your_db_name',
  'user' => 'your_db_user',
  'pass' => 'your_db_pass',
],
```

3. Set `app.base_url`:
- If the site is at the domain root: `''`
- If it is in a subfolder: `'/subfolder'`

## 3) InfinityFree deployment

1. In InfinityFree, your web root is typically:
   - `htdocs/` (or `public_html/` depending on panel)
2. Upload the repository contents into your web root.
3. Create a MySQL database in the InfinityFree control panel.
4. Open **phpMyAdmin** and import `database/install.sql`.
5. Create `app/config/config.php` with the credentials.
6. Visit the site.

### InfinityFree troubleshooting

- **HTTP 500 error after upload**
  - Temporarily rename `.htaccess` to `htaccess.disabled`.
  - If the site works, your host may not support a directive in `.htaccess`.
  - The platform can run without URL rewriting by visiting `index.php?page=...`.

- **Database connection errors**
  - Double-check host/user/password/db name.
  - Ensure `pdo_mysql` is enabled (most hosting enables it).

## 4) Paid hosting (cPanel / VPS)

1. Upload files to your web root.
2. Create DB + user.
3. Import `database/install.sql`.
4. Set `app/config/config.php`.
5. Enable HTTPS and redirect HTTP → HTTPS (recommended).

## Default accounts (for testing)

From `database/install.sql`:

- Admin: `admin` / `admin123!`
- Parent: `parent` / `parent123!`
- Student: `student` / `student123!`

**Important:** change these passwords immediately on production.
