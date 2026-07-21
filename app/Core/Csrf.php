<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf {
  public static function token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    if (empty($_SESSION['_csrf'])) {
      $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
  }

  public static function rotate(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
  }

  public static function check(?string $token): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $ok = isset($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
    if (!$ok) {
      http_response_code(419);
      echo "<h1>419</h1><p>Invalid CSRF token. Please refresh and try again.</p>";
      exit;
    }
  }
}
