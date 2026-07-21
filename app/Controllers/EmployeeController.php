<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Csrf;
use App\Models\Settings;
use App\Models\PayPeriod;
use App\Models\Timecard;
use App\Models\TimeEntry;
use App\Services\TimeService;

final class EmployeeController {

  private static function getCurrentPayPeriod(): array {
    return \current_pay_period();
  }

  public static function home(): void {
    Auth::requireLogin();
    redirect('/timecards');
  }

  public static function dashboard(): void {
    Auth::requireLogin();
    redirect('/timecards');
  }

  public static function timecard(): void {
    Auth::requireLogin();
    $u = Auth::user();
    $pp = \selected_pay_period((int)($_GET['pp'] ?? 0));

    $sv = Settings::settingsVersion((int)$pp['settings_version_id']);
    $cfg = $sv['config'];

    $tc = Timecard::ensure((int)$u['id'], (int)$pp['id']);
    $entries = TimeEntry::byUserPeriod((int)$u['id'], (int)$pp['id']);

    $daysToShow = $cfg['days_to_show'] ?? [1,2,3,4,5,6];
    $dayRows = [];
    $d = new \DateTimeImmutable($pp['start_date']);
    $endD = new \DateTimeImmutable($pp['end_date']);
    while ($d <= $endD) {
      $dow = (int)$d->format('N');
      if (in_array($dow, $daysToShow, true)) {
        $date = $d->format('Y-m-d');
        $e = $entries[$date] ?? null;
        $dayRows[] = [
          'date' => $date,
          'day_name' => $d->format('l'),
          'time_in' => $e['time_in'] ?? null,
          'time_out' => $e['time_out'] ?? null,
          'minutes' => $e ? TimeService::durationMinutes($e['time_in'], $e['time_out']) : 0,
        ];
      }
      $d = $d->modify('+1 day');
    }

    $year = (int)(new \DateTimeImmutable('today'))->format('Y');
    $usedYtd = Timecard::sumPtoUsedYtd((int)$u['id'], $year);
    $bank = (int)$u['pto_bank_minutes'];
    $avail = max(0, $bank - $usedYtd);
    $periods = \recent_pay_periods_with_selected($pp, 8);

    View::render('employee/timecard', [
      'title' => 'Timecard',
      'user' => $u,
      'payPeriod' => $pp,
      'periods' => $periods,
      'settings' => $cfg,
      'pto_bank' => $bank,
      'pto_used_ytd' => $usedYtd,
      'pto_available' => $avail,
      'timecard' => $tc,
      'dayRows' => $dayRows,
    ]);
  }

  public static function saveTimecard(): void {
    Auth::requireLogin();
    $u = Auth::user();
    Csrf::check($_POST['_csrf'] ?? null);

    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $pp = PayPeriod::findById($ppId);
    if (!$pp) { http_response_code(400); echo "Invalid pay period."; return; }
    $_SESSION['selected_pay_period_id'] = $ppId;

    $tc = Timecard::ensure((int)$u['id'], (int)$ppId);
    if (!empty($tc['locked_at'])) { flash_set('error','Timecard is locked.'); redirect('/timecard?pp=' . $ppId); }

    $sv = Settings::settingsVersion((int)$pp['settings_version_id']);
    $cfg = $sv['config'];

    $rows = $_POST['rows'] ?? [];
    foreach ($rows as $workDate => $vals) {
      $tin = trim((string)($vals['in'] ?? ''));
      $tout = trim((string)($vals['out'] ?? ''));

      $tin = $tin !== '' ? TimeService::roundTime($tin, $cfg) : null;
      $tout = $tout !== '' ? TimeService::roundTime($tout, $cfg) : null;

      if ($tin === null && $tout === null) {
        TimeEntry::deleteForDate((int)$u['id'], (int)$ppId, $workDate);
      } else {
        TimeEntry::upsert((int)$u['id'], (int)$ppId, $workDate, $tin, $tout);
      }
    }

    $ptoHrs = trim((string)($_POST['pto_hours'] ?? ''));
    $ptoMin = 0;
    if ($ptoHrs !== '') {
      $f = (float)$ptoHrs;
      $ptoMin = (int)round($f * 60);
      $inc = (int)($cfg['time_increment_minutes'] ?? 15);
      if ($inc > 0) $ptoMin = (int)round($ptoMin / $inc) * $inc;
      $ptoMin = max(0, $ptoMin);
    }

    $wlUnits = 0;
    if (!empty($u['weight_loss_consultant'])) {
      $wlUnits = (int)($_POST['weight_loss_units'] ?? 0);
      if ($wlUnits < 0) $wlUnits = 0;
    }

    $nursingEncounters = 0;
    if (!empty($u['nursing_encounters_enabled'])) {
      $nursingEncounters = (int)($_POST['nursing_encounters'] ?? 0);
      if ($nursingEncounters < 0) $nursingEncounters = 0;
    }

    Timecard::update((int)$u['id'], (int)$ppId, ['pto_minutes' => $ptoMin, 'weight_loss_units' => $wlUnits, 'nursing_encounters' => $nursingEncounters]);

    flash_set('ok', 'Saved.');
    redirect('/timecard?pp=' . $ppId);
  }

  public static function submitTimecard(): void {
    Auth::requireLogin();
    $u = Auth::user();
    Csrf::check($_POST['_csrf'] ?? null);

    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    $tc = Timecard::ensure((int)$u['id'], $ppId);
    if (!empty($tc['locked_at'])) { flash_set('error','Timecard is locked.'); redirect('/timecard?pp=' . $ppId); }

    Timecard::setSubmitted((int)$u['id'], $ppId);
    flash_set('ok', 'Submitted for approval.');
    redirect('/timecard?pp=' . $ppId);
  }
}
