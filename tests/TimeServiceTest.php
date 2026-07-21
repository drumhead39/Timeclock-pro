<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Services/TimeService.php';

use App\Services\TimeService;

function assert_same($expected, $actual, string $message): void {
  if ($expected !== $actual) {
    fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
    exit(1);
  }
}

function assert_throws(callable $callback, string $message): void {
  try {
    $callback();
  } catch (InvalidArgumentException $e) {
    return;
  }
  fwrite(STDERR, $message . "\n");
  exit(1);
}

$settings = [
  'allowed_minutes' => [0, 15, 30, 45],
  'rounding_mode' => 'nearest',
  'days_to_show' => [1, 2, 3, 4, 5, 6],
];
$period = ['start_date' => '2026-07-20', 'end_date' => '2026-08-02'];

assert_same(510, TimeService::timeToMinutes('08:30'), 'Valid time conversion failed.');
assert_same(null, TimeService::timeToMinutes('24:00'), 'Out-of-range hours must be rejected.');
assert_same(null, TimeService::timeToMinutes('08:99'), 'Out-of-range minutes must be rejected.');
assert_same('08:30:00', TimeService::roundTime('08:29', $settings), 'Nearest-quarter rounding failed.');

$rows = TimeService::normalizeRows([
  '2026-07-20' => ['in' => '08:02', 'out' => '16:31'],
], $period, $settings);
assert_same('08:00:00', $rows['2026-07-20']['in'], 'Time-in normalization failed.');
assert_same('16:30:00', $rows['2026-07-20']['out'], 'Time-out normalization failed.');

assert_throws(static function() use ($period, $settings): void {
  TimeService::normalizeRows(['2026-08-03' => ['in' => '08:00', 'out' => '17:00']], $period, $settings);
}, 'Dates outside the pay period must be rejected.');

assert_throws(static function() use ($period, $settings): void {
  TimeService::normalizeRows(['2026-07-20' => ['in' => 'not-a-time', 'out' => '17:00']], $period, $settings);
}, 'Malformed times must be rejected.');

fwrite(STDOUT, "TimeService regression tests passed.\n");

