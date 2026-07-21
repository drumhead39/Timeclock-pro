<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Auth;

final class DebugController {
  public static function clientError(): void {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
      http_response_code(404);
      echo "Not Found";
      return;
    }

    Auth::requireLogin();

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    Csrf::check(is_string($token) ? $token : null);

    $raw = file_get_contents('php://input', false, null, 0, 32768) ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = ['raw' => $raw];

    app_log('CLIENT', 'Browser error', [
      'data' => $data,
      'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
      'uri' => $_SERVER['HTTP_REFERER'] ?? ($_SERVER['REQUEST_URI'] ?? ''),
    ]);

    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'rid' => request_id()]);
  }
}
