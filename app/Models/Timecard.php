<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Timecard {
  public static function ensure(int $userId, int $payPeriodId): array {
    $st = DB::pdo()->prepare("SELECT * FROM timecards WHERE user_id=? AND pay_period_id=? LIMIT 1");
    $st->execute([$userId, $payPeriodId]);
    $row = $st->fetch();
    if ($row) return $row;
    DB::pdo()->prepare("INSERT INTO timecards(user_id,pay_period_id,pto_minutes,weight_loss_units,nursing_encounters,created_at,updated_at) VALUES(?,?,0,0,0,NOW(),NOW())")
      ->execute([$userId, $payPeriodId]);
    $st->execute([$userId, $payPeriodId]);
    return $st->fetch();
  }

  public static function update(int $userId, int $payPeriodId, array $data): void {
    $fields = [];
    $vals = [];
    foreach (['pto_minutes','weight_loss_units','nursing_encounters','submitted_at','locked_at','locked_by'] as $k) {
      if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $vals[] = $data[$k]; }
    }
    if (!$fields) return;
    $vals[] = $userId; $vals[] = $payPeriodId;
    $sql = "UPDATE timecards SET " . implode(',', $fields) . ", updated_at=NOW() WHERE user_id=? AND pay_period_id=?";
    DB::pdo()->prepare($sql)->execute($vals);
  }

  public static function setSubmitted(int $userId, int $payPeriodId): void {
    DB::pdo()->prepare("UPDATE timecards SET submitted_at=NOW(), updated_at=NOW() WHERE user_id=? AND pay_period_id=? AND locked_at IS NULL")
      ->execute([$userId, $payPeriodId]);
  }

  /**
   * Admin helper: ensure submitted_at is set even if the card is already locked.
   * We only fill it when missing so we don't overwrite an employee's original submit time.
   */
  public static function markSubmittedIfNull(int $userId, int $payPeriodId): void {
    DB::pdo()->prepare(
      "UPDATE timecards SET submitted_at = COALESCE(submitted_at, NOW()), updated_at=NOW() WHERE user_id=? AND pay_period_id=?"
    )->execute([$userId, $payPeriodId]);
  }

  public static function setLocked(int $userId, int $payPeriodId, int $lockedBy): void {
    DB::pdo()->prepare("UPDATE timecards SET locked_at=NOW(), locked_by=?, updated_at=NOW() WHERE user_id=? AND pay_period_id=? AND locked_at IS NULL")
      ->execute([$lockedBy, $userId, $payPeriodId]);
  }

  public static function setUnlocked(int $userId, int $payPeriodId): void {
    DB::pdo()->prepare("UPDATE timecards SET locked_at=NULL, locked_by=NULL, updated_at=NOW() WHERE user_id=? AND pay_period_id=?")
      ->execute([$userId, $payPeriodId]);
  }

  public static function statusForPayPeriod(int $payPeriodId): array {
    $sql = "SELECT u.id as user_id, u.full_name, u.email, u.role, u.active,
      tc.submitted_at, tc.locked_at
      FROM users u
      LEFT JOIN timecards tc ON tc.user_id=u.id AND tc.pay_period_id=?
      ORDER BY u.role DESC, u.full_name ASC";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$payPeriodId]);
    return $st->fetchAll();
  }

  public static function sumPtoUsedYtd(int $userId, int $year): int {
    $sql = "SELECT COALESCE(SUM(tc.pto_minutes),0) as m
      FROM timecards tc
      JOIN pay_periods pp ON pp.id=tc.pay_period_id
      WHERE tc.user_id=? AND YEAR(pp.start_date)=?";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$userId, $year]);
    return (int)($st->fetch()['m'] ?? 0);
  }

  public static function sumPtoUsedYtdThroughDate(int $userId, int $year, string $throughEndDate): int {
    $sql = "SELECT COALESCE(SUM(tc.pto_minutes),0) as m
      FROM timecards tc
      JOIN pay_periods pp ON pp.id=tc.pay_period_id
      WHERE tc.user_id=?
        AND YEAR(pp.start_date)=?
        AND pp.end_date <= ?";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$userId, $year, $throughEndDate]);
    return (int)($st->fetch()['m'] ?? 0);
  }
}
