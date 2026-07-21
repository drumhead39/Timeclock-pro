# Contributing to TimeClock Pro

Thank you for helping improve TimeClock Pro.

## Before opening an issue

- Search existing issues for the same problem or request.
- Confirm the problem still occurs on the latest release or `main` branch.
- Remove employee names, email addresses, credentials, server paths, and production data from screenshots and logs.
- Report security vulnerabilities privately according to `SECURITY.md`.

## Bug reports

Include the PHP version, MySQL or MariaDB version, hosting environment, relevant steps, expected behavior, actual behavior, and sanitized error output.

## Pull requests

1. Create a focused branch from `main`.
2. Keep the change limited to one concern.
3. Preserve compatibility with PHP 8.0 unless a version change has been discussed.
4. Use prepared database statements for all user-controlled values.
5. Require authentication, authorization, and CSRF checks for sensitive actions.
6. Add or update tests and documentation when behavior changes.
7. Confirm that no configuration, employee data, reports, logs, or session files are included.
8. Describe the change and its verification in the pull request.

By contributing, you agree that your contribution will be licensed under GPLv3.

