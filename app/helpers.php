<?php
declare(strict_types=1);

/**
 * HTML escape helper.
 */
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/**
 * Returns the base path of the current script (subfolder) like "/timeclock/public",
 * or "" when installed at domain root.
 */
function base_path(): string {
  $sn = $_SERVER['SCRIPT_NAME'] ?? '';
  $dir = str_replace('\\', '/', dirname($sn));
  if ($dir === '/' || $dir === '.' ) return '';
  return rtrim($dir, '/');
}

/**
 * Builds a URL to an internal route/asset.
 *
 * Priority:
 *  1) app.base_url (if set) e.g. https://example.com/timeclock
 *  2) inferred base path from SCRIPT_NAME
 */
function url(string $path): string {
  $path = '/' . ltrim($path, '/');
  $cfg = \App\Core\Config::get();
  $baseUrl = trim((string)($cfg['app']['base_url'] ?? ''));

  if ($baseUrl !== '') {
    return rtrim($baseUrl, '/') . $path;
  }
  return base_path() . $path;
}

/**
 * Redirect to an internal route (or full URL).
 */
function redirect(string $path): void {
  // If it's already an absolute URL, leave it.
  if (preg_match('~^https?://~i', $path)) {
    header('Location: ' . $path);
    exit;
  }
  header('Location: ' . url($path));
  exit;
}

/**
 * Debug toggle: enabled if app.debug=true OR storage/DEBUG_ON file exists.
 */
function debug_enabled(): bool {
  if (defined('APP_DEBUG')) return (bool)APP_DEBUG;
  // Fallback (shouldn't happen, but safe)
  $cfg = \App\Core\Config::get();
  $cfgDebug = (bool)($cfg['app']['debug'] ?? false);
  $root = dirname(__DIR__);
  $flag = $root . '/storage/DEBUG_ON';
  return $cfgDebug || file_exists($flag);
}

/**
 * Request ID for correlating browser errors to logs.
 */
function request_id(): string {
  return defined('APP_REQUEST_ID') ? (string)APP_REQUEST_ID : '';
}

/**
 * Log directory helper.
 */
function app_log_dir(): string {
  if (defined('APP_LOG_DIR')) return (string)APP_LOG_DIR;
  $root = dirname(__DIR__);
  return $root . '/storage/logs';
}

/**
 * Append a JSON line to the daily app log.
 * Log file: storage/logs/app-YYYY-MM-DD.log
 */
