<?php
declare(strict_types=1);

namespace App\Services;

final class TimeService {

  public static function timeToMinutes(?string $t): ?int {
    if ($t === null || $t === '') return null;
    if (!preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $t, $m)) return null;
    return ((int)$m[1])*60 + (int)$m[2];
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
