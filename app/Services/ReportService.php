<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Models\User;
use App\Models\TimeEntry;
use App\Models\Timecard;
use App\Models\Settings;
use App\Models\PayPeriod;
use App\Models\Provider;
use App\Models\ProviderProduction;
use App\Models\ProviderPto;
use App\Models\ProviderRate;
use App\Models\AppointmentType;

final class ReportService {

  /**
   * Dates are stored as YYYY-MM-DD, but we display as MM/DD/YYYY.
   * If the app already has a global fmt_date() helper, use it.
   */
  private static function fmtDate(string $ymd): string {
    if (function_exists('fmt_date')) {
      /** @var callable $f */
      $f = 'fmt_date';
      return (string)$f($ymd);
    }

    $d = $ymd;
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $ymd, $m)) {
      $d = $m[1];
    }
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $d);
    return $dt ? $dt->format('m/d/Y') : $ymd;
  }

  /**
   * Times are stored as HH:MM or HH:MM:SS (24h). Display as h:mm AM/PM.
   */
  private static function fmtTime(?string $t): string {
    if (!$t) return '';
    $t = trim($t);
    if ($t === '') return '';
    // Already formatted (e.g., "6:30 AM")
    if (preg_match('/\b(AM|PM)\b/i', $t)) return $t;
    $hhmm = substr($t, 0, 5);
    $dt = \DateTimeImmutable::createFromFormat('H:i', $hhmm);
    return $dt ? $dt->format('g:i A') : $hhmm;
  }

  private static function hoursDecimal(int $minutes): string {
    // Keep 2 decimals for payroll friendliness, e.g. 67.75
    return number_format($minutes / 60, 2, '.', '');
  }

  private static function pdfEscape(string $s): string {
    return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\(', '\)', ''], $s);
  }

  private static function makePdf(array $linesByPage): string {
    // Minimal PDF 1.4 generator with Helvetica Type1
    $objects = [];
    $offsets = [];

    $addObj = function(string $obj) use (&$objects) {
      $objects[] = $obj;
      return count($objects);
    };

    // Use a monospaced font so columns line up (spaces align).
    $fontObjNum = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>");

    $pageKids = [];
    $contentsObjNums = [];
    foreach ($linesByPage as $pageLines) {
      $text = "BT\n/F1 10 Tf\n72 760 Td\n12 TL\n";
      foreach ($pageLines as $i => $line) {
        if ($i === 0) {
          $text .= "(" . self::pdfEscape($line) . ") Tj\n";
        } else {
          $text .= "T*\n(" . self::pdfEscape($line) . ") Tj\n";
        }
      }
      $text .= "ET\n";
      $stream = "<< /Length " . strlen($text) . " >>\nstream\n" . $text . "endstream";
      $contentsObjNums[] = $addObj($stream);
    }

    $pagesObjNum = 0; // placeholder
    // Create page objects
    foreach ($linesByPage as $idx => $_) {
      $contentNum = $contentsObjNums[$idx];
      $pageObj = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$fontObjNum} 0 R >> >> /Contents {$contentNum} 0 R >>";
      $pageKids[] = $addObj($pageObj);
    }

    // Pages object (must be object 2 for the hard-coded Parent above)
    // We'll insert as object #2 by building final list carefully.
    // Easiest: rebuild objects with fixed numbering.
    // We'll rebuild now:

    $rebuilt = [];
    $rebuilt[] = null; // index 0 unused
    $rebuilt[] = "<< /Type /Catalog /Pages 2 0 R >>"; // 1
    // 2 pages object
    $kidsRefs = implode(' ', array_map(fn($n) => "{$n} 0 R", range(4, 3 + count($linesByPage))));
    $rebuilt[] = "<< /Type /Pages /Count " . count($linesByPage) . " /Kids [ {$kidsRefs} ] >>"; // 2
    // 3 font
    // 3 font (monospace)
    $rebuilt[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>"; // 3

    // Page objects start at 4
    $pageCount = count($linesByPage);
    for ($i=0; $i<$pageCount; $i++){
      $contentObjNum = 4 + $pageCount + $i; // contents start after all pages
      $rebuilt[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObjNum} 0 R >>";
    }

    // Contents objects
    foreach ($linesByPage as $pageLines) {
      $text = "BT\n/F1 10 Tf\n72 760 Td\n12 TL\n";
      foreach ($pageLines as $i => $line) {
        if ($i === 0) $text .= "(" . self::pdfEscape($line) . ") Tj\n";
        else $text .= "T*\n(" . self::pdfEscape($line) . ") Tj\n";
      }
      $text .= "ET\n";
      $rebuilt[] = "<< /Length " . strlen($text) . " >>\nstream\n" . $text . "endstream";
    }

    // Build PDF with xref
    $pdf = "%PDF-1.4\n";
    $xref = "xref\n0 " . count($rebuilt) . "\n";
    $xref .= "0000000000 65535 f \n";
    $offset = strlen($pdf);
    for ($i=1; $i<count($rebuilt); $i++){
      $offsets[$i] = $offset;
      $obj = $rebuilt[$i];
      $chunk = "{$i} 0 obj\n{$obj}\nendobj\n";
      $pdf .= $chunk;
      $offset += strlen($chunk);
    }
    foreach ($offsets as $i => $off) {
      $xref .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $trailer = "trailer\n<< /Size " . count($rebuilt) . " /Root 1 0 R >>\nstartxref\n{$offset}\n%%EOF";
    return $pdf . $xref . $trailer;
  }

  public static function generatePayPeriodPdf(int $payPeriodId, string $saveDir): array {
    $pp = PayPeriod::findById($payPeriodId);
    if (!$pp) throw new \RuntimeException("Pay period not found.");
    $start = $pp['start_date'];
    $end = $pp['end_date'];

    $state = Settings::appState();
    $company = $state['company_name'] ?: 'TimeClock';
    $emailTo = $state['company_email'] ?: '';

    $users = DB::pdo()->query("SELECT * FROM users WHERE active=1 ORDER BY role DESC, full_name ASC")->fetchAll();

    $lines = [];
    $lines[] = "{$company} - Timecard Report";
    $lines[] = "Pay Period: " . self::fmtDate((string)$start) . " through " . self::fmtDate((string)$end);
    $lines[] = str_repeat('-', 72);
    $lines[] = "";

    foreach ($users as $u) {
      if ($u['role'] !== 'employee' && $u['role'] !== 'super') continue;
      $uid = (int)$u['id'];
      $tc = \App\Models\Timecard::ensure($uid, $payPeriodId);
      $entries = \App\Models\TimeEntry::byUserPeriod($uid, $payPeriodId);

      $lines[] = "Employee: " . $u['full_name'] . " <" . $u['email'] . ">";
      $lines[] = "Submitted: " . ($tc['submitted_at'] ? (string)$tc['submitted_at'] : 'No') . "   Locked: " . ($tc['locked_at'] ? (string)$tc['locked_at'] : 'No');

      // Fixed-width columns for consistent alignment.
      // Date=10 (MM/DD/YYYY), In=8 ("12:00 PM"), Out=8, Hours=6 ("100.00")
      $lines[] = sprintf('%-10s  %-8s  %-8s  %6s', 'Date', 'In', 'Out', 'Hours');
      $lines[] = sprintf('%-10s  %-8s  %-8s  %6s', str_repeat('-', 10), str_repeat('-', 8), str_repeat('-', 8), str_repeat('-', 6));

      $sum = 0;
      $d = new \DateTimeImmutable($start);
      $endD = new \DateTimeImmutable($end);
      while ($d <= $endD) {
        $day = $d->format('Y-m-d');
        $e = $entries[$day] ?? null;
        $in = $e ? self::fmtTime($e['time_in']) : '';
        $out = $e ? self::fmtTime($e['time_out']) : '';
        $dur = $e ? \App\Services\TimeService::durationMinutes($e['time_in'], $e['time_out']) : 0;
        $sum += $dur;
        $h = $dur ? self::hoursDecimal($dur) : '';
        $lines[] = sprintf('%-10s  %-8s  %-8s  %6s', self::fmtDate($day), $in, $out, $h);
        $d = $d->modify('+1 day');
      }

      $pto = (int)($tc['pto_minutes'] ?? 0);
      $grand = $sum + $pto;
      $lines[] = "";
      $lines[] = "Work Hours (decimal): " . self::hoursDecimal($sum);
      $lines[] = "PTO Hours (decimal): " . self::hoursDecimal($pto);

      $nursingEncounters = (int)($tc['nursing_encounters'] ?? 0);
      if (!empty($u['nursing_encounters_enabled'])) {
        $lines[] = "Nursing Encounters: " . $nursingEncounters . " x rate = ____________";
        $lines[] = "Grand Total(decimal): " . self::hoursDecimal($grand) . " + Nursing Encounters Total";
      } else {
        $lines[] = "Grand Total(decimal): " . self::hoursDecimal($grand);
      }

      $wlUnits = (int)($tc['weight_loss_units'] ?? 0);
      if (!empty($u['weight_loss_consultant']) && $wlUnits > 0) {
        $lines[] = "Weight Loss Programs: " . $wlUnits;
        $lines[] = "Weight Loss Bonus: $" . number_format($wlUnits * 20, 2);
      }

      $lines[] = str_repeat('-', 72);
      $lines[] = "";
    }

    
// --- Provider Production (admin-only) ---
try {
  $providers = DB::pdo()->query("SELECT * FROM providers WHERE active=1 ORDER BY provider_type ASC, full_name ASC")->fetchAll();
} catch (\Throwable $e) {
  $providers = [];
}
if ($providers) {
  $lines[] = "";
  $lines[] = "PROVIDER PRODUCTION";
  $lines[] = str_repeat('-', 72);
  $lines[] = "";

  foreach ($providers as $p) {
    $pid = (int)$p['id'];
    ProviderProduction::ensureRows($pid, $payPeriodId);
    $rows = ProviderProduction::rowsForProviderPeriod($pid, $payPeriodId);

    $lines[] = "Provider: " . $p['full_name'] . " (" . strtoupper((string)$p['provider_type']) . ")";
    // Fixed-width columns: Type=20, Count=5, Rate=8, Total=10
    $lines[] = sprintf('%-20s  %5s  %8s  %10s', 'Type', 'Count', 'Rate', 'Line Total');
    $lines[] = sprintf('%-20s  %5s  %8s  %10s', str_repeat('-', 20), str_repeat('-', 5), str_repeat('-', 8), str_repeat('-', 10));

    $provTotal = 0.0;
    foreach ($rows as $r) {
      $typeName = (string)$r['type_name'];
      $count = (int)$r['count'];

      $rate = $r['rate_used'] !== null ? (float)$r['rate_used'] : (ProviderRate::effectiveRate($pid, (int)$r['appointment_type_id'], (string)$end) ?? 0.0);
      $lineTotal = $count * $rate;
      $provTotal += $lineTotal;

      $lines[] = sprintf('%-20s  %5d  %8s  %10s',
        (function_exists('mb_strimwidth') ? mb_strimwidth($typeName, 0, 20, '') : substr($typeName, 0, 20)),
        $count,
        number_format($rate, 2, '.', ''),
        number_format($lineTotal, 2, '.', '')
      );
    }

    $lines[] = "";
    $lines[] = "Provider Total: " . number_format($provTotal, 2, '.', '');

    // Provider PTO (hours) is tracked separately from production dollars
    $ptoBank = (int)($p['pto_bank_minutes'] ?? 0);
    $ptoThis = ProviderPto::minutesForProviderPeriod($pid, $payPeriodId);
    if ($ptoBank > 0 || $ptoThis > 0) {
      $year = (int)substr((string)$start, 0, 4);
      // YTD should be "as of" this report's pay period end date (ignore PTO entered on future periods)
      $ptoUsedYtd = ProviderPto::sumUsedYtdThroughDate($pid, $year, (string)$end);
      $ptoAvail = max(0, $ptoBank - $ptoUsedYtd);

      $lines[] = "PTO This Period (hrs): " . minutes_to_hours_decimal($ptoThis);
      $lines[] = "PTO Bank (hrs): " . minutes_to_hours_decimal($ptoBank);
      $lines[] = "PTO Used YTD (hrs): " . minutes_to_hours_decimal($ptoUsedYtd);
      $lines[] = "PTO Remaining (hrs): " . minutes_to_hours_decimal($ptoAvail);
    }
    $lines[] = str_repeat('-', 72);
    $lines[] = "";
  }
}

// paginate
    $linesPerPage = 52;
    $pages = [];
    for ($i=0; $i<count($lines); $i += $linesPerPage) {
      $pages[] = array_slice($lines, $i, $linesPerPage);
    }

    $pdfBin = self::makePdf($pages);

    $filename = "TIMECARD_{$start}-through-{$end}.pdf";
    if (!is_dir($saveDir)) mkdir($saveDir, 0775, true);
    $path = rtrim($saveDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($path, $pdfBin);

    return ['filename' => $filename, 'path' => $path, 'email_to' => $emailTo];
  }
}
