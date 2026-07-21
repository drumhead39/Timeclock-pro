<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class ProviderPto {

  public static function ensureRow(int $providerId, int $payPeriodId): void {
    $sql = "INSERT IGNORE INTO provider_pto(provider_id,pay_period_id,pto_minutes,created_at,updated_at)
            VALUES(?,?,0,NOW(),NOW())";
    DB::pdo()->prepare($sql)->execute([$providerId, $payPeriodId]);
  }

  public static function findForProviderPeriod(int $providerId, int $payPeriodId): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM provider_pto WHERE provider_id=? AND pay_period_id=? LIMIT 1");
    $st->execute([$providerId, $payPeriodId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function minutesForProviderPeriod(int $providerId, int $payPeriodId): int {
    $st = DB::pdo()->prepare("SELECT COALESCE(pto_minutes,0) as m FROM provider_pto WHERE provider_id=? AND pay_period_id=? LIMIT 1");
    $st->execute([$providerId, $payPeriodId]);
    $row = $st->fetch();
    return (int)($row['m'] ?? 0);
  }

  public static function saveMinutes(int $providerId, int $payPeriodId, int $minutes): void {
    if ($minutes < 0) $minutes = 0;
    self::ensureRow($providerId, $payPeriodId);
    DB::pdo()->prepare("UPDATE provider_pto SET pto_minutes=?, updated_at=NOW() WHERE provider_id=? AND pay_period_id=?")
      ->execute([$minutes, $providerId, $payPeriodId]);
  }

  public static function sumUsedYtd(int $providerId, int $year): int {
    $sql = "SELECT COALESCE(SUM(ppto.pto_minutes),0) as m
      FROM provider_pto ppto
      JOIN pay_periods pp ON pp.id=ppto.pay_period_id
      WHERE ppto.provider_id=? AND YEAR(pp.start_date)=?";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerId, $year]);
    $row = $st->fetch();
    return (int)($row['m'] ?? 0);
  }

  /**
   * Sum PTO used for a provider for a given year, but only through a specific pay period end date.
   *
   * This prevents "future" PTO (entered on open timecards for later pay periods) from affecting
   * historical reports and bank/remaining calculations.
   */
  public static function sumUsedYtdThroughDate(int $providerId, int $year, string $throughEndDate): int {
    $sql = "SELECT COALESCE(SUM(ppto.pto_minutes),0) as m
      FROM provider_pto ppto
      JOIN pay_periods pp ON pp.id=ppto.pay_period_id
      WHERE ppto.provider_id=?
        AND YEAR(pp.start_date)=?
        AND pp.end_date <= ?";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerId, $year, $throughEndDate]);
    $row = $st->fetch();
    return (int)($row['m'] ?? 0);
  }

  public static function lockProviderPeriod(int $providerId, int $payPeriodId, int $lockedBy): void {
    self::ensureRow($providerId, $payPeriodId);
    DB::pdo()->prepare("UPDATE provider_pto
      SET submitted_at=COALESCE(submitted_at,NOW()),
          locked_at=COALESCE(locked_at,NOW()),
          locked_by=COALESCE(locked_by,?),
          updated_at=NOW()
      WHERE provider_id=? AND pay_period_id=?")
      ->execute([$lockedBy, $providerId, $payPeriodId]);
  }

  public static function unlockProviderPeriod(int $providerId, int $payPeriodId): void {
    DB::pdo()->prepare("UPDATE provider_pto SET locked_at=NULL, locked_by=NULL, updated_at=NOW()
      WHERE provider_id=? AND pay_period_id=?")
      ->execute([$providerId, $payPeriodId]);
  }

  public static function lockAllForPayPeriod(int $payPeriodId, int $lockedBy): void {
    $providers = DB::pdo()->query("SELECT id FROM providers WHERE active=1")->fetchAll();
    foreach ($providers as $p) {
      $pid = (int)$p['id'];
      self::ensureRow($pid, $payPeriodId);
      self::lockProviderPeriod($pid, $payPeriodId, $lockedBy);
    }
  }
}
