# Security Policy

TimeClock Pro stores employee and payroll-related information. Security and privacy reports are taken seriously.

## Supported versions

Security fixes are applied to the latest release and the current `main` branch. Older versions may no longer receive patches.

## Reporting a vulnerability

Do not open a public issue containing credentials, employee information, screenshots of production data, exploit instructions, or vulnerability details.

Use the repository's **Security** tab to submit a private vulnerability report. Include the affected version, reproduction steps, potential impact, and any suggested mitigation. Please allow a reasonable period for investigation before public disclosure.

## Deployment safeguards

- Use HTTPS and redirect all HTTP traffic to HTTPS.
- Point the web root to `public/` whenever possible.
- Keep `app/config.php`, database exports, reports, logs, and sessions out of Git.
- Disable debug mode in production.
- Remove temporary diagnostic scripts after troubleshooting.
- Use a dedicated database user with only the privileges the application needs.
- Restrict filesystem write access to the required `storage/` directories.
- Maintain encrypted, tested backups outside the Git repository.
- Review user accounts promptly when staff access changes.
- Add web-server or firewall rate limiting when the application is internet-accessible.

The project is provided without warranty. Each operator is responsible for evaluating whether a deployment satisfies applicable privacy, payroll, employment, and data-retention requirements.

