<?php
declare(strict_types=1);

/**
 * Optional cron to ensure the next pay period exists ahead of time.
 * Usage (daily): php /path/to/timeclock-pro/cron/ensure_pay_period.php
 */
require __DIR__ . '/../app/bootstrap.php';

use App\Models\Settings;
use App\Models\PayPeriod;

$state = Settings::appState();
$anchor = new DateTimeImmutable($state['pay_period_anchor_date']);
$len = (int)$state['pay_period_length_days'];

$today = new DateTimeImmutable('today');
$diffDays = (int)$anchor->diff($today)->format('%r%a');
$idx = (int)floor($diffDays / $len);
if ($diffDays < 0) $idx = (int)ceil($diffDays / $len) - 1;

$currentStart = $anchor->modify('+' . ($idx*$len) . ' days');
$nextStart = $currentStart->modify('+' . $len . ' days');
$nextEnd = $nextStart->modify('+' . ($len-1) . ' days');

$sv = (int)$state['current_settings_version_id'];
PayPeriod::ensure($nextStart->format('Y-m-d'), $nextEnd->format('Y-m-d'), $sv);

echo "OK: ensured next pay period " . $nextStart->format('Y-m-d') . " through " . $nextEnd->format('Y-m-d') . PHP_EOL;
