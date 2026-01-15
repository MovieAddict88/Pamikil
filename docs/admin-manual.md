# Admin Manual

## Logging in

Use an Admin account to access:
- `/admin` – dashboard
- `/admin/content` – activity CMS
- `/admin/users` – user management
- `/admin/reports` – analytics

## Managing activities (CMS)

Go to `/admin/content`.

### Create / edit

An activity contains:
- **Title**
- **Description**
- **Category** (Math, Science, Language Arts, Social Studies, Art)
- **Type** (one of the 7 supported activity types)
- **Difficulty** (1–5)
- **Age groups** (optional; if none selected, it shows for all)
- **Tags** (comma-separated)
- **Prerequisites**
  - Used to lock content until earlier activities are completed.

### Content JSON

Content is stored as JSON in the DB to keep the platform framework-free.

See `docs/technical.md` for type-specific JSON formats and examples.

## User management

Go to `/admin/users`.

- Search by username/email/display name
- Filter by role
- Deactivate/reactivate accounts
- Reset passwords (generates a temporary password)

All admin changes are recorded in `admin_logs`.

## Reports

Go to `/admin/reports` for:
- Weekly completions by category
- Top activities
- Top students by coins

