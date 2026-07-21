<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class ProviderRate {

  public static function effectiveRate(int $providerId, int $appointmentTypeId, string $asOfYmd): ?float {
    $sql = "SELECT rate FROM provider_rates
      WHERE provider_id=? AND appointment_type_id=?
        AND effective_from <= ?
        AND (effective_to IS NULL OR effective_to >= ?)
      ORDER BY effective_from DESC
      LIMIT 1";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerId, $appointmentTypeId, $asOfYmd, $asOfYmd]);
    $row = $st->fetch();
    if (!$row) return null;
    return (float)$row['rate'];
  }

  public static function listForProvider(int $providerId): array {
    $sql = "SELECT pr.*, at.name as type_name, at.provider_type
      FROM provider_rates pr
      JOIN appointment_types at ON at.id=pr.appointment_type_id
      WHERE pr.provider_id=?
      ORDER BY at.sort_order ASC, at.name ASC, pr.effective_from DESC";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerId]);
    return $st->fetchAll();
  }

  public static function addRate(int $providerId, int $appointmentTypeId, float $rate, string $effectiveFrom, ?string $effectiveTo): void {
    // Auto-close any open-ended rate that overlaps the new effectiveFrom (same provider+type)
    DB::tx(function($pdo) use ($providerId,$appointmentTypeId,$rate,$effectiveFrom,$effectiveTo) {
      $dayBefore = (new \DateTimeImmutable($effectiveFrom))->modify('-1 day')->format('Y-m-d');

      // Close open-ended rows that start before the new effective date
      $pdo->prepare("UPDATE provider_rates
        SET effective_to=?, updated_at=NOW()
        WHERE provider_id=? AND appointment_type_id=?
          AND effective_to IS NULL
          AND effective_from < ?")
        ->execute([$dayBefore, $providerId, $appointmentTypeId, $effectiveFrom]);

      // Insert new row
      $pdo->prepare("INSERT INTO provider_rates(provider_id,appointment_type_id,rate,effective_from,effective_to,created_at,updated_at)
        VALUES(?,?,?,?,?,NOW(),NOW())")
        ->execute([$providerId, $appointmentTypeId, $rate, $effectiveFrom, $effectiveTo]);
    });
  }

  public static function setEffectiveTo(int $id, ?string $effectiveTo): void {
    DB::pdo()->prepare("UPDATE provider_rates SET effective_to=?, updated_at=NOW() WHERE id=?")->execute([$effectiveTo, $id]);
  }


public static function updateRate(int $id, float $rate): void {
  DB::pdo()->prepare("UPDATE provider_rates SET rate=?, updated_at=NOW() WHERE id=?")->execute([$rate, $id]);
}

public static function deleteRate(int $id): void {
  DB::pdo()->prepare("DELETE FROM provider_rates WHERE id=?")->execute([$id]);
}
}
