<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Csrf;
use App\Core\DB;
use App\Models\Settings;
use App\Models\PayPeriod;
use App\Models\TimeEntry;
use App\Models\Timecard;
use App\Services\TimeService;

final class SettingsController {

  private static function getCurrentPayPeriod(): array {
    $state = Settings::appState();
    $anchor = new \DateTimeImmutable($state['pay_period_anchor_date']);
    $len = (int)$state['pay_period_length_days'];
    $today = new \DateTimeImmutable('today');
    $diffDays = (int)$anchor->diff($today)->format('%r%a');
    $idx = (int)floor($diffDays / $len);
    if ($diffDays < 0) $idx = (int)ceil($diffDays / $len) - 1;
    $start = $anchor->modify('+' . ($idx*$len) . ' days');
    $end = $start->modify('+' . ($len-1) . ' days');
    $sv = (int)$state['current_settings_version_id'];
    return PayPeriod::ensure($start->format('Y-m-d'), $end->format('Y-m-d'), $sv);
  }

  public static function settings(): void {
    Auth::requireRole('super');
    $state = Settings::appState();
    $cur = Settings::currentSettingsVersion();
    View::render('admin/settings', [
      'title' => 'Settings',
      'user' => Auth::user(),
      'state' => $state,
      'current' => $cur,
    ]);
  }

  public static function save(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $actor = (int)Auth::user()['id'];
    $state = Settings::appState();
    $current = Settings::currentSettingsVersion();
    $cfg = $current['config'];

    $inc = max(1, (int)($_POST['time_increment_minutes'] ?? ($cfg['time_increment_minutes'] ?? 15)));
    $mode = in_array($_POST['rounding_mode'] ?? '', ['nearest','down','up'], true) ? $_POST['rounding_mode'] : ($cfg['rounding_mode'] ?? 'nearest');

    $daysToShow = $_POST['days_to_show'] ?? [];
    $daysToShow = array_values(array_filter(array_map('intval', (array)$daysToShow), fn($n)=>$n>=1 && $n<=7));
    if (!$daysToShow) $daysToShow = $cfg['days_to_show'] ?? [1,2,3,4,5,6];

    // allowed minutes derived from increment
    $allowed = [];
    for ($m=0; $m<60; $m+=$inc) $allowed[] = $m;

    $newCfg = $cfg;
    $newCfg['time_increment_minutes'] = $inc;
    $newCfg['allowed_minutes'] = $allowed;
    $newCfg['rounding_mode'] = $mode;
    $newCfg['days_to_show'] = $daysToShow;

    // App state items
    $companyName = trim((string)($_POST['company_name'] ?? $state['company_name']));
    $companyEmail = trim((string)($_POST['company_email'] ?? $state['company_email']));
    $anchor = trim((string)($_POST['pay_period_anchor_date'] ?? $state['pay_period_anchor_date']));
    $length = max(7, (int)($_POST['pay_period_length_days'] ?? $state['pay_period_length_days']));

    $scope = ($_POST['apply_scope'] ?? 'next') === 'immediate' ? 'immediate' : 'next';
    $reround = isset($_POST['reround_existing']) && $scope === 'immediate';

    $newVersionId = Settings::createSettingsVersion($actor, $newCfg);

    // Update app_state defaults for next periods
    Settings::setCurrentSettingsVersion($newVersionId);
    Settings::updateAppState([
      'company_name' => $companyName,
      'company_email' => $companyEmail,
      'pay_period_anchor_date' => $anchor,
      'pay_period_length_days' => $length,
    ]);

    if ($scope === 'immediate') {
      $pp = self::getCurrentPayPeriod();
      // Only if the pay period is not globally locked
      if (empty($pp['locked_at'])) {
        PayPeriod::setSettingsVersion((int)$pp['id'], $newVersionId);

        if ($reround) {
          // Reround all time_entries in this pay period that belong to unlocked timecards
          $sql = "SELECT te.* FROM time_entries te
            JOIN timecards tc ON tc.user_id=te.user_id AND tc.pay_period_id=te.pay_period_id
            WHERE te.pay_period_id=? AND (tc.locked_at IS NULL)";
          $st = DB::pdo()->prepare($sql);
          $st->execute([(int)$pp['id']]);
          $rows = $st->fetchAll();
          foreach ($rows as $r) {
            $tin = $r['time_in'] ? TimeService::roundTime(substr($r['time_in'],0,5), $newCfg) : null;
            $tout = $r['time_out'] ? TimeService::roundTime(substr($r['time_out'],0,5), $newCfg) : null;
            DB::pdo()->prepare("UPDATE time_entries SET time_in=?, time_out=?, updated_at=NOW() WHERE id=?")
              ->execute([$tin, $tout, (int)$r['id']]);
          }
        }
      }
    }

    // Audit
    DB::pdo()->prepare("INSERT INTO audit_log(actor_user_id,action,entity,entity_id,payload_json,created_at)
      VALUES(?,?,?,?,?,NOW())")->execute([
      $actor, 'settings_saved', 'settings_versions', $newVersionId,
      json_encode(['apply_scope'=>$scope,'reround_existing'=>$reround], JSON_UNESCAPED_SLASHES)
    ]);

    flash_set('ok', 'Settings saved.');
    redirect('/admin/settings');
  }
}
