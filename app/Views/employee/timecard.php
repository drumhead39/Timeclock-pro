<?php
use App\Core\Csrf;
/** @var array $user */
/** @var array $payPeriod */
/** @var array $timecard */
/** @var array $settings */
/** @var array $dayRows */
/** @var array $periods */

// New vars passed from controller:
// /** @var int $pto_bank */
// /** @var int $pto_used_ytd */
// /** @var int $pto_available */
?>

<?php
$inc = (int)($settings['time_increment_minutes'] ?? 15);
$inc = ($inc > 0 && $inc <= 60) ? $inc : 15;

// Hard limits requested: 5:00 AM to 8:00 PM
$startHour = 5;   // 05:00
$endHour   = 20;  // 20:00

if (!function_exists('time_options')) {
  function time_options(?string $selected, int $inc, int $startHour, int $endHour): void {
    $sel = $selected ? substr($selected, 0, 5) : '';

    $start = max(0, min(23, $startHour)) * 60;
    $end   = max(0, min(23, $endHour)) * 60;

    if ($end < $start) { [$start, $end] = [$end, $start]; }

    for ($mins = $start; $mins <= $end; $mins += $inc) {
      $h = intdiv($mins, 60);
      $m = $mins % 60;

      $value = sprintf('%02d:%02d', $h, $m);

      $h12 = $h % 12;
      if ($h12 === 0) $h12 = 12;
      $ampm = ($h < 12) ? 'AM' : 'PM';
      $label = sprintf('%d:%02d %s', $h12, $m, $ampm);

      $is = ($value === $sel) ? ' selected' : '';
      echo '<option value="' . e($value) . '"' . $is . '>' . e($label) . '</option>';
    }
  }
}

// --- PTO display helpers (bank / used / remaining) ---
$ptoBank = (int)($pto_bank ?? 0);
$ptoUsedYtd = (int)($pto_used_ytd ?? 0);
$ptoAvail = (int)($pto_available ?? 0);

// Exclude the currently-saved PTO minutes from YTD so the “remaining” can update nicely while typing
$ptoCurrent = (int)($timecard['pto_minutes'] ?? 0);
$ptoUsedExclCurrent = max(0, $ptoUsedYtd - $ptoCurrent);
?>

<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Timecard</div>
      <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>
