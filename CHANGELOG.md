# Changelog

Notable changes to TimeClock Pro are documented here.

## Unreleased

### Added

- Complete fresh-install database schema
- Command-line first-administrator setup
- Login throttling
- Open-source contribution and security documentation
- Generic project icon and GitHub community templates
- Regression checks for time-entry validation and repository hygiene

### Security

- Restricted the all-employee timecard overview to administrators
- Added session ID and CSRF token rotation after login
- Changed logout to a CSRF-protected POST request
- Added strict time-entry date and time validation
- Hid server filesystem paths from production error pages
- Strengthened private-directory and security-header protection

