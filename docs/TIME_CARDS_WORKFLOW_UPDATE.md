# Time Cards Workflow Update

## Summary

This update changes the primary working screen from separate Home/Admin views to one shared **Time Cards** page.

## Included changes

- Added `/timecards` as the default landing page after login.
- Removed Home/Admin redundancy from the visible navigation.
- Added a super-user-only **Admin** dropdown for setup screens:
  - Employees
  - Providers
  - Appointment Types
  - Settings
- Reworked the Time Cards page into two sections:
  - Employees: hourly time cards
  - Providers: production / quantity cards
- Added pay-period selection to the Time Cards page.
- The selected pay period persists while logged in and across Time Cards, employee timecard edit, and provider production edit screens.
- Fresh login defaults back to the current pay period.
- Kept employee timecards and provider production separate internally, but displayed them together for convenience.
- Added public working routes for provider production cards:
  - `/provider-production/{id}`
  - `/provider-production/save`
  - `/provider-production/lock`
  - `/provider-production/unlock`
- Regular users can edit their own employee timecard.
- Super users can edit all employee timecards and all provider production cards.
- Regular users can edit a provider production card only when the provider name exactly matches the logged-in user's full name.
- Added responsive/mobile CSS so time entry and production entry screens stack into mobile-friendly cards instead of requiring horizontal scrolling.
- Kept the prior locked pay-period regeneration/resend update:
  - `Regenerate PDF + Email` appears for locked pay periods on the Time Cards page.
  - It re-locks corrected/unlocked cards, regenerates the PDF using the same filename, stores it, and emails it again.

## No database migration required

This update uses the existing employee, provider, pay period, timecard, provider production, PTO, report, and settings tables.
