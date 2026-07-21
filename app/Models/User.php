<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class User {
  public static function findById(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function findByEmail(string $email): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $st->execute([strtolower(trim($email))]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function all(): array {
    return DB::pdo()->query("SELECT * FROM users ORDER BY role DESC, full_name ASC")->fetchAll();
  }

  public static function create(array $data): int {
    $st = DB::pdo()->prepare("INSERT INTO users(full_name,email,password_hash,role,active,weight_loss_consultant,nursing_encounters_enabled,pto_bank_minutes,created_at,updated_at)
      VALUES(?,?,?,?,?,?,?,?,NOW(),NOW())");
    $st->execute([
      $data['full_name'],
      strtolower(trim($data['email'])),
      $data['password_hash'],
      $data['role'] ?? 'employee',
      $data['active'] ?? 1,
      $data['weight_loss_consultant'] ?? 0,
      $data['nursing_encounters_enabled'] ?? 0,
      $data['pto_bank_minutes'] ?? 0,
    ]);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void {
    $fields = [];
    $vals = [];
    foreach (['full_name','email','role','active','weight_loss_consultant','nursing_encounters_enabled','pto_bank_minutes'] as $k) {
      if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $vals[] = ($k==='email'? strtolower(trim((string)$data[$k])) : $data[$k]); }
    }
    if (!$fields) return;
    $vals[] = $id;
    $sql = "UPDATE users SET " . implode(',', $fields) . ", updated_at=NOW() WHERE id=?";
    DB::pdo()->prepare($sql)->execute($vals);
  }

  public static function setPassword(int $id, string $password_hash): void {
    DB::pdo()->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$password_hash, $id]);
  }

  public static function deleteHard(int $id): void {
    DB::pdo()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
  }
}
