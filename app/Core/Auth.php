<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth {
  public static function user(): ?array {
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return null;
    return User::findById((int)$id);
  }

  public static function check(): bool {
    $u = self::user();
    return $u !== null && (int)$u['active'] === 1;
  }

  public static function requireLogin(): void {
    if (!self::check()) redirect('/login');
  }

  public static function requireRole(string $role): void {
    self::requireLogin();
    $u = self::user();
    if (!$u || $u['role'] !== $role) {
      http_response_code(403);
      echo "<h1>403</h1><p>Forbidden</p>";
      exit;
    }
  }

  public static function login(string $email, string $password): bool {
    $u = User::findByEmail($email);
    if (!$u || (int)$u['active'] !== 1) return false;
    if (!password_verify($password, $u['password_hash'])) return false;
    $_SESSION['user_id'] = (int)$u['id'];
    return true;
  }

  public static function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]
      );
    }
    session_destroy();
  }
}
