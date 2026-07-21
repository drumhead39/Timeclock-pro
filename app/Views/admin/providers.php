<?php
/** @var array $providers */
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Providers</div>
      <div class="badge">Admin-only: manage providers, appointment types, and rates</div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
      <a class="btn secondary" href="<?= e(url('/admin/provider-production')) ?>">Provider Production</a>
      <a class="btn secondary" href="<?= e(url('/admin/appointment-types')) ?>">Appointment Types</a>
      <a class="btn ok" href="<?= e(url('/admin/providers/new')) ?>">+ Add Provider</a>
    </div>
  </div>

  <div class="bd">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Active</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($providers as $p): ?>
        <tr>
          <td><?= e($p['full_name']) ?></td>
          <td class="mono"><?= e($p['provider_type']) ?></td>
          <td><?= (int)$p['active']===1 ? '<span class="badge ok">Yes</span>' : '<span class="badge danger">No</span>' ?></td>
          <td style="text-align:right">
            <a class="btn secondary" href="<?= e(url('/admin/providers/' . (string)$p['id'])) ?>">Edit</a>
            <a class="btn secondary" href="<?= e(url('/admin/providers/' . (string)$p['id'] . '/rates')) ?>">Rates</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="notice" style="margin-top:12px">
      <strong>Note:</strong> Providers do not log in. Super users enter appointment counts per pay period and the report will include provider totals.
    </div>
  </div>
</div>
