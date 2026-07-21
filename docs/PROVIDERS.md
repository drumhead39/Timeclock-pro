# Provider payroll setup (Admin-only)

This release adds **provider production pay** alongside hourly staff timecards.

Providers do **not** log in. Super users enter counts for provider appointment types and lock the provider production card for a pay period. Rates are **provider-specific** and **effective-dated**.

## 1) Run the database migration (existing installs)

In phpMyAdmin, select your TimeClock Pro database and run:

- `database/migrations/2026-01-09_add_providers.sql`

This creates:
- `providers`
- `appointment_types` (seeded with default chiro + massage types)
- `provider_rates` (effective-dated per provider+type)
- `provider_production` (counts per pay period)

## 2) Admin workflow

1. **Admin → Providers**
   - Add providers and choose type: Chiropractor (chiro) or Massage (massage)

2. **Admin → Appointment Types**
   - Add/disable appointment types as needed (future proof)

3. **Admin → Provider Rates**
   - For each provider, add rates per appointment type with an **Effective From** date.
   - When you add a new rate, the system auto-closes the previous open-ended rate the day before the new effective date.

4. **Admin → Provider Production**
   - Select a pay period.
   - Enter appointment counts.
   - Save and **Lock** when complete.
   - Locking snapshots each line's **rate_used** (so history does not change when rates change later).

## Reporting

The pay period PDF report includes a **Provider Production** section after hourly timecards:
- Type, count, rate used, line total, provider total.
