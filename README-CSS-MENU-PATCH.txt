TimeClock Pro patch - Admin dropdown / Time Cards layout / mobile cleanup

Upload these files over the existing files in your live timeclock-pro folder:

app/Views/layout.php
app/Views/admin/timecards.php
app/Controllers/AdminController.php
app/Controllers/ProviderController.php
public/assets/css/app.css

Changes:
- Admin menu changed to a real click-to-open dropdown using <details>/<summary>.
- CSS cache-busting was added to the stylesheet link.
- Time Cards Employees and Providers sections now stack vertically on desktop and mobile.
- Provider production access is now super-user-only; no provider name matching.
- Added extra mobile CSS cleanup for nav/dropdown/table wrapping.

No database change required.
