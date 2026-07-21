<?php
declare(strict_types=1);

namespace App\Services;

final class LoginThrottle {
  private const WINDOW_SECONDS = 900;
  private const MAX_FAILURES = 10;

  private static function directory(): string {
    return APP_STORAGE . '/rate-limits';
  }

  private static function filePath(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return self::directory() . '/' . hash('sha256', $ip) . '.json';
  }

  private static function recentFailures(): array {
    $path = self::filePath();
    if (!is_file($path)) return [];

    $raw = @file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    $timestamps = is_array($data) ? $data : [];
    $cutoff = time() - self::WINDOW_SECONDS;

    return array_values(array_filter($timestamps, static function($value) use ($cutoff): bool {
      return is_int($value) && $value >= $cutoff;
    }));
  }

  private static function store(array $timestamps): void {
    $dir = self::directory();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!is_dir($dir) || !is_writable($dir)) return;

    @file_put_contents(self::filePath(), json_encode(array_values($timestamps)), LOCK_EX);
  }

  public static function tooManyAttempts(): bool {
    $failures = self::recentFailures();
    self::store($failures);
    return count($failures) >= self::MAX_FAILURES;
  }

  public static function recordFailure(): void {
    $failures = self::recentFailures();
    $failures[] = time();
    self::store($failures);
  }

  public static function clear(): void {
    $path = self::filePath();
    if (is_file($path)) @unlink($path);
  }
}
