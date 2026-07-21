<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Csrf;
use App\Core\DB;
use App\Models\PayPeriod;
use App\Models\Settings;
use App\Models\Provider;
use App\Models\AppointmentType;
use App\Models\ProviderRate;
use App\Models\ProviderProduction;
use App\Models\ProviderPto;

final class ProviderController {

  private static function validDate(string $value): bool {
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
  }

  
  private static function getPayPeriodFromRequest(): array {
    return \selected_pay_period((int)($_GET["pp"] ?? 0));
  }

  private static function recentPeriodsWithSelected(array $selected, int $limit = 8): array {
    return \recent_pay_periods_with_selected($selected, $limit);
  }

  private static function canAccessProvider(int $providerId): bool {
    Auth::requireLogin();
    $u = Auth::user();
    return $u && $u['role'] === 'super';
  }

  private static function requireProviderAccess(int $providerId): void {
    if (!self::canAccessProvider($providerId)) {
      http_response_code(403);
      echo "<h1>403</h1><p>Forbidden</p>";
      exit;
    }
  }

  public static function providers(): void {
    Auth::requireRole('super');
    View::render('admin/providers', [
      'title' => 'Providers',
      'user' => Auth::user(),
      'providers' => Provider::all(true),
    ]);
  }

  public static function providerEdit(array $params): void {
    Auth::requireRole('super');
    $id = (int)($params['id'] ?? 0);
    $prov = $id ? Provider::findById($id) : null;
    View::render('admin/provider_edit', [
      'title' => $id ? 'Edit Provider' : 'Add Provider',
      'user' => Auth::user(),
      'prov' => $prov,
    ]);
  }

  public static function providerSave(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['full_name'] ?? ''));
    $ptype = (string)($_POST['provider_type'] ?? 'chiro');
    $ptype = in_array($ptype, ['chiro','massage'], true) ? $ptype : 'chiro';
    $active = isset($_POST['active']) ? 1 : 0;

    // Provider PTO bank (hours, decimal). Stored as minutes.
    $ptoBankHours = (string)($_POST['pto_bank_hours'] ?? '');
    $ptoBankHours = trim($ptoBankHours);
    $ptoBankMin = 0;
    if ($ptoBankHours !== '') {
      $h = (float)$ptoBankHours;
      if ($h < 0) $h = 0;
      // keep quarter-hour granularity
      $ptoBankMin = (int)round($h * 60);
    }

    if ($name === '' || strlen($name) > 190) {
      flash_set('error','Provider name is required.');
      redirect('/admin/providers');
    }

    if ($id) {
      Provider::update($id, ['full_name'=>$name,'provider_type'=>$ptype,'active'=>$active,'pto_bank_minutes'=>$ptoBankMin]);
    } else {
      Provider::create(['full_name'=>$name,'provider_type'=>$ptype,'active'=>$active,'pto_bank_minutes'=>$ptoBankMin]);
    }

    flash_set('ok','Saved.');
    redirect('/admin/providers');
  }

  public static function appointmentTypes(): void {
  Auth::requireRole('super');

  $editId = (int)($_GET['edit_id'] ?? 0);
  $editType = $editId > 0 ? AppointmentType::findById($editId) : null;

  View::render('admin/appointment_types', [
    'title' => 'Appointment Types',
    'user' => Auth::user(),
    'types_chiro' => AppointmentType::listByProviderType('chiro', true),
    'types_massage' => AppointmentType::listByProviderType('massage', true),
    'editType' => $editType,
  ]);
}

  public static function appointmentTypeSave(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $id = (int)($_POST['id'] ?? 0);
    $ptype = (string)($_POST['provider_type'] ?? 'chiro');
    $ptype = in_array($ptype, ['chiro','massage'], true) ? $ptype : 'chiro';
    $name = trim((string)($_POST['name'] ?? ''));
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    // Provider PTO bank (hours, decimal). Stored as minutes.
    $ptoBankHours = (string)($_POST['pto_bank_hours'] ?? '');
    $ptoBankHours = trim($ptoBankHours);
    $ptoBankMin = 0;
    if ($ptoBankHours !== '') {
      $h = (float)$ptoBankHours;
      if ($h < 0) $h = 0;
      // keep quarter-hour granularity
      $ptoBankMin = (int)round($h * 60);
    }

    if ($name === '' || strlen($name) > 190) {
      flash_set('error','Type name is required.');
      redirect('/admin/appointment-types');
    }

    if ($id) {
      AppointmentType::update($id, ['provider_type'=>$ptype,'name'=>$name,'sort_order'=>$sort,'active'=>$active]);
    } else {
      AppointmentType::create(['provider_type'=>$ptype,'name'=>$name,'sort_order'=>$sort,'active'=>$active]);
    }

    flash_set('ok','Saved.');
    redirect('/admin/appointment-types');
  }

  public static function appointmentTypeToggle(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $id = (int)($_POST['id'] ?? 0);
    $active = (int)($_POST['active'] ?? 0) === 1 ? 1 : 0;
    AppointmentType::setActive($id, $active);
    flash_set('ok','Updated.');
    redirect('/admin/appointment-types');
  }


