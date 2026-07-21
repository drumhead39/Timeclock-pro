<?php
declare(strict_types=1);

namespace App\Services;

final class TimeService {

  public static function timeToMinutes(?string $t): ?int {
    if ($t === null || $t === '') return null;
    if (!preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $t, $m)) return null;
    $hours = (int)$m[1];
    $minutes = (int)$m[2];
    if ($hours > 23 || $minutes > 59) return null;
    return $hours * 60 + $minutes;
  }

  public static function minutesToTime(?int $m): ?string {
    if ($m === null) return null;
    $m = max(0, min(1439, $m));
    $h = intdiv($m, 60);
    $mm = $m % 60;
    return sprintf('%02d:%02d:00', $h, $mm);
  }

  public static function roundMinutes(int $minutes, array $cfg): int {
    $allowed = $cfg['allowed_minutes'] ?? [0,15,30,45];
    $mode = $cfg['rounding_mode'] ?? 'nearest'; // nearest|down|up
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;

    // Find closest allowed minute for this hour.
    $candidates = [];
    foreach ($allowed as $am) {
      $am = (int)$am;
      if ($am < 0 || $am > 59) continue;
      $candidates[] = $h*60 + $am;
    }
    sort($candidates);

    if (!$candidates) return $minutes;

    if ($mode === 'down') {
      $best = $candidates[0];
      foreach ($candidates as $c) if ($c <= $minutes) $best = $c;
      // if minutes is before first candidate, go to previous hour last candidate
      if ($minutes < $candidates[0]) {
        $prevh = max(0, $h-1);
        $best = $prevh*60 + (int)max($allowed);
      }
      return $best;
    }

    if ($mode === 'up') {
      foreach ($candidates as $c) if ($c >= $minutes) return $c;
      // after last candidate, go to next hour first candidate
      $nexth = min(23, $h+1);
      return $nexth*60 + (int)min($allowed);
    }

    // nearest
    $best = $candidates[0];
    $bestDist = abs($best - $minutes);
    foreach ($candidates as $c) {
      $d = abs($c - $minutes);
      if ($d < $bestDist) { $best = $c; $bestDist = $d; }
    }
    // if before first candidate and nearest would be that candidate, ok; no cross-hour nearest for simplicity
    return $best;
  }

  public static function roundTime(?string $timeHHMM, array $cfg): ?string {
    $m = self::timeToMinutes($timeHHMM);
    if ($m === null) return null;
    $rm = self::roundMinutes($m, $cfg);
    return self::minutesToTime($rm);
  }

  /**
   * Validate and normalize submitted time-entry rows before any database writes.
   * Unknown dates and malformed time values are rejected rather than silently saved.
   */
  public static function normalizeRows(array $rows, array $payPeriod, array $cfg): array {
    $startText = (string)($payPeriod['start_date'] ?? '');
    $endText = (string)($payPeriod['end_date'] ?? '');
    $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $startText);
    $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $endText);
    if (!$start || !$end || $start->format('Y-m-d') !== $startText || $end->format('Y-m-d') !== $endText || $end < $start) {
      throw new \InvalidArgumentException('The selected pay period is invalid.');
    }

    $daysToShow = array_values(array_filter(
      array_map('intval', (array)($cfg['days_to_show'] ?? [1,2,3,4,5,6])),
      static fn(int $day): bool => $day >= 1 && $day <= 7
    ));
    $allowedDates = [];
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
      if (in_array((int)$date->format('N'), $daysToShow, true)) {
        $allowedDates[$date->format('Y-m-d')] = true;
      }
    }

    $normalized = [];
    foreach ($rows as $workDate => $values) {
      if (!is_string($workDate) || !isset($allowedDates[$workDate]) || !is_array($values)) {
        throw new \InvalidArgumentException('A submitted timecard row is outside the selected pay period.');
      }

      $timeInText = trim((string)($values['in'] ?? ''));
      $timeOutText = trim((string)($values['out'] ?? ''));
      $timeIn = $timeInText === '' ? null : self::roundTime($timeInText, $cfg);
      $timeOut = $timeOutText === '' ? null : self::roundTime($timeOutText, $cfg);

      if (($timeInText !== '' && $timeIn === null) || ($timeOutText !== '' && $timeOut === null)) {
        throw new \InvalidArgumentException('A submitted time value is invalid.');
      }

      $normalized[$workDate] = ['in' => $timeIn, 'out' => $timeOut];
    }

    return $normalized;
  }

  public static function durationMinutes(?string $in, ?string $out): int {
    $mi = self::timeToMinutes($in);
    $mo = self::timeToMinutes($out);
    if ($mi === null || $mo === null) return 0;
    $d = $mo - $mi;
    if ($d < 0) return 0;
    return $d;
  }

  public static function minutesToDecimalHours(int $minutes): float {
    return round($minutes / 60.0, 2);
  }
}
