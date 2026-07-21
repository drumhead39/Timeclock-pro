<?php
use App\Core\Csrf;
/** @var array $payPeriod */
/** @var array $periods */
/** @var array $status */
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Provider Production</div>
      <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
      <a class="btn secondary" href="<?= e(url('/admin/providers')) ?>">Providers</a>
      <a class="btn secondary" href="<?= e(url('/admin/appointment-types')) ?>">Appointment Types</a>
    </div>
  </div>

  <div class="bd">
    <form method="get" action="<?= e(url('/admin/provider-production')) ?>">
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

    <?php if (!empty($payPeriod['locked_at'])): ?>
      <form method="post" action="<?= e(url('/admin/payperiod/regenerate')) ?>" style="margin-top:10px" onsubmit="return confirm('Regenerate this pay period report? This will re-lock any corrected/unlocked cards, overwrite the stored PDF, and email it again.');">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
        <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
        <button class="btn ok" type="submit">Regenerate PDF + Email</button>
      </form>
      <div class="notice" style="margin-top:10px">
        Use this after unlocking, editing, and re-locking provider production for a past pay period.
      </div>
    <?php endif; ?>

    <table class="table" style="margin-top:12px">
      <thead><tr><th>Provider</th><th>Type</th><th>Submitted</th><th>Locked</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($status as $s): ?>
        <tr>
          <td><?= e($s['full_name']) ?></td>
          <td class="mono"><?= e($s['provider_type']) ?></td>
          <td><?= $s['submitted_at'] ? '<span class="badge warn">Yes</span>' : '<span class="badge">No</span>' ?></td>
          <td><?= $s['locked_at'] ? '<span class="badge danger">Yes</span>' : '<span class="badge">No</span>' ?></td>
          <td style="text-align:right">
            <a class="btn secondary" href="<?= e(url('/admin/provider-production/' . (string)$s['provider_id'] . '?pp=' . (string)$payPeriod['id'])) ?>">View/Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="notice" style="margin-top:12px">
      Providers are admin-only. Enter counts, then lock to snapshot rates used for reporting.
    </div>
  </div>
</div>
