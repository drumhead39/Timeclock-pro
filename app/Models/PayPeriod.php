<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class PayPeriod {

  public static function findByDates(string $start, string $end): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM pay_periods WHERE start_date=? AND end_date=? LIMIT 1");
    $st->execute([$start, $end]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function findById(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM pay_periods WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function ensure(string $start, string $end, int $settingsVersionId): array {
    $existing = self::findByDates($start, $end);
    if ($existing) return $existing;
    $st = DB::pdo()->prepare("INSERT INTO pay_periods(start_date,end_date,settings_version_id,created_at) VALUES(?,?,?,NOW())");
    $st->execute([$start, $end, $settingsVersionId]);
    return self::findById((int)DB::pdo()->lastInsertId());
  }

  public static function listRecent(int $limit = 10): array {
    $st = DB::pdo()->prepare("SELECT * FROM pay_periods ORDER BY start_date DESC LIMIT ?");
    $st->bindValue(1, $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function setSettingsVersion(int $payPeriodId, int $settingsVersionId): void {
    DB::pdo()->prepare("UPDATE pay_periods SET settings_version_id=? WHERE id=? AND locked_at IS NULL")->execute([$settingsVersionId, $payPeriodId]);
  }

  public static function lock(int $payPeriodId, int $lockedBy): void {
    DB::pdo()->prepare("UPDATE pay_periods SET locked_at=NOW(), locked_by=? WHERE id=? AND locked_at IS NULL")->execute([$lockedBy, $payPeriodId]);
  }
}
