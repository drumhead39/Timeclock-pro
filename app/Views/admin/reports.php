<?php
/** @var array $user */
/** @var array $reports */
/** @var string $q */
/** @var string $sort */
/** @var int $payPeriodId */
/** @var array $recentPeriods */
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Reports</div>
      <div class="badge">Search, sort, and download older PDF reports. Use “Timecards” to view/edit/resubmit past timecards.</div>
    </div>
    <div>
      <a class="btn secondary" href="<?= e(url('/admin')) ?>">Back</a>
    </div>
  </div>

  <div class="bd">
    <form method="get" action="<?= e(url('/admin/reports')) ?>" class="row" style="gap:12px; align-items:flex-end">
      <div style="min-width:220px; flex:1">
        <label>Search</label>
        <input class="input" name="q" value="<?= e($q) ?>" placeholder="Filename, emailed-to, or date (MM/DD/YYYY or YYYY-MM-DD)"/>
        <small class="mono">Example: <span class="mono">2025-12-01</span> or <span class="mono">TIMECARD_</span></small>
      </div>

      <div style="min-width:220px">
        <label>Sort</label>
        <select class="input" name="sort">
          <option value="period_desc" <?= $sort==='period_desc' ? 'selected' : '' ?>>Pay period (newest first)</option>
          <option value="period_asc" <?= $sort==='period_asc' ? 'selected' : '' ?>>Pay period (oldest first)</option>
          <option value="created_desc" <?= $sort==='created_desc' ? 'selected' : '' ?>>Created (newest first)</option>
          <option value="created_asc" <?= $sort==='created_asc' ? 'selected' : '' ?>>Created (oldest first)</option>
        </select>
      </div>

      <div style="min-width:260px">
        <label>Filter pay period (recent)</label>
        <select class="input" name="pp">
          <option value="0">All pay periods</option>
          <?php foreach ($recentPeriods as $p): ?>
            <option value="<?= e((string)$p['id']) ?>" <?= ((int)$payPeriodId === (int)$p['id']) ? 'selected' : '' ?>>
              <?= e(fmt_date($p['start_date'])) ?> → <?= e(fmt_date($p['end_date'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="mono">Tip: for older periods, leave this on “All” and search by date.</small>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end">
        <button class="btn ok" type="submit">Apply</button>
        <a class="btn secondary" href="<?= e(url('/admin/reports')) ?>">Reset</a>
      </div>
    </form>

    <div class="table-wrap" style="margin-top:12px">
      <table class="table">
        <thead>
          <tr>
            <th>Pay Period</th>
            <th>Created</th>
            <th>Filename</th>
            <th>Emailed To</th>
            <th>Status</th>
            <th style="text-align:right"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($reports)): ?>
            <tr><td colspan="6" class="mono">No reports found.</td></tr>
          <?php endif; ?>

          <?php foreach ($reports as $r): ?>
            <tr>
              <td class="mono"><?= e(fmt_date($r['start_date'])) ?> → <?= e(fmt_date($r['end_date'])) ?></td>
              <td class="mono"><?= e(fmt_datetime($r['created_at'])) ?></td>
              <td class="mono"><?= e($r['filename']) ?></td>
              <td class="mono"><?= e($r['emailed_to'] ?? '') ?></td>
              <td><?= e($r['email_status'] ?? '') ?></td>
              <td style="text-align:right; white-space:nowrap">
                <a class="btn secondary" href="<?= e(url('/admin/reports/download/' . (string)$r['id'])) ?>">Download</a>
                <a class="btn secondary" href="<?= e(url('/admin/timecards?pp=' . (string)$r['pay_period_id'])) ?>">Timecards</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="notice" style="margin-top:12px">
      <strong>Note:</strong> If you edit/resubmit a past timecard, you can use the Admin dashboard’s “Regenerate PDF + Email” button for that pay period to produce an updated report.
    </div>
  </div>
</div>
