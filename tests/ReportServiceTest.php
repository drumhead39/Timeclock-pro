<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Services/TimeService.php';
require dirname(__DIR__) . '/app/Services/ReportService.php';

use App\Services\ReportService;

function assert_report_same($expected, $actual, string $message): void {
  if ($expected !== $actual) {
    fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
    exit(1);
  }
}

$start = '2026-11-09';
$end = '2026-11-22';

$empty = ReportService::timeRowsForPeriod([], $start, $end);
assert_report_same(['No hours'], $empty['lines'], 'Empty timecard should use a single No hours line.');
assert_report_same(0, $empty['minutes'], 'Empty timecard should total zero worked minutes.');

$blankSavedRow = ReportService::timeRowsForPeriod([
  '2026-11-09' => ['time_in' => null, 'time_out' => null],
], $start, $end);
assert_report_same(['No hours'], $blankSavedRow['lines'], 'Blank saved row should also be compact.');

$worked = ReportService::timeRowsForPeriod([
  '2026-11-09' => ['time_in' => '08:00:00', 'time_out' => '16:30:00'],
], $start, $end);
assert_report_same(510, $worked['minutes'], 'Worked minutes should remain accurate.');
assert_report_same(16, count($worked['lines']), 'Worked timecard should retain its daily rows and headings.');
if (!str_contains($worked['lines'][2], '8:00 AM') || !str_contains($worked['lines'][2], '8.50')) {
  fwrite(STDERR, "Worked timecard has incorrect first-day formatting.\n");
  exit(1);
}

$partial = ReportService::timeRowsForPeriod([
  '2026-11-09' => ['time_in' => '08:00:00', 'time_out' => null],
], $start, $end);
assert_report_same(0, $partial['minutes'], 'Incomplete punch should not count as worked hours.');
assert_report_same(16, count($partial['lines']), 'Incomplete punch should remain visible for correction.');

fwrite(STDOUT, "ReportService regression tests passed.\n");
