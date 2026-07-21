# FAQ / Knowledge Base

## Employees

### How do I clock in/out?
This system is built around a **bi-weekly timecard** (not a live punch clock). You select your **Time IN** and **Time OUT** for each work day in the current pay period, then save.

### Why can I only pick times like :00, :15, :30, :45?
Time entries are restricted to a configured increment (default **15 minutes**) to match how your office records time. Admin can change this in **Settings**.

### What does “Save Changes” do?
It saves your timecard as a draft so you can come back later.

### What does “Final Submit” do?
It marks your timecard as submitted for admin review. You’ll be prompted to confirm your hours/PTO are correct.

### Why can’t I edit my timecard anymore?
If it’s **locked**, an admin has finalized it for payroll reporting.

### How is my PTO shown?
- **PTO Bank**: set by admin on your employee profile
- **PTO Used YTD**: sum of PTO hours entered on timecards for the current year
- **PTO Available**: bank − used YTD

---

## Super Users (Admin)

### How do I create an employee?
Admin → Employees → Add Employee

### How do password resets work?
Admin → Employee → Reset Password
It requires:
1) Your admin password, AND
2) typing the employee email to confirm

### How do hard deletes work?
Admin → Employee → Hard Delete
It requires:
1) Your admin password, AND
2) typing **DELETE** to confirm

This permanently deletes the employee and related records.

### How do I see who has submitted their timecard?
Admin Dashboard shows Submitted/Locked status per employee for the current pay period.

### How do I edit an employee timecard?
Admin → Dashboard → View/Edit
Or Admin → Timecards → View/Edit

### What does “Lock Timecard” do?
Locks that employee’s timecard for the pay period so no further edits can be made unless unlocked.

### What does “Lock Pay Period + Generate PDF + Email” do?
- Locks all employee timecards for the pay period
- Locks the pay period (global)
- Generates a PDF report into `/storage/reports/`
- Emails the PDF to the report email set in Settings

---

## Settings / Preferences

### What settings can I change?
- Increment minutes (5/10/15/20/30)
- Rounding mode (nearest/up/down)
- Days shown on the timecard (Mon–Sun checkboxes)
- Company name + report email destination
- Pay period anchor date + length

### What does “Apply immediately” mean?
It assigns the new settings version to the **current pay period** if it’s not locked.

### What does “Apply starting next pay period only” mean?
It preserves the current pay period’s rules and only affects newly created pay periods.

### What does “Re-round existing unlocked times” do?
If you apply immediately, this option recalculates (re-snaps) IN/OUT times for all **unlocked** timecards in the current pay period, using the new rounding rules.

---

## Technical

### Where are PDF reports saved?
`/storage/reports/`
Files are named:
`TIMECARD_YYYY-MM-DD-through-YYYY-MM-DD.pdf`

### Why didn’t my email send?
Some shared hosts block or rate-limit PHP `mail()`. The PDF is still generated. If you need guaranteed delivery, swap the mailer to SMTP (PHPMailer) or an email API.

### Can I run this under WordPress?
This is a standalone app. You *can* run it alongside WordPress in a subfolder, but it does not depend on WordPress.


---

## Debugging

### How do I turn debugging on/off?
Create or delete this file:
- `storage/DEBUG_ON`  (empty file)

### Where do I find errors?
- `storage/logs/app-YYYY-MM-DD.log` (best place)
- `storage/logs/php-error.log` (if your host allows setting PHP error_log)
- In the browser (only when debug is ON) you’ll see a stack trace and a **Request ID**.

### Can I see browser console errors in the server log?
Yes—when debug is ON, the layout includes an optional client-side error capture that POSTs JavaScript errors to:
- `/debug/client-error`
These get written to the same app log file.
