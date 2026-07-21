<?php
use App\Core\Csrf;
/** @var array $prov */
/** @var array $payPeriod */
/** @var array $periods */
/** @var array $rows */
// PTO vars passed from controller:
// /** @var int $pto_bank */
// /** @var int $pto_used_ytd */
// /** @var int $pto_available */
// /** @var int $pto_minutes */
// /** @var int $pto_used_excl_current */
$locked = false;
foreach ($rows as $r) { if (!empty($r['locked_at'])) { $locked = true; break; } }

// --- PTO display helpers (bank / used / remaining) ---
$ptoBank = (int)($pto_bank ?? 0);
$ptoUsedYtd = (int)($pto_used_ytd ?? 0);
$ptoAvail = (int)($pto_available ?? 0);
$ptoCurrent = (int)($pto_minutes ?? 0);
$ptoUsedExcl = (int)($pto_used_excl_current ?? max(0, $ptoUsedYtd - $ptoCurrent));
$showPto = ($ptoBank > 0 || $ptoCurrent > 0 || $ptoUsedYtd > 0);
$ptoDec = $ptoCurrent ? number_format($ptoCurrent/60, 2, '.', '') : '';
$ptoMaxDec = ($ptoBank > 0) ? number_format(max(0, ($ptoBank - $ptoUsedExcl))/60, 2, '.', '') : '';
$isSuper = (bool)($isSuper ?? false);
$backUrl = (string)($backUrl ?? ("/timecards?pp=" . (string)$payPeriod["id"]));
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Provider Production</div>
      <div class="badge"><?= e($prov['full_name']) ?> • <?= e($prov['provider_type']) ?></div>
      <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>

      <?php if ($showPto): ?>
        <div class="badge ok">
          PTO Remaining: <span class="mono" id="provPtoRemaining"
            data-bank="<?= e((string)$ptoBank) ?>"
            data-used-excl="<?= e((string)$ptoUsedExcl) ?>"
          ><?= e(minutes_to_hours_decimal($ptoAvail)) ?></span>
        </div>
        <div class="badge">PTO Bank: <span class="mono"><?= e(minutes_to_hours_decimal($ptoBank)) ?></span></div>
        <div class="badge">PTO Used YTD: <span class="mono"><?= e(minutes_to_hours_decimal($ptoUsedYtd)) ?></span></div>
      <?php endif; ?>
      <?php if ($locked): ?>
        <span class="badge danger">Locked</span>
      <?php endif; ?>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
      <a class="btn secondary" href="<?= e(url($backUrl)) ?>">Back</a>
    </div>
  </div>

  <div class="bd">
    <form method="get" action="<?= e(url('/provider-production/' . (string)$prov['id'])) ?>">
      <label>Select Pay Period</label>
      <select class="input" name="pp" onchange="this.form.submit()">
        <?php foreach ($periods as $p): ?>
          <option value="<?= e((string)$p['id']) ?>" <?= ((int)$p['id']===(int)$payPeriod['id']) ? 'selected' : '' ?>>
            <?= e(fmt_date($p['start_date']) . ' through ' . fmt_date($p['end_date'])) ?>
            <?= $p['locked_at'] ? ' (LOCKED)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <hr class="sep"/>

    <form method="post" action="<?= e(url('/provider-production/save')) ?>">
      <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
      <input type="hidden" name="provider_id" value="<?= e((string)$prov['id']) ?>"/>
      <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>

      <table class="table production-entry-table">
        <thead>
          <tr>
            <th>Type</th>
            <th style="width:140px">Count</th>
            <th style="width:140px">Rate</th>
            <th style="width:160px">Line Total</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $total = 0.0;
          foreach ($rows as $r):
            $count = (int)$r['count'];
            $rate = $r['rate_used'] !== null ? (float)$r['rate_used'] : (float)($r['effective_rate'] ?? 0.0);
            $line = $count * $rate;
            $total += $line;
        ?>
          <tr>
            <td><?= e($r['type_name']) ?></td>
            <td>
              <?php if ($locked): ?>
                <span class="mono"><?= e((string)$count) ?></span>
              <?php else: ?>
                <input class="input" type="number" min="0" step="1" name="counts[<?= e((string)$r['appointment_type_id']) ?>]" value="<?= e((string)$count) ?>"/>
              <?php endif; ?>
            </td>
            <td class="mono"><?= e(number_format($rate, 2, '.', '')) ?></td>
            <td class="mono"><?= e(number_format($line, 2, '.', '')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($showPto): ?>
      <div class="row" style="margin-top:12px">
        <div>
          <label>PTO Hours (this pay period)</label>
          <?php if ($locked || !$isSuper): ?>
            <div class="input mono"><?= e($ptoDec !== '' ? $ptoDec : '0.00') ?></div>
          <?php else: ?>
            <input class="input" type="number" min="0" step="0.25" name="pto_hours" value="<?= e((string)$ptoDec) ?>"
              <?= $ptoMaxDec !== '' ? 'max="' . e((string)$ptoMaxDec) . '"' : '' ?> />
            <?php if ($ptoMaxDec !== ''): ?>
              <small class="mono">Max available this period: <?= e((string)$ptoMaxDec) ?> hours</small>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="notice" style="margin-top:12px">
        <strong>Total:</strong> <span class="mono"><?= e(number_format($total, 2, '.', '')) ?></span>
        <div style="margin-top:6px">
          Rates are resolved as-of the pay period end date, and are snapshotted into <span class="mono">rate_used</span> when locked.
        </div>
      </div>

      <div class="footer-actions">
        <button class="btn ok" type="submit" <?= $locked ? 'disabled' : '' ?>>Save</button>
      </div>
    </form>

    <div class="footer-actions">
      <form method="post" action="<?= e(url('/provider-production/lock')) ?>" style="margin:0" onsubmit="return confirm('Lock or re-submit this provider production card? After corrections are complete, use Regenerate PDF + Email for the pay period.');">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
        <input type="hidden" name="provider_id" value="<?= e((string)$prov['id']) ?>"/>
        <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
        <button class="btn danger" type="submit" <?= $locked ? 'disabled' : '' ?>><?= !empty($payPeriod['locked_at']) ? 'Relock / Resubmit' : 'Lock' ?></button>
      </form>

      <?php if ($isSuper): ?>
      <form method="post" action="<?= e(url('/provider-production/unlock')) ?>" style="margin:0" onsubmit="return confirm('Unlock this provider production card?');">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
        <input type="hidden" name="provider_id" value="<?= e((string)$prov['id']) ?>"/>
        <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
        <button class="btn secondary" type="submit">Unlock</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>


<?php if ($showPto && !$locked): ?>
<script>
(function(){
  const remEl = document.getElementById('provPtoRemaining');
  const input = document.querySelector('input[name="pto_hours"]');
  if(!remEl || !input) return;

  const bank = parseInt(remEl.dataset.bank || '0', 10);
  const usedExcl = parseInt(remEl.dataset.usedExcl || '0', 10);
  const maxMins = Math.max(0, bank - usedExcl);

  function toMins(val){
    const h = parseFloat(val || '0');
    if (isNaN(h) || h < 0) return 0;
    return Math.round(h * 60);
  }
  function fmtHours(mins){
    mins = Math.max(0, mins|0);
    return (mins/60).toFixed(2);
  }
  function update(){
    let mins = toMins(input.value);
    if (mins > maxMins) {
      mins = maxMins;
      input.value = fmtHours(mins);
    }
    const remaining = bank - usedExcl - mins;
    remEl.textContent = fmtHours(remaining);
  }

  input.addEventListener('input', update);
  update();
})();
</script>
<?php endif; ?>

