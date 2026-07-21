<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class ProviderProduction {

  public static function ensureRows(int $providerId, int $payPeriodId): void {
    $prov = Provider::findById($providerId);
    if (!$prov) return;

    $types = AppointmentType::listByProviderType((string)$prov['provider_type'], false); // active only
    foreach ($types as $t) {
      $sql = "INSERT IGNORE INTO provider_production(provider_id,pay_period_id,appointment_type_id,count,created_at,updated_at)
        VALUES(?,?,?,0,NOW(),NOW())";
      DB::pdo()->prepare($sql)->execute([$providerId, $payPeriodId, (int)$t['id']]);
    }
  }

  public static function rowsForProviderPeriod(int $providerId, int $payPeriodId): array {
    $sql = "SELECT pp.*, at.name as type_name, at.sort_order
      FROM provider_production pp
      JOIN appointment_types at ON at.id=pp.appointment_type_id
      WHERE pp.provider_id=? AND pp.pay_period_id=?
      ORDER BY at.sort_order ASC, at.name ASC";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerId, $payPeriodId]);
    return $st->fetchAll();
  }

  public static function statusForPayPeriod(int $payPeriodId): array {
    $sql = "SELECT p.id as provider_id, p.full_name, p.provider_type, p.active,
      MAX(pp.submitted_at) as submitted_at,
      MAX(pp.locked_at) as locked_at
      FROM providers p
      LEFT JOIN provider_production pp ON pp.provider_id=p.id AND pp.pay_period_id=?
      GROUP BY p.id, p.full_name, p.provider_type, p.active
      ORDER BY p.active DESC, p.provider_type ASC, p.full_name ASC";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$payPeriodId]);
    return $st->fetchAll();
  }

  public static function saveCounts(int $providerId, int $payPeriodId, array $counts): void {
    foreach ($counts as $appointmentTypeId => $count) {
      $c = (int)$count;
      $c = max(0, min(1000000, $c));
      DB::pdo()->prepare("UPDATE provider_production SET count=?, updated_at=NOW()
        WHERE provider_id=? AND pay_period_id=? AND appointment_type_id=?")
        ->execute([$c, $providerId, $payPeriodId, (int)$appointmentTypeId]);
    }
  }

  public static function lockProviderPeriod(int $providerId, int $payPeriodId, int $lockedBy, string $asOfYmd): void {
    $rows = self::rowsForProviderPeriod($providerId, $payPeriodId);
    foreach ($rows as $r) {
      $rateUsed = $r['rate_used'];
      if ($rateUsed === null) {
        $eff = ProviderRate::effectiveRate($providerId, (int)$r['appointment_type_id'], $asOfYmd);
        $rateUsed = $eff !== null ? $eff : 0.00;
        DB::pdo()->prepare("UPDATE provider_production SET rate_used=?, updated_at=NOW() WHERE id=?")
          ->execute([$rateUsed, (int)$r['id']]);
      }
    }
    DB::pdo()->prepare("UPDATE provider_production
      SET submitted_at=COALESCE(submitted_at,NOW()),
          locked_at=COALESCE(locked_at,NOW()),
          locked_by=COALESCE(locked_by,?),
          updated_at=NOW()
      WHERE provider_id=? AND pay_period_id=?")
      ->execute([$lockedBy, $providerId, $payPeriodId]);
  }

  public static function unlockProviderPeriod(int $providerId, int $payPeriodId): void {
    DB::pdo()->prepare("UPDATE provider_production SET locked_at=NULL, locked_by=NULL, updated_at=NOW()
      WHERE provider_id=? AND pay_period_id=?")
      ->execute([$providerId, $payPeriodId]);
  }

  public static function lockAllForPayPeriod(int $payPeriodId, int $lockedBy, string $asOfYmd): void {
    $providers = DB::pdo()->query("SELECT id FROM providers WHERE active=1")->fetchAll();
    foreach ($providers as $p) {
      $pid = (int)$p['id'];
      self::ensureRows($pid, $payPeriodId);
      self::lockProviderPeriod($pid, $payPeriodId, $lockedBy, $asOfYmd);
    }
  }
}
