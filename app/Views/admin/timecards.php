<?php
use App\Core\Csrf;
/** @var array $user */
/** @var array $payPeriod */
/** @var array $recentPeriods */
/** @var array $employeeStatus */
/** @var array $providerStatus */
/** @var array $accessibleProviderIds */
/** @var bool $isSuper */

$employeeStatus = $employeeStatus ?? ($status ?? []);
$providerStatus = $providerStatus ?? [];
$accessibleProviderIds = $accessibleProviderIds ?? [];
$isSuper = (bool)($isSuper ?? false);
$currentUserId = (int)($user['id'] ?? 0);
$ppId = (int)$payPeriod['id'];
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Time Cards</div>
      <div class="badge">Pay Period: <?= e(fmt_date($payPeriod['start_date'])) ?> through <?= e(fmt_date($payPeriod['end_date'])) ?></div>
      <?php if (!empty($payPeriod['locked_at'])): ?>
        <span class="badge danger">Pay Period Locked</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="bd">
    <form method="get" action="<?= e(url('/timecards')) ?>" class="period-picker">
      <label>Select Pay Period</label>
      <select name="pp" class="input" onchange="this.form.submit()">
        <?php foreach ($recentPeriods as $p): ?>
          <option value="<?= e((string)$p['id']) ?>" <?= ((int)$p['id'] === $ppId) ? 'selected' : '' ?>>
            <?= e(fmt_date($p['start_date']) . ' through ' . fmt_date($p['end_date'])) ?><?= !empty($p['locked_at']) ? ' (Locked)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if ($isSuper): ?>
      <div class="report-actions">
        <?php if (!empty($payPeriod['locked_at'])): ?>
          <form method="post" action="<?= e(url('/admin/payperiod/regenerate')) ?>" onsubmit="return confirm('Regenerate this pay period report? This will re-lock corrected/unlocked cards, overwrite the stored PDF, and email it again.');">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
            <input type="hidden" name="pay_period_id" value="<?= e((string)$ppId) ?>"/>
            <button class="btn ok" type="submit">Regenerate PDF + Email</button>
          </form>
          <div class="notice flex-notice">Use this after unlocking, editing, and re-locking a past employee or provider time card.</div>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/payperiod/lock')) ?>" onsubmit="return confirm('Lock all submitted/current time cards and generate the pay period PDF/email?');">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
            <input type="hidden" name="pay_period_id" value="<?= e((string)$ppId) ?>"/>
            <button class="btn danger" type="submit">Lock Pay Period + Generate PDF</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid timecards-grid">
  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:800; font-size:18px">Employees</div>
        <div class="badge">Hourly time cards</div>
      </div>
    </div>
    <div class="bd">
      <div class="table-wrap no-mobile-scroll">
        <table class="table staff-table mobile-card-table">
          <thead><tr><th>Employee</th><th>Submitted</th><th>Locked</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($employeeStatus as $s): ?>
            <?php
              $rowUserId = (int)$s['user_id'];
              $canEdit = $isSuper || ($rowUserId === $currentUserId);
              $editUrl = $isSuper
                ? url('/admin/timecards/' . (string)$rowUserId . '?pp=' . (string)$ppId)
                : url('/timecard?pp=' . (string)$ppId);
            ?>
            <tr>
              <td data-label="Employee"><?= e($s['full_name']) ?></td>
              <td data-label="Submitted"><?= $s['submitted_at'] ? '<span class="badge warn">Yes</span>' : '<span class="badge">No</span>' ?></td>
              <td data-label="Locked"><?= $s['locked_at'] ? '<span class="badge danger">Yes</span>' : '<span class="badge">No</span>' ?></td>
              <td data-label="Action" class="actions-cell">
                <?php if ($canEdit): ?>
                  <a class="btn secondary" href="<?= e($editUrl) ?>">View/Edit</a>
                <?php else: ?>
                  <span class="badge">View only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="hd">
      <div>
        <div style="font-weight:800; font-size:18px">Providers</div>
        <div class="badge">Production / quantity cards</div>
      </div>
    </div>
    <div class="bd">
      <div class="table-wrap no-mobile-scroll">
        <table class="table staff-table mobile-card-table">
          <thead><tr><th>Provider</th><th>Type</th><th>Submitted</th><th>Locked</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($providerStatus as $s): ?>
            <?php
              $providerId = (int)$s['provider_id'];
              $canEditProvider = $isSuper;
            ?>
            <tr>
              <td data-label="Provider"><?= e($s['full_name']) ?></td>
              <td data-label="Type" class="mono"><?= e($s['provider_type']) ?></td>
              <td data-label="Submitted"><?= $s['submitted_at'] ? '<span class="badge warn">Yes</span>' : '<span class="badge">No</span>' ?></td>
              <td data-label="Locked"><?= $s['locked_at'] ? '<span class="badge danger">Yes</span>' : '<span class="badge">No</span>' ?></td>
              <td data-label="Action" class="actions-cell">
                <?php if ($canEditProvider): ?>
                  <a class="btn secondary" href="<?= e(url('/provider-production/' . (string)$providerId . '?pp=' . (string)$ppId)) ?>">View/Edit</a>
                <?php else: ?>
                  <span class="badge">View only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