<?php if (!empty($periods) && is_array($periods)): ?>
<form method="get" action="<?= e(url('/timecard')) ?>" style="margin-top:6px">
  <label style="display:block; font-size:12px; font-weight:700; opacity:.85; margin-bottom:4px">Select Pay Period</label>
  <select name="pp" class="input" style="max-width:340px" onchange="this.form.submit()">
    <?php foreach ($periods as $p): ?>
      <option value="<?= e((string)$p['id']) ?>" <?= ((int)$p['id'] === (int)$payPeriod['id']) ? 'selected' : '' ?>>
        <?= e(fmt_date($p['start_date']) . ' through ' . fmt_date($p['end_date'])) ?><?= !empty($p['locked_at']) ? ' (Locked)' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>

      <div class="badge">Rounding: <?= e($settings['rounding_mode'] ?? 'nearest') ?> • Increment: <?= e((string)($settings['time_increment_minutes'] ?? 15)) ?> minutes</div>

      <!-- PTO bank/used/remaining -->
      <div class="badge ok">
        PTO Remaining: <span class="mono" id="ptoRemaining"
          data-bank="<?= e((string)$ptoBank) ?>"
          data-used-excl="<?= e((string)$ptoUsedExclCurrent) ?>"
        ><?= e(minutes_to_hhmm($ptoAvail)) ?></span>
      </div>
      <div class="badge">
        PTO Bank: <span class="mono"><?= e(minutes_to_hhmm($ptoBank)) ?></span>
      </div>
      <div class="badge">
        PTO Used YTD: <span class="mono"><?= e(minutes_to_hhmm($ptoUsedYtd)) ?></span>
      </div>
    </div>

    <div>
      <?php if (!empty($timecard['locked_at'])): ?>
        <span class="badge danger">Locked</span>
      <?php elseif (!empty($timecard['submitted_at'])): ?>
        <span class="badge warn">Submitted</span>
      <?php else: ?>
        <span class="badge">Draft</span>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" action="<?= e(url('/timecard/save')) ?>">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>

    <div class="bd">
      <div class="notice">
        <strong>Tip:</strong> Your current paper sheet is organized as two 1-week blocks (Mon–Sat) with Time IN/OUT and totals, plus a PTO hours line.
      </div>

      <div class="table-wrap" style="margin-top:12px">
        <table class="table time-entry-table">
          <thead>
            <tr>
              <th style="width:160px">Day / Date</th>
              <th style="width:160px">Time IN</th>
              <th style="width:160px">Time OUT</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $totalMinutes = 0;
            $weekBreak = (int)ceil(count($dayRows) / 2);
            $i = 0;

            foreach ($dayRows as $r):

              if ($i === 0): ?>
                <tr class="week-sep"><td colspan="4">Week 1</td></tr>
              <?php elseif ($i === $weekBreak): ?>
                <tr class="week-sep"><td colspan="4">Week 2</td></tr>
              <?php endif;

              $totalMinutes += (int)$r['minutes'];
            ?>
              <tr>
                <td>
                  <div style="font-weight:800"><?= e($r['day_name']) ?></div>
                  <small class="mono"><?= e(fmt_date($r['date'])) ?></small>
                </td>

                <td>
                  <?php if (!empty($timecard['locked_at'])): ?>
                    <span class="mono"><?= e($r['time_in'] ? substr($r['time_in'], 0, 5) : '') ?></span>
                  <?php else: ?>
                    <select name="rows[<?= e($r['date']) ?>][in]" class="input">
                      <option value="">--</option>
                      <?php time_options($r['time_in'] ?? null, $inc, $startHour, $endHour); ?>
                    </select>
                  <?php endif; ?>
                </td>

                <td>
                  <?php if (!empty($timecard['locked_at'])): ?>
                    <span class="mono"><?= e($r['time_out'] ? substr($r['time_out'], 0, 5) : '') ?></span>
                  <?php else: ?>
                    <select name="rows[<?= e($r['date']) ?>][out]" class="input">
                      <option value="">--</option>
                      <?php time_options($r['time_out'] ?? null, $inc, $startHour, $endHour); ?>
                    </select>
                  <?php endif; ?>
                </td>

                <td class="mono"><?= e($r['minutes'] ? minutes_to_hhmm((int)$r['minutes']) : '') ?></td>
              </tr>
            <?php $i++; endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="row" style="margin-top:14px">
        <div>
          
<?php
$wlUnits = (int)($timecard['weight_loss_units'] ?? 0);
$wlBonus = $wlUnits * 20;
$nursingEncounters = (int)($timecard['nursing_encounters'] ?? 0);
?>
<?php if (!empty($user['weight_loss_consultant'])): ?>
  <label>Weight Loss Programs Sold (this pay period)</label>
  <?php if (!empty($timecard['locked_at'])): ?>
    <div class="input mono"><?= e((string)$wlUnits) ?></div>
  <?php else: ?>
    <input class="input" type="number" step="1" min="0" name="weight_loss_units" value="<?= e((string)$wlUnits) ?>"/>
  <?php endif; ?>
  <small class="mono">Bonus: $20 per program (<?= e('$' . number_format($wlBonus, 2)) ?> this period)</small>
  <div style="height:12px"></div>
<?php endif; ?>
<?php if (!empty($user['nursing_encounters_enabled'])): ?>
  <label>Nursing Encounters</label>
  <?php if (!empty($timecard['locked_at'])): ?>
    <div class="input mono"><?= e((string)$nursingEncounters) ?></div>
  <?php else: ?>
    <input class="input" type="number" step="1" min="0" name="nursing_encounters" value="<?= e((string)$nursingEncounters) ?>"/>
  <?php endif; ?>
  <div style="height:12px"></div>
