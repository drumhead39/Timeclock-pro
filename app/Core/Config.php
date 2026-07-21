<?php
declare(strict_types=1);

namespace App\Core;

final class Config {
  private static array $cfg = [];

  public static function load(string $path): void {
    if (!file_exists($path)) {
      throw new \RuntimeException("Missing config.php. Copy app/config.sample.php to app/config.php and edit it.");
    }
    self::$cfg = require $path;
  }

  public static function get(): array { return self::$cfg; }
}
