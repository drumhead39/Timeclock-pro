<?php
use App\Core\Csrf;
/** @var array|null $prov */
$isNew = !$prov;
?>
<?php
$ptoBankMin = (int)($prov['pto_bank_minutes'] ?? 0);
$ptoBankHours = $ptoBankMin ? number_format($ptoBankMin/60, 2, '.', '') : '';
?>

<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px"><?= $isNew ? 'Add Provider' : 'Edit Provider' ?></div>
      <div class="badge"><?= $isNew ? 'Create a provider record (no login)' : 'Update provider details' ?></div>
    </div>
    <div>
      <a class="btn secondary" href="<?= e(url('/admin/providers')) ?>">Back</a>
    </div>
  </div>

  <div class="bd">
    <form method="post" action="<?= e(url('/admin/providers/save')) ?>">
      <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
      <input type="hidden" name="id" value="<?= e((string)($prov['id'] ?? 0)) ?>"/>

      <div class="row">
        <div>
          <label>Full Name</label>
          <input class="input" name="full_name" value="<?= e((string)($prov['full_name'] ?? '')) ?>" required/>
        </div>
        <div>
          <label>Provider Type</label>
          <select class="input" name="provider_type">
            <option value="chiro" <?= (($prov['provider_type'] ?? '')==='chiro') ? 'selected' : '' ?>>chiro</option>
            <option value="massage" <?= (($prov['provider_type'] ?? '')==='massage') ? 'selected' : '' ?>>massage</option>
          </select>
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <div>
          <label>PTO Bank (hours)</label>
          <input class="input" type="number" step="0.25" min="0" name="pto_bank_hours" value="<?= e((string)$ptoBankHours) ?>"/>
          <small class="mono">Leave blank (or 0) if this provider does not accrue PTO.</small>
        </div>
      </div>

      <div style="margin-top:12px">
        <label><input type="checkbox" name="active" <?= (!isset($prov['active']) || (int)$prov['active']===1) ? 'checked' : '' ?>/> Active</label>
      </div>

      <div style="margin-top:14px">
        <button class="btn ok" type="submit">Save</button>
      </div>
    </form>

    <?php if (!$isNew): ?>
      <hr class="sep"/>
      <div style="display:flex; gap:10px; flex-wrap:wrap">
        <a class="btn secondary" href="<?= e(url('/admin/providers/' . (string)$prov['id'] . '/rates')) ?>">Manage Rates</a>
        <a class="btn secondary" href="<?= e(url('/admin/provider-production')) ?>">Enter Production</a>
      </div>
    <?php endif; ?>
  </div>
</div>
