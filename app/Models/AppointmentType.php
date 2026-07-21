<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class AppointmentType {

  public static function findById(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM appointment_types WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function listByProviderType(string $providerType, bool $includeInactive = true): array {
    $sql = "SELECT * FROM appointment_types WHERE provider_type=?";
    if (!$includeInactive) $sql .= " AND active=1";
    $sql .= " ORDER BY sort_order ASC, name ASC";
    $st = DB::pdo()->prepare($sql);
    $st->execute([$providerType]);
    return $st->fetchAll();
  }

  public static function all(bool $includeInactive = true): array {
    $sql = "SELECT * FROM appointment_types";
    if (!$includeInactive) $sql .= " WHERE active=1";
    $sql .= " ORDER BY provider_type ASC, sort_order ASC, name ASC";
    return DB::pdo()->query($sql)->fetchAll();
  }

  public static function create(array $data): int {
    $st = DB::pdo()->prepare("INSERT INTO appointment_types(provider_type,name,active,sort_order,created_at,updated_at)
      VALUES(?,?,?,?,NOW(),NOW())");
    $st->execute([
      $data['provider_type'],
      $data['name'],
      $data['active'] ?? 1,
      $data['sort_order'] ?? 0,
    ]);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void {
    $fields = [];
    $vals = [];
    foreach (['provider_type','name','active','sort_order'] as $k) {
      if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $vals[] = $data[$k]; }
    }
    if (!$fields) return;
    $vals[] = $id;
    $sql = "UPDATE appointment_types SET " . implode(',', $fields) . ", updated_at=NOW() WHERE id=?";
    DB::pdo()->prepare($sql)->execute($vals);
  }

  public static function setActive(int $id, int $active): void {
    DB::pdo()->prepare("UPDATE appointment_types SET active=?, updated_at=NOW() WHERE id=?")->execute([$active, $id]);
  }


public static function canDelete(int $id): bool {
  $pdo = DB::pdo();
  $st = $pdo->prepare("SELECT COUNT(*) c FROM provider_rates WHERE appointment_type_id=?");
  $st->execute([$id]);
  $c1 = (int)($st->fetch()['c'] ?? 0);

  $st = $pdo->prepare("SELECT COUNT(*) c FROM provider_production WHERE appointment_type_id=?");
  $st->execute([$id]);
  $c2 = (int)($st->fetch()['c'] ?? 0);

  return ($c1 + $c2) === 0;
}

public static function delete(int $id): void {
  DB::pdo()->prepare("DELETE FROM appointment_types WHERE id=?")->execute([$id]);
}
}
