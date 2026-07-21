<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Provider {

  public static function findById(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM providers WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function all(bool $includeInactive = true): array {
    $sql = "SELECT * FROM providers";
    if (!$includeInactive) $sql .= " WHERE active=1";
    $sql .= " ORDER BY active DESC, provider_type ASC, full_name ASC";
    return DB::pdo()->query($sql)->fetchAll();
  }

  public static function activeMatchingFullName(string $fullName): array {
    $name = strtolower(trim($fullName));
    if ($name === '') return [];
    $st = DB::pdo()->prepare("SELECT * FROM providers WHERE active=1 AND LOWER(TRIM(full_name))=? ORDER BY provider_type ASC, full_name ASC");
    $st->execute([$name]);
    return $st->fetchAll();
  }

  public static function create(array $data): int {
    $st = DB::pdo()->prepare("INSERT INTO providers(provider_type,full_name,active,pto_bank_minutes,created_at,updated_at)
      VALUES(?,?,?, ?,NOW(),NOW())");
    $st->execute([
      $data['provider_type'],
      $data['full_name'],
      $data['active'] ?? 1,
      $data['pto_bank_minutes'] ?? 0,
    ]);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void {
    $fields = [];
    $vals = [];
    foreach (['provider_type','full_name','active','pto_bank_minutes'] as $k) {
      if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $vals[] = $data[$k]; }
    }
    if (!$fields) return;
    $vals[] = $id;
    $sql = "UPDATE providers SET " . implode(',', $fields) . ", updated_at=NOW() WHERE id=?";
    DB::pdo()->prepare($sql)->execute($vals);
  }
}
