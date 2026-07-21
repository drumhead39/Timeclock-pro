<?php
declare(strict_types=1);

use App\Core\Config;

require __DIR__ . '/helpers.php';

/**
 * Early boot: define paths + request id + debug flag
 */
$root = dirname(__DIR__);
if (!defined('APP_ROOT')) define('APP_ROOT', $root);
if (!defined('APP_STORAGE')) define('APP_STORAGE', APP_ROOT . '/storage');
if (!defined('APP_LOG_DIR')) define('APP_LOG_DIR', APP_STORAGE . '/logs');
if (!defined('APP_DEBUG_FLAG')) define('APP_DEBUG_FLAG', APP_STORAGE . '/DEBUG_ON');
if (!defined('APP_REQUEST_ID')) define('APP_REQUEST_ID', bin2hex(random_bytes(8)));

@header('X-Request-Id: ' . APP_REQUEST_ID);

$GLOBALS['APP_DEBUG_RUNTIME'] = file_exists(APP_DEBUG_FLAG);

/**
 * Ensure log dir exists + point PHP error_log at a file we control (when possible)
 */
if (!is_dir(APP_LOG_DIR)) {
  @mkdir(APP_LOG_DIR, 0755, true);
}

$phpLog = APP_LOG_DIR . '/php-error.log';
if (is_dir(APP_LOG_DIR) && is_writable(APP_LOG_DIR)) {
  @ini_set('log_errors', '1');
  @ini_set('error_log', $phpLog);
}

error_reporting(E_ALL);
@ini_set('display_errors', $GLOBALS['APP_DEBUG_RUNTIME'] ? '1' : '0');
@ini_set('display_startup_errors', $GLOBALS['APP_DEBUG_RUNTIME'] ? '1' : '0');

$handling = false;

$renderErrorPage = function(string $publicMessage, ?Throwable $e = null) use ($phpLog): void {
  if (!headers_sent()) http_response_code(500);

  $rid = APP_REQUEST_ID;
  $debug = !empty($GLOBALS['APP_DEBUG_RUNTIME']);
  $logWritable = (is_dir(APP_LOG_DIR) && is_writable(APP_LOG_DIR));

  $details = '';
  if ($debug && $e) {
    $details = "<h2 style='margin:16px 0 8px'>Details</h2>"
      . "<pre style='white-space:pre-wrap;overflow:auto;background:#0b1020;color:#e6edf3;padding:12px;border-radius:12px;'>"
      . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8')
      . "</pre>";
  }

  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>500</title></head><body style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;padding:20px;max-width:920px;margin:0 auto;'>";
  echo "<h1 style='margin:0 0 8px'>Server error</h1>";
  echo "<p style='margin:0 0 8px'>" . htmlspecialchars($publicMessage, ENT_QUOTES, 'UTF-8') . "</p>";
  echo "<p style='margin:0 0 8px'><strong>Request ID:</strong> " . htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') . "</p>";
  echo "<p style='margin:0 0 16px'><strong>Log file:</strong> " . htmlspecialchars($phpLog, ENT_QUOTES, 'UTF-8')
     . ($logWritable ? "" : " <em>(not writable — fix permissions on storage/logs)</em>") . "</p>";
  echo $details;
  echo "</body></html>";
};

set_exception_handler(function(Throwable $e) use (&$handling, $renderErrorPage) {
  if ($handling) return;
  $handling = true;

  // IMPORTANT: log as string (PHP 8.2-safe)
  error_log("[" . APP_REQUEST_ID . "] " . (string)$e);

  $renderErrorPage('Unhandled exception', $e);
});

set_error_handler(function(int $severity, string $message, string $file, int $line) {
  if (!(error_reporting() & $severity)) return false;

  error_log("[" . APP_REQUEST_ID . "] PHP {$severity}: {$message} in {$file}:{$line}");

  // In debug, convert warnings/notices into exceptions to surface stack traces
  if (!empty($GLOBALS['APP_DEBUG_RUNTIME'])) {
    throw new ErrorException($message, 0, $severity, $file, $line);
  }

  // In production, swallow (already logged)
  return true;
});

register_shutdown_function(function() use ($renderErrorPage) {
  $err = error_get_last();
  if (!$err) return;

  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
  if (!in_array($err['type'], $fatalTypes, true)) return;

  error_log("[" . APP_REQUEST_ID . "] FATAL {$err['type']}: {$err['message']} in {$err['file']}:{$err['line']}");
  $renderErrorPage('Fatal error (see log for details)');
});

/**
 * Autoloader
 */
spl_autoload_register(function($class){
  $prefix = 'App\\';
  $baseDir = __DIR__ . '/';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
  if (file_exists($file)) require $file;
});

/**
 * Load config AFTER handlers are active (so missing/bad config logs properly)
 */
Config::load(__DIR__ . '/config.php');

$cfg = Config::get();
$GLOBALS['APP_DEBUG_RUNTIME'] = !empty($GLOBALS['APP_DEBUG_RUNTIME']) || (bool)($cfg['app']['debug'] ?? false);
if (!defined('APP_DEBUG')) define('APP_DEBUG', (bool)$GLOBALS['APP_DEBUG_RUNTIME']);

date_default_timezone_set($cfg['app']['timezone'] ?? 'America/New_York');

/**
 * Session hardening (shared-hosting safe)
 */
// --- Session setup (critical for CSRF on subfolder installs) ---
$cfg = Config::get();
$baseUrl = (string)($cfg['app']['base_url'] ?? '');
$cookiePath = '/';

if ($baseUrl !== '') {
  $u = parse_url($baseUrl);
  if (!empty($u['path'])) {
    $cookiePath = rtrim($u['path'], '/') . '/';
  }
}

// Store sessions inside the app (more reliable on shared hosting)
$sessionDir = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionDir)) { @mkdir($sessionDir, 0755, true); }
if (is_dir($sessionDir) && is_writable($sessionDir)) {
  ini_set('session.save_path', $sessionDir);
}

// Avoid collisions with other PHP apps on the same domain
session_name('TIMECLKSESSID');

ini_set('session.cookie_path', $cookiePath);
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
  ini_set('session.cookie_secure', '1');
}

session_start();

