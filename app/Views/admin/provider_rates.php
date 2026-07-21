<?php
use App\Core\Csrf;
/** @var array $prov */
/** @var array $types */
/** @var array $rates */
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Provider Rates</div>
      <div class="badge"><?= e($prov['full_name']) ?> • <?= e($prov['provider_type']) ?></div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
      <a class="btn secondary" href="<?= e(url('/admin/providers')) ?>">Providers</a>
      <a class="btn secondary" href="<?= e(url('/admin/provider-production')) ?>">Provider Production</a>
    </div>
  </div>

  <div class="bd">
    <div class="notice"><strong>Tip:</strong> To change a start date, add a new rate with a new Effective From. Use Set End to retire old rates.
<br/><br/>
      <strong>Effective dates:</strong> add a new rate with an Effective From date. The app auto-closes the previous open-ended rate (same provider+type) the day before.
    </div>

    <div class="card" style="margin-top:12px">
      <div class="hd"><div style="font-weight:800">Add Rate</div></div>
      <div class="bd">
        <form method="post" action="<?= e(url('/admin/providers/' . (string)$prov['id'] . '/rates/add')) ?>">
          <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
          <div class="row">
            <div>
              <label>Appointment Type</label>
              <select class="input" name="appointment_type_id" required>
                <option value="">-- select --</option>
                <?php foreach ($types as $t): ?>
                  <option value="<?= e((string)$t['id']) ?>"><?= e($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Rate</label>
              <input class="input" type="number" step="0.01" min="0" name="rate" required/>
            </div>
          </div>

          <div class="row" style="margin-top:12px">
            <div>
              <label>Effective From</label>
              <input class="input" type="date" name="effective_from" value="<?= e(date('Y-m-d')) ?>"/>
            </div>
            <div>
              <label>Effective To (optional)</label>
              <input class="input" type="date" name="effective_to"/>
            </div>
          </div>

          <div style="margin-top:12px">
            <button class="btn ok" type="submit">Add Rate</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card" style="margin-top:12px">
      <div class="hd"><div style="font-weight:800">Rate History</div></div>
      <div class="bd">
        <table class="table">
          <thead><tr><th>Type</th><th>Rate</th><th>Effective From</th><th>Effective To</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($rates as $r): ?>
            <tr>
              <td><?= e($r['type_name']) ?></td>
              <td class="mono"><?= e(number_format((float)$r['rate'], 2, '.', '')) ?></td>
              <td class="mono"><?= e(fmt_date($r['effective_from'])) ?></td>
              <td class="mono"><?= e($r['effective_to'] ? fmt_date($r['effective_to']) : '') ?></td>
              <td style="text-align:right">
<div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; align-items:center">
  <form method="post" action="<?= e(url('/admin/providers/' . (string)$prov['id'] . '/rates/update')) ?>" style="display:inline">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="rate_id" value="<?= e((string)$r['id']) ?>"/>
    <input class="input" type="number" step="0.01" min="0" name="rate"
           value="<?= e(number_format((float)$r['rate'], 2, '.', '')) ?>"
           style="width:110px; display:inline-block"/>
    <button class="btn secondary" type="submit">Update</button>
  </form>

  <form method="post" action="<?= e(url('/admin/providers/' . (string)$prov['id'] . '/rates/end')) ?>" style="display:inline" onsubmit="return confirm('Set an end date for this rate?');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="rate_id" value="<?= e((string)$r['id']) ?>"/>
    <input class="input" type="date" name="effective_to"
           style="width:155px; display:inline-block"
           value="<?= e((string)($r['effective_to'] ?? '')) ?>"/>
    <button class="btn secondary" type="submit">Set End</button>
  </form>

  <form method="post" action="<?= e(url('/admin/providers/' . (string)$prov['id'] . '/rates/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this rate row? (Recommended: use Set End instead for history)');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="rate_id" value="<?= e((string)$r['id']) ?>"/>
    <button class="btn danger" type="submit">Delete</button>
  </form>
</div>
</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="notice" style="margin-top:12px">
          Tip: If you want rates to align cleanly, start new rates on a pay period boundary.
        </div>
      </div>
    </div>
  </div>
</div>
