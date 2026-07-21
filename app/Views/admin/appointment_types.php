<?php
use App\Core\Csrf;
/** @var array $types_chiro */
/** @var array $types_massage */
/** @var array|null $editType */
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Appointment Types</div>
      <div class="badge">Add and manage appointment categories (future-proof)</div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
      <a class="btn secondary" href="<?= e(url('/admin/providers')) ?>">Providers</a>
      <a class="btn secondary" href="<?= e(url('/admin/provider-production')) ?>">Provider Production</a>
    </div>
  </div>

  <div class="bd">
    <div class="grid" style="grid-template-columns: 1fr;">
      <div class="card">
        <div class="hd"><div style="font-weight:800">Chiropractor</div></div>
        <div class="bd">
          <table class="table">
            <thead><tr><th>Name</th><th>Sort</th><th>Active</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($types_chiro as $t): ?>
                <tr>
                  <td><?= e($t['name']) ?></td>
                  <td class="mono"><?= e((string)$t['sort_order']) ?></td>
                  <td><?= (int)$t['active']===1 ? '<span class="badge ok">Yes</span>' : '<span class="badge danger">No</span>' ?></td>
                  <td style="text-align:right">
<div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
  <a class="btn secondary" href="<?= e(url('/admin/appointment-types?edit_id=' . (string)$t['id'])) ?>">Edit</a>

  <form method="post" action="<?= e(url('/admin/appointment-types/toggle')) ?>" style="display:inline" onsubmit="return confirm('Update active status?');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="id" value="<?= e((string)$t['id']) ?>"/>
    <input type="hidden" name="active" value="<?= (int)$t['active']===1 ? '0' : '1' ?>"/>
    <button class="btn secondary" type="submit"><?= (int)$t['active']===1 ? 'Deactivate' : 'Activate' ?></button>
  </form>

  <form method="post" action="<?= e(url('/admin/appointment-types/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this type? Only allowed if it is not used in any rates or production.');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="id" value="<?= e((string)$t['id']) ?>"/>
    <button class="btn danger" type="submit">Delete</button>
  </form>
</div>
</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="hd"><div style="font-weight:800">Massage</div></div>
        <div class="bd">
          <table class="table">
            <thead><tr><th>Name</th><th>Sort</th><th>Active</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($types_massage as $t): ?>
                <tr>
                  <td><?= e($t['name']) ?></td>
                  <td class="mono"><?= e((string)$t['sort_order']) ?></td>
                  <td><?= (int)$t['active']===1 ? '<span class="badge ok">Yes</span>' : '<span class="badge danger">No</span>' ?></td>
                  <td style="text-align:right">
<div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
  <a class="btn secondary" href="<?= e(url('/admin/appointment-types?edit_id=' . (string)$t['id'])) ?>">Edit</a>

  <form method="post" action="<?= e(url('/admin/appointment-types/toggle')) ?>" style="display:inline" onsubmit="return confirm('Update active status?');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="id" value="<?= e((string)$t['id']) ?>"/>
    <input type="hidden" name="active" value="<?= (int)$t['active']===1 ? '0' : '1' ?>"/>
    <button class="btn secondary" type="submit"><?= (int)$t['active']===1 ? 'Deactivate' : 'Activate' ?></button>
  </form>

  <form method="post" action="<?= e(url('/admin/appointment-types/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this type? Only allowed if it is not used in any rates or production.');">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
    <input type="hidden" name="id" value="<?= e((string)$t['id']) ?>"/>
    <button class="btn danger" type="submit">Delete</button>
  </form>
</div>
</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <hr class="sep"/>

    <div class="card">
      <div class="hd"><div style="font-weight:800">Add / Update Type</div></div>
      <div class="bd">
        <form method="post" action="<?= e(url('/admin/appointment-types/save')) ?>">
          <?php if ($editType): ?>
            <div class="notice" style="margin-bottom:12px"><strong>Editing:</strong> <?= e($editType['name']) ?> • <a href="<?= e(url('/admin/appointment-types')) ?>">Cancel</a></div>
          <?php endif; ?>
          <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
          <input type="hidden" name="id" value="<?= e((string)($editType['id'] ?? 0)) ?>"/>
          <div class="row">
            <div>
              <label>Provider Type</label>
              <select class="input" name="provider_type">
                <option value="chiro" <?= (($editType['provider_type'] ?? '')==='chiro') ? 'selected' : '' ?>>chiro</option>
                <option value="massage" <?= (($editType['provider_type'] ?? '')==='massage') ? 'selected' : '' ?>>massage</option>
              </select>
            </div>
            <div>
              <label>Name</label>
              <input class="input" name="name" value="<?= e((string)($editType['name'] ?? '')) ?>" required/>
            </div>
          </div>
          <div class="row" style="margin-top:12px">
            <div>
              <label>Sort Order</label>
              <input class="input" type="number" name="sort_order" value="<?= e((string)($editType['sort_order'] ?? 0)) ?>"/>
            </div>
            <div>
              <label><input type="checkbox" name="active" <?= (!isset($editType['active']) || (int)$editType['active']===1) ? 'checked' : '' ?>/> Active</label>
            </div>
          </div>
          <div style="margin-top:12px">
            <button class="btn ok" type="submit">Save Type</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
