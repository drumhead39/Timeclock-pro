<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Settings {
  public static function appState(): array {
    $row = DB::pdo()->query("SELECT * FROM app_state WHERE id=1")->fetch();
    if (!$row) throw new \RuntimeException("Missing app_state row. Run installer.");
    return $row;
  }

  public static function currentSettingsVersion(): array {
    $state = self::appState();
    $id = (int)$state['current_settings_version_id'];
    return self::settingsVersion($id);
  }

  public static function settingsVersion(int $id): array {
    $st = DB::pdo()->prepare("SELECT * FROM settings_versions WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) throw new \RuntimeException("Settings version not found: $id");
    $row['config'] = json_decode($row['config_json'], true) ?: [];
    return $row;
  }

  public static function createSettingsVersion(int $createdBy, array $config): int {
    $st = DB::pdo()->prepare("INSERT INTO settings_versions(created_by,created_at,config_json) VALUES(?,NOW(),?)");
    $st->execute([$createdBy, json_encode($config, JSON_UNESCAPED_SLASHES)]);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function setCurrentSettingsVersion(int $id): void {
    DB::pdo()->prepare("UPDATE app_state SET current_settings_version_id=? WHERE id=1")->execute([$id]);
  }

  public static function updateAppState(array $data): void {
    $fields = [];
    $vals = [];
    foreach (['company_name','company_email','pay_period_anchor_date','pay_period_length_days'] as $k) {
      if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $vals[] = $data[$k]; }
    }
    if (!$fields) return;
    $sql = "UPDATE app_state SET " . implode(',', $fields) . " WHERE id=1";
    DB::pdo()->prepare($sql)->execute($vals);
  }
}