function app_log(string $level, string $message, array $context = []): void {
  $dir = app_log_dir();
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  $file = rtrim($dir, '/') . '/app-' . date('Y-m-d') . '.log';

  $entry = [
    'ts' => date('c'),
    'rid' => request_id(),
    'level' => strtoupper($level),
    'msg' => $message,
    'ctx' => $context,
  ];

  $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
  @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function now(): string { return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'); }

function minutes_to_hhmm(int $minutes): string {
  $sign = $minutes < 0 ? '-' : '';
  $minutes = abs($minutes);
  $h = intdiv($minutes, 60);
  $m = $minutes % 60;
  return sprintf('%s%02d:%02d', $sign, $h, $m);
}

function minutes_to_hours_decimal(int $minutes, int $precision = 2): string {
  $sign = $minutes < 0 ? '-' : '';
  $minutes = abs($minutes);
  $hours = $minutes / 60;
  return $sign . number_format($hours, $precision, '.', '');
}


/**
 * Format a YYYY-MM-DD date string as MM/DD/YYYY for display.
 * If parsing fails, returns the original string.
 */
function fmt_date(?string $date): string {
  $date = trim((string)$date);
  if ($date === '') return '';
  $date = substr($date, 0, 10);

  $cfg = \App\Core\Config::get();
  $tzName = (string)($cfg['app']['timezone'] ?? 'UTC');

  try {
    $tz = new \DateTimeZone($tzName);
  } catch (\Throwable $e) {
    $tz = new \DateTimeZone('UTC');
  }

  try {
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date, $tz);
    if ($dt instanceof \DateTimeImmutable) {
      return $dt->format('m/d/Y');
    }
    $dt2 = new \DateTimeImmutable($date, $tz);
    return $dt2->format('m/d/Y');
  } catch (\Throwable $e) {
    return $date;
  }
}

/**
 * Format a datetime (YYYY-MM-DD HH:MM:SS) as MM/DD/YYYY h:mm AM/PM for display.
 * If parsing fails, returns the original string.
 */
function fmt_datetime(?string $dt, bool $includeTime = true): string {
  $dt = trim((string)$dt);
  if ($dt === '') return '';

  $cfg = \App\Core\Config::get();
  $tzName = (string)($cfg['app']['timezone'] ?? 'UTC');

  try {
    $tz = new \DateTimeZone($tzName);
  } catch (\Throwable $e) {
    $tz = new \DateTimeZone('UTC');
  }

  try {
    $dti = new \DateTimeImmutable($dt, $tz);
    return $dti->format($includeTime ? 'm/d/Y g:i A' : 'm/d/Y');
  } catch (\Throwable $e) {
    return $dt;
  }
}

function hhmm_to_minutes(string $hhmm): int {
  if (!preg_match('/^(\d{1,3}):(\d{2})$/', $hhmm, $m)) return 0;
  return ((int)$m[1]) * 60 + (int)$m[2];
}

function flash_set(string $key, string $msg): void {
  $_SESSION['_flash'][$key] = $msg;
}
function flash_get(string $key): ?string {
  $msg = $_SESSION['_flash'][$key] ?? null;
  if ($msg !== null) unset($_SESSION['_flash'][$key]);
  return $msg;
}

/**
 * Compute and ensure the pay period that contains today's date.
 */
function current_pay_period(): array {
  $state = \App\Models\Settings::appState();
  $anchor = new \DateTimeImmutable($state['pay_period_anchor_date']);
  $len = (int)$state['pay_period_length_days'];

  $today = new \DateTimeImmutable('today');
  $diffDays = (int)$anchor->diff($today)->format('%r%a');
  $idx = (int)floor($diffDays / $len);
  if ($diffDays < 0) $idx = (int)ceil($diffDays / $len) - 1;

  $start = $anchor->modify('+' . ($idx * $len) . ' days');
  $end = $start->modify('+' . ($len - 1) . ' days');

  $sv = (int)$state['current_settings_version_id'];
  return \App\Models\PayPeriod::ensure($start->format('Y-m-d'), $end->format('Y-m-d'), $sv);
}

/**
 * Resolve the selected working pay period.
 * ?pp=ID updates the session selection. No ?pp=ID uses the current session selection.
 * A fresh login/no session selection defaults to the current pay period.
 */
function selected_pay_period(?int $requestedId = null): array {
  $requestedId = $requestedId ?? (int)($_GET['pp'] ?? 0);

  if ($requestedId > 0) {
    $pp = \App\Models\PayPeriod::findById($requestedId);
    if ($pp) {
      $_SESSION['selected_pay_period_id'] = (int)$pp['id'];
      return $pp;
    }
  }

  $savedId = (int)($_SESSION['selected_pay_period_id'] ?? 0);
  if ($savedId > 0) {
    $pp = \App\Models\PayPeriod::findById($savedId);
    if ($pp) return $pp;
  }

  $pp = current_pay_period();
  $_SESSION['selected_pay_period_id'] = (int)$pp['id'];
  return $pp;
}

function recent_pay_periods_with_selected(array $selected, int $limit = 8): array {
  $periods = \App\Models\PayPeriod::listRecent($limit);
  $has = false;
  foreach ($periods as $p) {
    if ((int)$p['id'] === (int)$selected['id']) { $has = true; break; }
  }
  if (!$has) array_unshift($periods, $selected);
  return $periods;
}
