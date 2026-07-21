# TimeClock Pro

TimeClock Pro is a lightweight PHP and MySQL application for biweekly employee timecards on shared hosting.

> This application handles employee and payroll-related information. Keep the GitHub repository private and never commit production credentials, session files, logs, database exports, or generated timecard PDFs.

## Features

### Employees

- Email and password login
- Mobile-friendly timecard for the current pay period
- Configurable time increments and rounding
- PTO balance, usage, and availability
- Final submission with confirmation
- Optional per-pay-period production and encounter tracking

### Administrators

- Employee and provider management
- Timecard review, editing, submission, and locking
- Versioned timecard and rounding preferences
- Pay-period controls
- PDF payroll report generation and email delivery
- Provider production and rate reporting

## Requirements

- PHP 8.0 or newer; PHP 8.1 or newer is recommended
- MySQL or MariaDB
- Apache with `mod_rewrite`, or equivalent rewrite support
- HTTPS
- PHP write access to `storage/logs/`, `storage/reports/`, and `storage/sessions/`

## Repository safety

The included `.gitignore` intentionally excludes:

- `app/config.php`
- Debug flags and temporary diagnostic scripts
- PHP and application logs
- Session files
- Generated payroll PDFs
- Environment files and database credentials

Use `app/config.sample.php` as the template for each installation. Do not rename or remove the sample file from the repository.

## Installation

### 1. Choose the web root

Recommended: upload the project outside `public_html` and point the domain or subdomain document root to the project's `public/` directory.

For a subfolder installation on shared hosting, upload the entire project to a folder such as `public_html/timeclock-pro/` and access the application through its `public/` subdirectory. Keep the root `.htaccess` file in place so private folders cannot be served directly.

### 2. Create the database

Create a MySQL database and database user, grant the user the required privileges, and import:

```text
database/schema.sql
```

For an existing installation, apply only the migration files that have not already been run.

### 3. Create the local configuration

On the server, copy:

```text
app/config.sample.php
```

to:

```text
app/config.php
```

Then update the database name, username, password, base URL, timezone, and debug setting in `app/config.php`. This file is ignored by Git and must remain server-only.

### 4. Create the first administrator

The initial database schema does not create a default administrator. Generate a password hash with PHP:

```bash
php -r "echo password_hash('REPLACE_WITH_A_STRONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Insert the first user into the `users` table with the generated hash and the role `super`. After login, additional employees can be created through the administrator area.

### 5. Set writable directories

Ensure PHP can write to:

```text
storage/logs/
storage/reports/
storage/sessions/
```

On shared hosting, directory permissions of `0775` are often appropriate, but follow the hosting provider's guidance.

## Usage

- Employees sign in at `/login` and use `/timecard`.
- Administrators use `/admin` to manage employees, review timecards, and finalize pay periods.
- Finalized payroll reports are generated in `storage/reports/` and are intentionally excluded from Git.

## Optional cron job

To create upcoming pay periods automatically, run this daily with the correct server path:

```bash
php /full/path/to/timeclock-pro/cron/ensure_pay_period.php
```

## Updating an existing installation

1. Back up the production files and database.
2. Preserve the server's `app/config.php` file.
3. Upload the updated tracked source files.
4. Run only the new SQL files in `database/migrations/`.
5. Verify login, time entry, totals, report generation, and email delivery.

Never overwrite production runtime data with files from GitHub.

## Debugging

For temporary server troubleshooting, create the empty file `storage/DEBUG_ON`. Delete it immediately after troubleshooting. Debug mode can also be controlled by the `app.debug` setting in the server-only `app/config.php`.

Logs are written under `storage/logs/`. They may contain server paths or other sensitive context and must not be committed.

## Security

See [SECURITY.md](SECURITY.md) for repository and deployment safeguards.

