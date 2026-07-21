<?php
use App\Core\Csrf;
/** @var array $user */
/** @var array $payPeriod */
/** @var array $status */
/** @var array $recentPeriods */
?>
<div class="card">
 <div class="hd">
 <div>
 <div style="font-weight:800; font-size:18px">Admin Dashboard</div>
 <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>
<form method="get" action="<?= e(url('/admin')) ?>" style="margin-top:6px">
  <label style="display:block; font-size:12px; font-weight:700; opacity:.85; margin-bottom:4px">Select Pay Period</label>
  <select name="pp" class="input" style="max-width:340px" onchange="this.form.submit()">
    <?php foreach ($recentPeriods as $p): ?>
      <option value="<?= e((string)$p['id']) ?>" <?= ((int)$p['id'] === (int)$payPeriod['id']) ? 'selected' : '' ?>>
        <?= e(fmt_date($p['start_date']) . ' through ' . fmt_date($p['end_date'])) ?><?= !empty($p['locked_at']) ? ' (Locked)' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

 </div>
 <div>
 <?php if (!empty($payPeriod['locked_at'])): ?>
 <span class="badge danger">Pay Period Locked</span>
 <?php else: ?>
 <span class="badge">Pay Period Open</span>
 <?php endif; ?>
 </div>
 </div>
 <div class="bd">
 <div class="notice">
 <strong>Status:</strong> Below shows each employee and whether they have submitted their final timecard for the current two-week pay period.
 </div>

 <table class="table" style="margin-top:12px">
 <thead>
 <tr>
 <th>Employee</th>
 <th>Email</th>
 <th>Submitted</th>
 <th>Locked</th>
 <th></th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($status as $s): ?>
 <tr>
 <td><?= e($s['full_name']) ?></td>
 <td class="mono"><?= e($s['email']) ?></td>
 <td>
 <?php if ($s['submitted_at']): ?><span class="badge warn">Yes</span><?php else: ?><span class="badge">No</span><?php endif; ?>
 </td>
 <td>
 <?php if ($s['locked_at']): ?><span class="badge danger">Yes</span><?php else: ?><span class="badge">No</span><?php endif; ?>
 </td>
 <td style="text-align:right">
 <a class="btn secondary" href="<?= e(url('/admin/timecards/' . (string)$s['user_id'] . '?pp=' . (string)$payPeriod['id'])) ?>">View/Edit</a>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>

 <hr class="sep"/>

 <?php if (empty($payPeriod['locked_at'])): ?>
 <form method="post" action="<?= e(url('/admin/payperiod/lock')) ?>" onsubmit="return confirm('Lock this pay period, generate PDF, and email it?');">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
 <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
 <button class="btn danger" type="submit">Lock Pay Period + Generate PDF + Email</button>
 </form>
 <?php else: ?>
 <form method="post" action="<?= e(url('/admin/payperiod/regenerate')) ?>" onsubmit="return confirm('Regenerate this pay period report? This will re-lock any corrected/unlocked timecards, overwrite the stored PDF, and email it again.');">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
 <input type="hidden" name="pay_period_id" value="<?= e((string)$payPeriod['id']) ?>"/>
 <button class="btn ok" type="submit">Regenerate PDF + Email</button>
 </form>
 <div class="notice" style="margin-top:12px">
 After correcting a past employee timecard or provider production card, use this to overwrite the saved PDF and email the updated report again.
 </div>
 <?php endif; ?>

 <div class="card" style="margin-top:16px">
 <div class="hd">
 <div style="font-weight:800">Recent Pay Periods</div>
 </div>
 <div class="bd">
 <table class="table">
 <thead><tr><th>Start</th><th>End</th><th>Locked</th></tr></thead>
 <tbody>
 <?php foreach ($recentPeriods as $p): ?>
 <tr>
 <td class="mono"><a href="<?= e(url('/admin?pp=' . (string)$p['id'])) ?>"><?= e(fmt_date($p['start_date'])) ?></a></td>
 <td class="mono"><?= e(fmt_date($p['end_date'])) ?></td>
 <td><?= $p['locked_at'] ? '<span class="badge danger">Yes</span>' : '<span class="badge">No</span>' ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 <div class="notice" style="margin-top:12px">
 Tip: Switch pay periods above to review late entries. For a locked period, correct the card, re-lock it, then use Regenerate PDF + Email.
 </div>
 </div>
 </div>
 </div>
</div>