<?php endif; ?>
          <label>PTO Hours (decimal allowed, e.g., 1.5)</label>

          <?php
          $pto = (int)($timecard['pto_minutes'] ?? 0);
          $ptoDec = $pto ? round($pto/60, 2) : '';

          // Max PTO available = bank minus used YTD (excluding current timecard PTO)
          $ptoMaxMinutes = max(0, $ptoBank - $ptoUsedExclCurrent);
          $ptoMaxHours = round($ptoMaxMinutes / 60, 2);
          ?>

          <?php if (!empty($timecard['locked_at'])): ?>
            <div class="input mono"><?= e($pto ? minutes_to_hhmm($pto) : '') ?></div>
          <?php else: ?>
            <input
              class="input"
              id="ptoHours"
              type="number"
              step="0.25"
              min="0"
              max="<?= e((string)$ptoMaxHours) ?>"
              name="pto_hours"
              value="<?= e((string)$ptoDec) ?>"
            />
          <?php endif; ?>

          <small class="mono" id="ptoHelp">
            Max available this period: <?= e((string)$ptoMaxHours) ?> hours (<?= e(minutes_to_hhmm($ptoMaxMinutes)) ?>).
          </small>
        </div>
        <div>
          <label>Totals</label>
          <div class="notice">
            <div><strong>Work Total:</strong> <span class="mono"><?= e(minutes_to_hhmm($totalMinutes)) ?></span></div>
            <div><strong>PTO:</strong> <span class="mono"><?= e(minutes_to_hhmm($pto)) ?></span></div>
            <div><strong>Grand Total:</strong> <span class="mono"><?= e(minutes_to_hhmm($totalMinutes + $pto)) ?></span></div>

<?php if (!empty($user['weight_loss_consultant'])): ?>
  <div><strong>Weight Loss Programs:</strong> <span class="mono"><?= e((string)$wlUnits) ?></span></div>
  <div><strong>Weight Loss Bonus:</strong> <span class="mono"><?= e('$' . number_format($wlUnits * 20, 2)) ?></span></div>
<?php endif; ?>
<?php if (!empty($user['nursing_encounters_enabled'])): ?>
  <div><strong>Nursing Encounters:</strong> <span class="mono"><?= e((string)$nursingEncounters) ?></span></div>
<?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-actions">
      <button class="btn secondary" type="submit" <?= !empty($timecard['locked_at']) ? 'disabled' : '' ?>>Save Changes</button>
    </div>
  </form>

  <form method="post" action="<?= e(url('/timecard/submit')) ?>" onsubmit="return confirm('Final submit? Please confirm your hours and PTO are correct.');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
    <div class="footer-actions">
      <button class="btn ok" type="submit" <?= (!empty($timecard['locked_at'])) ? 'disabled' : '' ?>>Final Submit</button>
    </div>
  </form>
</div>

<!-- Live update PTO Remaining as the user types PTO hours -->
<script>
(function(){
  const remEl = document.getElementById('ptoRemaining');
  const input = document.querySelector('input[name="pto_hours"]');
  if(!remEl || !input) return;

  const bank = parseInt(remEl.dataset.bank || '0', 10);
  const usedExcl = parseInt(remEl.dataset.usedExcl || '0', 10);

  function mm(mins){
    mins = Math.max(0, mins|0);
    const h = Math.floor(mins/60);
    const m = mins%60;
    return h + ':' + String(m).padStart(2,'0');
  }

  function update(){
    const hours = parseFloat(input.value || '0');
    const mins = Math.round(hours * 60);
    const remaining = bank - usedExcl - mins;
    remEl.textContent = mm(remaining);
  }

  input.addEventListener('input', update);
  update();
})();
</script>

<script>
(function(){
  const input = document.getElementById('ptoHours');
  const help  = document.getElementById('ptoHelp');
  if(!input) return;

  const max = parseFloat(input.getAttribute('max') || '0');

  function clamp(){
    let v = parseFloat(input.value || '0');
    if (isNaN(v) || v < 0) v = 0;

    // round to nearest 0.25
    v = Math.round(v / 0.25) * 0.25;

    if (v > max) {
      v = max;
      if (help) {
        help.innerHTML = "That exceeds your available PTO. Max is <strong>" + max.toFixed(2).replace(/\.00$/,'') + "</strong> hours.";
      }
    } else if (help) {
      help.innerHTML = "Max available this period: <strong>" + max.toFixed(2).replace(/\.00$/,'') + "</strong> hours.";
    }

    input.value = v.toFixed(2).replace(/\.00$/,'');
  }

  input.addEventListener('blur', clamp);
  input.addEventListener('change', clamp);
})();
</script>