public static function appointmentTypeDelete(): void {
  Auth::requireRole('super');
  Csrf::check($_POST['_csrf'] ?? null);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    flash_set('error','Invalid appointment type.');
    redirect('/admin/appointment-types');
  }

  if (!AppointmentType::canDelete($id)) {
    flash_set('error','This appointment type is already in use (rates or production). Deactivate it instead of deleting.');
    redirect('/admin/appointment-types');
  }

  AppointmentType::delete($id);
  flash_set('ok','Deleted.');
  redirect('/admin/appointment-types');
}

  public static function providerRates(array $params): void {
    Auth::requireRole('super');
    $providerId = (int)($params['id'] ?? 0);
    $prov = Provider::findById($providerId);
    if (!$prov) { http_response_code(404); echo "Provider not found."; return; }

    $types = AppointmentType::listByProviderType((string)$prov['provider_type'], false);
    $rates = ProviderRate::listForProvider($providerId);

    View::render('admin/provider_rates', [
      'title' => 'Provider Rates',
      'user' => Auth::user(),
      'prov' => $prov,
      'types' => $types,
      'rates' => $rates,
    ]);
  }

  public static function providerRateAdd(array $params): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);
    $providerId = (int)($params['id'] ?? 0);
    $prov = Provider::findById($providerId);
    if (!$prov) { flash_set('error','Provider not found.'); redirect('/admin/providers'); }

    $typeId = (int)($_POST['appointment_type_id'] ?? 0);
    $rate = (float)($_POST['rate'] ?? 0);
    $effFrom = (string)($_POST['effective_from'] ?? date('Y-m-d'));
    $effTo = trim((string)($_POST['effective_to'] ?? ''));
    $effTo = $effTo !== '' ? $effTo : null;

    $type = AppointmentType::findById($typeId);
    if (!$type || (string)$type['provider_type'] !== (string)$prov['provider_type']) {
      flash_set('error','Select an appointment type for this provider.');
      redirect('/admin/providers/' . $providerId . '/rates');
    }
    if (!is_finite($rate) || $rate < 0 || $rate > 1000000) {
      flash_set('error','Enter a valid rate.');
      redirect('/admin/providers/' . $providerId . '/rates');
    }
    if (!self::validDate($effFrom) || ($effTo !== null && (!self::validDate($effTo) || $effTo < $effFrom))) {
      flash_set('error','Enter a valid effective date range.');
      redirect('/admin/providers/' . $providerId . '/rates');
    }

    ProviderRate::addRate($providerId, $typeId, $rate, $effFrom, $effTo);
    flash_set('ok','Rate added (previous open rate auto-closed).');
    redirect('/admin/providers/' . $providerId . '/rates');
  }

  public static function providerRateEnd(array $params): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);
    $providerId = (int)($params['id'] ?? 0);
    $rateId = (int)($_POST['rate_id'] ?? 0);
    $effTo = trim((string)($_POST['effective_to'] ?? ''));
    $effTo = $effTo !== '' ? $effTo : null;
    if (!ProviderRate::belongsToProvider($rateId, $providerId) || ($effTo !== null && !self::validDate($effTo))) {
      flash_set('error','Invalid rate or end date.');
      redirect('/admin/providers/' . $providerId . '/rates');
    }
    ProviderRate::setEffectiveTo($rateId, $effTo);
    flash_set('ok','Rate updated.');
    redirect('/admin/providers/' . $providerId . '/rates');
  }


