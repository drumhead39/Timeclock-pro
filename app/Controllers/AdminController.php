<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Csrf;
use App\Core\DB;
use App\Models\User;
use App\Models\PayPeriod;
use App\Models\Timecard;
use App\Models\TimeEntry;
use App\Models\Settings;
use App\Services\TimeService;
use App\Services\ReportService;
use App\Services\MailerService;
use App\Models\Provider;
use App\Models\ProviderProduction;
use App\Models\ProviderPto;

final class AdminController {

  private static function getCurrentPayPeriod(): array {
    return \current_pay_period();
  }
  
  private static function getPayPeriodFromRequest(): array {
    return \selected_pay_period((int)($_GET["pp"] ?? 0));
  }

  private static function listRecentPeriodsWithSelected(array $selected, int $limit = 8): array {
    return \recent_pay_periods_with_selected($selected, $limit);
  }


  public static function dashboard(): void {
    Auth::requireRole('super');
    redirect('/admin/employees');
  }

  public static function employees(): void {
    Auth::requireRole('super');
    View::render('admin/employees', [
      'title' => 'Employees',
      'user' => Auth::user(),
      'employees' => User::all(),
    ]);
  }

  public static function employeeEdit(array $params): void {
    Auth::requireRole('super');
    $id = (int)($params['id'] ?? 0);
    $emp = $id ? User::findById($id) : null;
    View::render('admin/employee_edit', [
      'title' => $id ? 'Edit Employee' : 'Add Employee',
      'user' => Auth::user(),
      'emp' => $emp,
    ]);
  }

  public static function employeeSave(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $id = (int)($_POST['id'] ?? 0);
    $full = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = ($_POST['role'] ?? 'employee') === 'super' ? 'super' : 'employee';
    $active = isset($_POST['active']) ? 1 : 0;
    $wlc = isset($_POST['weight_loss_consultant']) ? 1 : 0;
    $nec = isset($_POST['nursing_encounters_enabled']) ? 1 : 0;
    $ptoBankHrs = (float)($_POST['pto_bank_hours'] ?? 0);
    $ptoBankMin = (int)round(max(0, $ptoBankHrs) * 60);

    if ($full === '' || $email === '') { flash_set('error','Name and email required.'); redirect($id?"/admin/employees/$id":"\/admin/employees/new"); }

    if ($id) {
      User::update($id, [
        'full_name'=>$full,'email'=>$email,'role'=>$role,'active'=>$active,'weight_loss_consultant'=>$wlc,'nursing_encounters_enabled'=>$nec,'pto_bank_minutes'=>$ptoBankMin
      ]);
    } else {
      $pw = (string)($_POST['password'] ?? '');
      if (strlen($pw) < 8) { flash_set('error','Password must be at least 8 characters.'); redirect('/admin/employees/new'); }
      $hash = password_hash($pw, PASSWORD_DEFAULT);
      User::create([
        'full_name'=>$full,'email'=>$email,'role'=>$role,'active'=>$active,'weight_loss_consultant'=>$wlc,'nursing_encounters_enabled'=>$nec,'pto_bank_minutes'=>$ptoBankMin,'password_hash'=>$hash
      ]);
    }
    flash_set('ok','Saved.');
    redirect('/admin/employees');
  }

  public static function employeeResetPassword(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $admin = Auth::user();
    $adminPass = (string)($_POST['admin_password'] ?? '');
    if (!password_verify($adminPass, $admin['password_hash'])) {
      flash_set('error','Admin password incorrect.');
      redirect('/admin/employees');
    }

    $empId = (int)($_POST['emp_id'] ?? 0);
    $emp = User::findById($empId);
    if (!$emp) { flash_set('error','Employee not found.'); redirect('/admin/employees'); }

    $confirmEmail = trim((string)($_POST['confirm_email'] ?? ''));
    if (strtolower($confirmEmail) !== strtolower($emp['email'])) {
      flash_set('error','Confirmation email does not match.');
      redirect('/admin/employees/' . $empId);
    }

    $newPw = (string)($_POST['new_password'] ?? '');
    if (strlen($newPw) < 8) { flash_set('error','New password must be at least 8 characters.'); redirect('/admin/employees/' . $empId); }

    User::setPassword($empId, password_hash($newPw, PASSWORD_DEFAULT));
    flash_set('ok','Password updated.');
    redirect('/admin/employees/' . $empId);
  }

  public static function employeeDelete(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $admin = Auth::user();
    $adminPass = (string)($_POST['admin_password'] ?? '');
    if (!password_verify($adminPass, $admin['password_hash'])) {
      flash_set('error','Admin password incorrect.');
      redirect('/admin/employees');
    }

    $empId = (int)($_POST['emp_id'] ?? 0);
    $emp = User::findById($empId);
    if (!$emp) { flash_set('error','Employee not found.'); redirect('/admin/employees'); }

    $confirm = trim((string)($_POST['confirm_text'] ?? ''));
    if ($confirm !== 'DELETE') {
      flash_set('error','Type DELETE to confirm hard delete.');
      redirect('/admin/employees/' . $empId);
    }

    // Prevent deleting self
    if ((int)$emp['id'] === (int)$admin['id']) {
      flash_set('error','You cannot delete your own account.');
      redirect('/admin/employees/' . $empId);
    }

    User::deleteHard($empId);
    flash_set('ok','Employee deleted (hard delete).');
    redirect('/admin/employees');
  }

