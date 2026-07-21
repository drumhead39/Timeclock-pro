<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;
    $cfg = Config::get()['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset'] ?? 'utf8mb4');
    self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return self::$pdo;
  }

  public static function tx(callable $fn) {
    $pdo = self::pdo();
    try {
      $pdo->beginTransaction();
      $res = $fn($pdo);
      $pdo->commit();
      return $res;
    } catch (\Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $e;
    }
  }
}
