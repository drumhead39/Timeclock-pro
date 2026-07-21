<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class TimeEntry {
  public static function byUserPeriod(int $userId, int $payPeriodId): array {
    $st = DB::pdo()->prepare("SELECT * FROM time_entries WHERE user_id=? AND pay_period_id=?");
    $st->execute([$userId, $payPeriodId]);
    $rows = $st->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['work_date']] = $r;
    return $map;
  }

  public static function upsert(int $userId, int $payPeriodId, string $workDate, ?string $timeIn, ?string $timeOut): void {
    $sql = "INSERT INTO time_entries(user_id,pay_period_id,work_date,time_in,time_out,created_at,updated_at)
      VALUES(?,?,?,?,?,NOW(),NOW())
      ON DUPLICATE KEY UPDATE time_in=VALUES(time_in), time_out=VALUES(time_out), updated_at=NOW()";
    DB::pdo()->prepare($sql)->execute([$userId, $payPeriodId, $workDate, $timeIn, $timeOut]);
  }

  public static function deleteForDate(int $userId, int $payPeriodId, string $workDate): void {
    DB::pdo()->prepare("DELETE FROM time_entries WHERE user_id=? AND pay_period_id=? AND work_date=?")->execute([$userId,$payPeriodId,$workDate]);
  }
}