public static function providerRateUpdate(array $params): void {
  Auth::requireRole('super');
  Csrf::check($_POST['_csrf'] ?? null);

  $providerId = (int)($params['id'] ?? 0);
  $rateId = (int)($_POST['rate_id'] ?? 0);
  $rate = (float)($_POST['rate'] ?? 0);

  if ($providerId <= 0 || $rateId <= 0 || !ProviderRate::belongsToProvider($rateId, $providerId)) {
    flash_set('error','Invalid rate.');
    redirect('/admin/providers/' . $providerId . '/rates');
  }

  if (!is_finite($rate) || $rate < 0 || $rate > 1000000) {
    flash_set('error','Enter a valid rate.');
    redirect('/admin/providers/' . $providerId . '/rates');
  }
  ProviderRate::updateRate($rateId, $rate);

  flash_set('ok','Rate updated.');
  redirect('/admin/providers/' . $providerId . '/rates');
}

public static function providerRateDelete(array $params): void {
  Auth::requireRole('super');
  Csrf::check($_POST['_csrf'] ?? null);

  $providerId = (int)($params['id'] ?? 0);
  $rateId = (int)($_POST['rate_id'] ?? 0);

  if ($providerId <= 0 || $rateId <= 0 || !ProviderRate::belongsToProvider($rateId, $providerId)) {
    flash_set('error','Invalid rate.');
    redirect('/admin/providers/' . $providerId . '/rates');
  }

  ProviderRate::deleteRate($rateId);

  flash_set('ok','Rate deleted.');
  redirect('/admin/providers/' . $providerId . '/rates');
}

  public static function production(): void {
    Auth::requireRole('super');
    $pp = self::getPayPeriodFromRequest();
    $periods = self::recentPeriodsWithSelected($pp, 8);

    $status = ProviderProduction::statusForPayPeriod((int)$pp['id']);

    View::render('admin/provider_production', [
      'title' => 'Provider Production',
      'user' => Auth::user(),
      'payPeriod' => $pp,
      'periods' => $periods,
      'status' => $status,
    ]);
  }

  public static function productionEdit(array $params): void {
    Auth::requireLogin();

    $providerId = (int)($params['id'] ?? 0);
    self::requireProviderAccess($providerId);
    $prov = Provider::findById($providerId);
    if (!$prov) { http_response_code(404); echo "Provider not found."; return; }

    $pp = self::getPayPeriodFromRequest();
    $periods = self::recentPeriodsWithSelected($pp, 8);

    ProviderProduction::ensureRows($providerId, (int)$pp['id']);
    $rows = ProviderProduction::rowsForProviderPeriod($providerId, (int)$pp['id']);

    // Resolve effective rates as-of pay period end date (used for preview if rate_used not set)
    $asOf = (string)$pp['end_date'];
    foreach ($rows as &$r) {
      $eff = ProviderRate::effectiveRate($providerId, (int)$r['appointment_type_id'], $asOf);
      $r['effective_rate'] = $eff !== null ? $eff : 0.00;
    }
    unset($r);


    // Provider PTO for this pay period (admin-entered)
    ProviderPto::ensureRow($providerId, (int)$pp['id']);
    $ptoMinutes = ProviderPto::minutesForProviderPeriod($providerId, (int)$pp['id']);
    $ptoBank = (int)($prov['pto_bank_minutes'] ?? 0);
    $year = (int)substr((string)$pp['start_date'], 0, 4);
    // YTD should be "as of" the selected pay period end date so future entries don't affect remaining.
    $ptoUsedYtd = ProviderPto::sumUsedYtdThroughDate($providerId, $year, (string)$pp['end_date']);
    $ptoAvail = max(0, $ptoBank - $ptoUsedYtd);
    $ptoUsedExclCurrent = max(0, $ptoUsedYtd - $ptoMinutes);
    View::render('admin/provider_production_edit', [
      'title' => 'Edit Provider Production',
      'user' => Auth::user(),
      'prov' => $prov,
      'payPeriod' => $pp,
      'periods' => $periods,
      'rows' => $rows,
      'pto_bank' => $ptoBank,
      'pto_used_ytd' => $ptoUsedYtd,
      'pto_available' => $ptoAvail,
      'pto_minutes' => $ptoMinutes,
      'pto_used_excl_current' => $ptoUsedExclCurrent,
      'isSuper' => (Auth::user() && Auth::user()['role'] === 'super'),
      'backUrl' => '/timecards?pp=' . (string)$pp['id'],
    ]);
  }

  public static function productionSave(): void {
    Auth::requireLogin();
    Csrf::check($_POST['_csrf'] ?? null);

    $providerId = (int)($_POST['provider_id'] ?? 0);
    self::requireProviderAccess($providerId);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    $pp = PayPeriod::findById($ppId);
    if (!$pp) { flash_set('error','Invalid pay period.'); redirect('/timecards'); }

    ProviderProduction::ensureRows($providerId, $ppId);
    $rows = ProviderProduction::rowsForProviderPeriod($providerId, $ppId);
    // If locked, do not save
    $locked = false;
    foreach ($rows as $r) { if (!empty($r['locked_at'])) { $locked = true; break; } }
    if ($locked) {
      flash_set('error','This provider production card is locked.');
      redirect('/provider-production/' . $providerId . '?pp=' . $ppId);
    }

    $counts = $_POST['counts'] ?? [];
    ProviderProduction::saveCounts($providerId, $ppId, $counts);

    if (Auth::user()['role'] === 'super') {
    // Provider PTO (hours) for this pay period (admin-entered)
    $ptoHours = trim((string)($_POST['pto_hours'] ?? ''));
    $ptoMin = 0;
    if ($ptoHours !== '') {
      $h = (float)$ptoHours;
      if ($h < 0) $h = 0;
      $ptoMin = (int)round($h * 60);
    }

    // Clamp to available bank if this provider accrues PTO
    $prov = Provider::findById($providerId);
    $ptoBank = (int)($prov['pto_bank_minutes'] ?? 0);
    if ($ptoBank > 0) {
      $year = (int)substr((string)$pp['start_date'], 0, 4);
      // Compute YTD used through this pay period end date (ignore PTO entered on later pay periods).
      $usedYtd = ProviderPto::sumUsedYtdThroughDate($providerId, $year, (string)$pp['end_date']);
      $current = ProviderPto::minutesForProviderPeriod($providerId, $ppId);
      $usedExcl = max(0, $usedYtd - $current);
      $maxForPeriod = max(0, $ptoBank - $usedExcl);
      if ($ptoMin > $maxForPeriod) $ptoMin = $maxForPeriod;
    }

    ProviderPto::saveMinutes($providerId, $ppId, $ptoMin);
    }

    flash_set('ok','Saved.');
    redirect('/provider-production/' . $providerId . '?pp=' . $ppId);
  }

  public static function productionLock(): void {
    Auth::requireLogin();
    Csrf::check($_POST['_csrf'] ?? null);

    $providerId = (int)($_POST['provider_id'] ?? 0);
    self::requireProviderAccess($providerId);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    $pp = PayPeriod::findById($ppId);
    if (!$pp) { flash_set('error','Invalid pay period.'); redirect('/timecards'); }

    ProviderProduction::ensureRows($providerId, $ppId);
    ProviderProduction::lockProviderPeriod($providerId, $ppId, (int)Auth::user()['id'], (string)$pp['end_date']);

    ProviderPto::lockProviderPeriod($providerId, $ppId, (int)Auth::user()['id']);

    flash_set('ok','Locked.');
    redirect('/provider-production/' . $providerId . '?pp=' . $ppId);
  }

  public static function productionUnlock(): void {
    Auth::requireRole('super');
    Csrf::check($_POST['_csrf'] ?? null);

    $providerId = (int)($_POST['provider_id'] ?? 0);
    $ppId = (int)($_POST['pay_period_id'] ?? 0);
    $_SESSION['selected_pay_period_id'] = $ppId;
    ProviderProduction::unlockProviderPeriod($providerId, $ppId);

    ProviderPto::unlockProviderPeriod($providerId, $ppId);

    flash_set('ok','Unlocked.');
    redirect('/provider-production/' . $providerId . '?pp=' . $ppId);
  }
}