  public static function timecards(): void {
    Auth::requireLogin();
    $u = Auth::user();
    $pp = self::getPayPeriodFromRequest();
    $employeeStatus = Timecard::statusForPayPeriod((int)$pp['id']);
    $providerStatus = ProviderProduction::statusForPayPeriod((int)$pp['id']);

    $accessibleProviderIds = [];

    View::render('admin/timecards', [
      'title' => 'Time Cards',
      'user' => $u,
      'payPeriod' => $pp,
      'recentPeriods' => self::listRecentPeriodsWithSelected($pp, 8),
      'employeeStatus' => $employeeStatus,
      'providerStatus' => $providerStatus,
      'accessibleProviderIds' => $accessibleProviderIds,
      'isSuper' => ($u && $u['role'] === 'super'),
    ]);
  }

  public static function timecardEdit(array $params): void {
    Auth::requireRole('super');
    $empId = (int)($params['id'] ?? 0);
    $emp = User::findById($empId);
    if (!$emp) { http_response_code(404); echo "Employee not found."; return; }

    $pp = self::getPayPeriodFromRequest();
    $tc = Timecard::ensure($empId, (int)$pp['id']);
    $sv = Settings::settingsVersion((int)$pp['settings_version_id']);
    $cfg = $sv['config'];

    $entries = TimeEntry::byUserPeriod($empId, (int)$pp['id']);

    $daysToShow = $cfg['days_to_show'] ?? [1,2,3,4,5,6];
    $rows = [];
    $d = new \DateTimeImmutable($pp['start_date']);
    $endD = new \DateTimeImmutable($pp['end_date']);
    while ($d <= $endD) {
      $dow = (int)$d->format('N');
      if (in_array($dow, $daysToShow, true)) {
        $date = $d->format('Y-m-d');
        $e = $entries[$date] ?? null;
        $rows[] = [
          'date'=>$date,
          'day_name'=>$d->format('l'),
          'time_in'=>$e['time_in'] ?? null,
          'time_out'=>$e['time_out'] ?? null,
          'minutes'=>$e ? TimeService::durationMinutes($e['time_in'], $e['time_out']) : 0,
        ];
      }
      $d = $d->modify('+1 day');
    }
    $year = (int)(new \DateTimeImmutable('today'))->format('Y');
    $usedYtd = Timecard::sumPtoUsedYtd((int)$emp['id'], $year);
    $bank = (int)$emp['pto_bank_minutes'];
    $avail = max(0, $bank - $usedYtd);

    View::render('admin/timecard_edit', [
      'title' => 'Edit Timecard',
      'user' => Auth::user(),
      'emp' => $emp,
      'payPeriod' => $pp,
      'pto_bank' => $bank,
      'pto_used_ytd' => $usedYtd,
      'pto_available' => $avail,
      'timecard' => $tc,
      'settings' => $cfg,
      'dayRows' => $rows,
    ]);
  }

  public static function timecardSave(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $empId = (int)($_POST['emp_id'] ?? 0);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;

    $pp = PayPeriod::findById($ppId);
    if (!$pp) { http_response_code(400); echo "Invalid pay period."; return; }

    $tc = Timecard::ensure($empId, $ppId);
    if (!empty($tc['locked_at'])) { flash_set('error','Timecard is locked.'); redirect('/admin/timecards/' . $empId . '?pp=' . $ppId); }

    $sv = Settings::settingsVersion((int)$pp['settings_version_id']);
    $cfg = $sv['config'];

    $rows = $_POST['rows'] ?? [];
    foreach ($rows as $workDate => $vals) {
      $tin = trim((string)($vals['in'] ?? ''));
      $tout = trim((string)($vals['out'] ?? ''));

      $tin = $tin !== '' ? TimeService::roundTime($tin, $cfg) : null;
      $tout = $tout !== '' ? TimeService::roundTime($tout, $cfg) : null;

      if ($tin === null && $tout === null) {
        TimeEntry::deleteForDate($empId, $ppId, $workDate);
      } else {
        TimeEntry::upsert($empId, $ppId, $workDate, $tin, $tout);
      }
    }

    $ptoHrs = trim((string)($_POST['pto_hours'] ?? ''));
    $ptoMin = 0;
    if ($ptoHrs !== '') {
      $ptoMin = (int)round(((float)$ptoHrs) * 60);
      $inc = (int)($cfg['time_increment_minutes'] ?? 15);
      if ($inc > 0) $ptoMin = (int)round($ptoMin / $inc) * $inc;
      $ptoMin = max(0, $ptoMin);
    }


$wlUnits = 0;
$nursingEncounters = 0;
$emp = User::findById($empId);
if ($emp && !empty($emp['weight_loss_consultant'])) {
  $wlUnits = (int)($_POST['weight_loss_units'] ?? 0);
  if ($wlUnits < 0) $wlUnits = 0;
}
if ($emp && !empty($emp['nursing_encounters_enabled'])) {
  $nursingEncounters = (int)($_POST['nursing_encounters'] ?? 0);
  if ($nursingEncounters < 0) $nursingEncounters = 0;
}
    Timecard::update($empId, $ppId, ['pto_minutes' => $ptoMin, 'weight_loss_units' => $wlUnits, 'nursing_encounters' => $nursingEncounters]);

    flash_set('ok','Saved.');
    redirect('/admin/timecards/' . $empId . '?pp=' . $ppId);
  }

