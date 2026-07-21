<?php
/** @var array $user */
/** @var array $payPeriod */
/** @var array $timecard */
/** @var int $pto_used_ytd */
/** @var int $pto_available */
/** @var int $pto_bank */
?>
<div class="card">
 <div class="hd">
 <div>
 <div style="font-weight:800; font-size:18px">Welcome, <?= e($user['full_name']) ?></div>
 <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>
 </div>
 <div>
 <?php if (!empty($timecard['locked_at'])): ?>
 <span class="badge danger">Locked</span>
 <?php elseif (!empty($timecard['submitted_at'])): ?>
 <span class="badge warn">Submitted</span>
 <?php else: ?>
 <span class="badge">In Progress</span>
 <?php endif; ?>
 </div>
 </div>
 <div class="bd">
 <div class="kpi">
 <div class="box">
 <div class="k">PTO Bank</div>
 <div class="v"><?= e(minutes_to_hours_decimal($pto_bank)) ?></div>
 </div>
 <div class="box">
 <div class="k">PTO Used YTD</div>
 <div class="v"><?= e(minutes_to_hours_decimal($pto_used_ytd)) ?></div>
 </div>
 <div class="box">
 <div class="k">PTO Available</div>
 <div class="v"><?= e(minutes_to_hours_decimal($pto_available)) ?></div>
 </div>
 </div>
 <div style="margin-top:14px">
 <a class="btn" href="<?= e(url('/timecard')) ?>">Open Current Timecard</a>
 </div>
 </div>
</div>
