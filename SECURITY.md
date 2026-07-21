# Security Policy

TimeClock Pro stores employee and payroll-related information. Keep production credentials, session files, logs, generated reports, and database exports out of this repository.

## Reporting a vulnerability

Do not open a public issue containing credentials, employee information, screenshots of production data, or exploit details. Report security concerns privately to the repository owner.

## Deployment safeguards

- Keep the repository private unless the code has been independently reviewed for public release.
- Serve the application over HTTPS.
- Point the web root to `public/` whenever possible.
- Copy `app/config.sample.php` to `app/config.php` only on the server and never commit the resulting file.
- Disable debug mode in production.
- Remove temporary diagnostic scripts after troubleshooting.
- Restrict write access to the required `storage/` directories.
- Back up the database and generated reports outside the Git repository.

