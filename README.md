# CineCraze (PHP + MySQL + PWA)

This project is a PHP/MySQL version of the CineCraze streaming portal + admin generator.

## Quick start

1. Upload the files to a PHP host.
2. Visit `/install.php` and enter your MySQL credentials.
3. Sign in at `/admin/login.php`.
4. Manage your catalog in the Admin Dashboard (`/admin/index.php`).
5. The public site is `/index.php`.

## Data model

The catalog is stored in MySQL in a single `app_settings` record (`catalog_json`) using the original `Categories` JSON schema.

## PWA

The public site ships with a `manifest.webmanifest` and `service-worker.js` for offline caching.