  public static function timecardLock(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);
    $empId = (int)($_POST['emp_id'] ?? 0);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    // When an admin locks a card, treat it as "final" for the period.
    Timecard::ensure($empId, $ppId);
    Timecard::markSubmittedIfNull($empId, $ppId);
    Timecard::setLocked($empId, $ppId, (int)Auth::user()['id']);
    flash_set('ok','Locked.');
    redirect('/admin/timecards/' . $empId . '?pp=' . $ppId);
  }

  public static function timecardUnlock(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);
    $empId = (int)($_POST['emp_id'] ?? 0);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    Timecard::setUnlocked($empId, $ppId);
    flash_set('ok','Unlocked.');
    redirect('/admin/timecards/' . $empId . '?pp=' . $ppId);
  }

  private static function lockEverythingForReport(array $pp): void {
    $ppId = (int)$pp['id'];
    $adminId = (int)Auth::user()['id'];

    // Lock all employee timecards, including any corrected card that was unlocked for editing.
    $users = DB::pdo()->query("SELECT id FROM users WHERE active=1")->fetchAll();
    foreach ($users as $r) {
      $uid = (int)$r['id'];
      Timecard::ensure($uid, $ppId);
      // Treat an admin-locked card as final/submitted without overwriting an employee's original submit time.
      Timecard::markSubmittedIfNull($uid, $ppId);
      Timecard::setLocked($uid, $ppId, $adminId);
    }

    // Lock provider production and PTO as well, so provider corrections are included in the regenerated report.
    ProviderProduction::lockAllForPayPeriod($ppId, $adminId, (string)$pp['end_date']);
    ProviderPto::lockAllForPayPeriod($ppId, $adminId);

    // Lock the pay period globally if it is not already locked.
    PayPeriod::lock($ppId, $adminId);
  }

  private static function generateEmailAndStoreReport(int $ppId, array $pp, bool $isUpdate): bool {
    $report = ReportService::generatePayPeriodPdf($ppId, __DIR__ . '/../../storage/reports');

    $range = \fmt_date($pp['start_date']) . " through " . \fmt_date($pp['end_date']);
    $subject = ($isUpdate ? "UPDATED Timecard Report " : "Timecard Report ") . $range;
    $body = $isUpdate
      ? "Attached is the UPDATED timecard report for {$range}. This replaces the previously sent report for this pay period."
      : "Attached is the timecard report for {$range}.";

    $ok = MailerService::sendWithAttachment(
      $report['email_to'],
      $subject,
      $body,
      $report['path'],
      $report['filename']
    );

    // The PDF filename is period-based, so ReportService overwrites the old PDF on disk.
    // Keep the Reports table to one current row per pay period so downloads point to the latest file.
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM reports WHERE pay_period_id=?")->execute([$ppId]);
    $pdo->prepare("INSERT INTO reports(pay_period_id,filename,filepath,created_at,emailed_to,email_status)
      VALUES(?,?,?,?,?,?)")->execute([
        $ppId,
        $report['filename'],
        $report['path'],
        date('Y-m-d H:i:s'),
        $report['email_to'],
        $ok ? ($isUpdate ? 'resent' : 'sent') : 'failed'
    ]);

    return $ok;
  }

  public static function lockGenerateReport(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    $pp = PayPeriod::findById($ppId);
    if (!$pp) { flash_set('error','Invalid pay period.'); redirect('/timecards?pp=' . $ppId); }

    self::lockEverythingForReport($pp);
    $ok = self::generateEmailAndStoreReport($ppId, $pp, false);

    flash_set('ok', $ok ? 'Pay period locked. PDF generated and emailed.' : 'Pay period locked. PDF generated, but email failed (check server mail settings).');
    redirect('/timecards?pp=' . $ppId);
  }

  public static function regeneratePayPeriodReport(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    $pp = PayPeriod::findById($ppId);
    if (!$pp) { flash_set('error','Invalid pay period.'); redirect('/timecards?pp=' . $ppId); }

    self::lockEverythingForReport($pp);
    $ok = self::generateEmailAndStoreReport($ppId, $pp, true);

    flash_set('ok', $ok ? 'Updated PDF regenerated, stored, and emailed.' : 'Updated PDF regenerated and stored, but email failed (check server mail settings).');
    redirect('/timecards?pp=' . $ppId);
  }
}
