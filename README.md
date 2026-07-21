# TimeClock Pro

[![PHP syntax check](https://github.com/drumhead39/Timeclock-pro/actions/workflows/php-syntax.yml/badge.svg)](https://github.com/drumhead39/Timeclock-pro/actions/workflows/php-syntax.yml)

TimeClock Pro is a self-hosted PHP and MySQL application for biweekly employee timecards, PTO tracking, pay-period approvals, payroll PDF reports, and optional provider-production reporting. It is designed for small clinics and service businesses using Apache-based shared hosting.

## Features

### Employees

- Secure email and password authentication
- Mobile-friendly time entry
- Configurable increments and rounding
- PTO balance and usage display
- Final submission with locking controls
- Optional per-pay-period sales or encounter counters

### Administrators

- Employee and provider management
- Timecard review, correction, submission, and locking
- Versioned timecard preferences
- Provider rates and production totals
- PDF payroll report generation and email delivery
- Pay-period selection and historical reporting

## Security and privacy

TimeClock Pro handles employee and payroll-related information. Production credentials, sessions, logs, database exports, and generated reports must never be committed to Git.

The repository includes protection for these files, but administrators remain responsible for HTTPS, server permissions, backups, access controls, and applicable privacy or employment-law requirements. Review [SECURITY.md](SECURITY.md) before deployment.

## Requirements

- PHP 8.0 or newer; PHP 8.1 or newer is recommended
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite`, or an equivalent web-server configuration
- HTTPS
- PHP `PDO` and `pdo_mysql`
- PHP write access to the required `storage/` subdirectories

## Installation

### 1. Download the source

Clone the repository or download a release archive. Place the project outside the public web root whenever the hosting provider permits it.

```bash
git clone https://github.com/drumhead39/Timeclock-pro.git
cd Timeclock-pro
```

### 2. Create the database

Create an empty MySQL or MariaDB database and database user. Grant that user the required privileges, then import:

```text
database/schema.sql
```

The fresh-install schema creates the complete table structure and default application settings. It does not create a default administrator.

### 3. Configure the application

Copy the sample configuration on the server:

```bash
cp app/config.sample.php app/config.php
```

Edit `app/config.php` and supply the database name, username, password, base URL, and timezone. Keep debug mode disabled in production. The real configuration file is ignored by Git.

### 4. Create the first administrator

From the project directory, run:

```bash
php bin/create-admin.php "Administrator Name" admin@example.com
```

The command will request a password containing at least 12 characters. To avoid an interactive prompt, the password may be supplied temporarily through `TIMECLOCK_ADMIN_PASSWORD`.

### 5. Configure the document root

Point the domain or subdomain document root to the project's `public/` directory. This is the recommended deployment arrangement because application code, configuration, database scripts, and runtime data remain outside the web root.

If the entire project must be placed under a web-accessible shared-hosting directory, keep both `.htaccess` files in place and confirm that direct requests to `app/`, `database/`, `storage/`, `cron/`, and `bin/` return HTTP 403.

### 6. Set writable directories

PHP must be able to write to:

```text
storage/logs/
storage/rate-limits/
storage/reports/
storage/sessions/
```

Permissions of `0775` are commonly appropriate on shared hosting, but follow the provider's guidance and avoid world-writable permissions.

## Usage

- Employees sign in and use **My Timecard**.
- Administrators use **Time Cards** and the **Admin** menu.
- Generated payroll PDFs are stored in `storage/reports/` and excluded from Git.
- Application and PHP logs are stored in `storage/logs/` and excluded from Git.

## Optional cron job

Create upcoming pay periods automatically by running this command daily with the correct server path:

```bash
php /full/path/to/timeclock-pro/cron/ensure_pay_period.php
```

## Updating an installation

1. Back up the production database and files.
2. Preserve the server-only `app/config.php`.
3. Upload the new tracked source files.
4. Apply only the new SQL files under `database/migrations/`.
5. Verify login, time entry, PTO totals, report generation, and email delivery.

Never replace production runtime data with files from a source archive.

## Development checks

Run the lightweight regression test with:

```bash
php tests/TimeServiceTest.php
```

GitHub Actions checks every PHP file for syntax errors, runs the regression test, and confirms that known production-only files have not been committed.

## Contributing

Bug reports and contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening an issue or pull request. Security vulnerabilities must be reported privately according to [SECURITY.md](SECURITY.md).

## License

TimeClock Pro is free software released under the [GNU General Public License version 3](LICENSE).

