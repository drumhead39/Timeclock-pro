<?php
use App\Core\Csrf;
/** @var array|null $emp */
$isNew = !$emp;
?>
<div class="card">
 <div class="hd">
 <div>
 <div style="font-weight:800; font-size:18px"><?= $isNew ? 'Add Employee' : 'Edit Employee' ?></div>
 <div class="badge"><?= $isNew ? 'Create a new login' : 'Update employee details' ?></div>
 </div>
 <div>
 <a class="btn secondary" href="<?= e(url('/admin/employees')) ?>">Back</a>
 </div>
 </div>
 <div class="bd">
 <form method="post" action="<?= e(url('/admin/employees/save')) ?>">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
 <input type="hidden" name="id" value="<?= e($emp['id'] ?? '') ?>"/>

 <div class="row">
 <div>
 <label>Full Name</label>
 <input class="input" name="full_name" value="<?= e($emp['full_name'] ?? '') ?>" required/>
 </div>
 <div>
 <label>Email (Login)</label>
 <input class="input" type="email" name="email" value="<?= e($emp['email'] ?? '') ?>" required/>
 </div>
 </div>

 <div class="row" style="margin-top:12px">
 <div>
 <label>Role</label>
 <select class="input" name="role">
 <option value="employee" <?= ($emp['role'] ?? '')==='employee' ? 'selected' : '' ?>>employee</option>
 <option value="super" <?= ($emp['role'] ?? '')==='super' ? 'selected' : '' ?>>super</option>
 </select>
 </div>
 <div>
 <label>PTO Bank Hours (total banked/available before YTD usage)</label>
 <input class="input" type="number" step="0.25" min="0" name="pto_bank_hours" value="<?= e(isset($emp['pto_bank_minutes']) ? round(((int)$emp['pto_bank_minutes'])/60, 2) : '0') ?>"/>
 </div>
 </div>

 <div style="margin-top:12px">
 <label><input type="checkbox" name="active" <?= (!isset($emp['active']) || (int)$emp['active']===1) ? 'checked' : '' ?>/> Active</label>
  <label><input type="checkbox" name="weight_loss_consultant" <?= (!empty($emp) && !empty($emp['weight_loss_consultant'])) ? 'checked' : '' ?>/> Weight Loss Consultant</label>
  <label><input type="checkbox" name="nursing_encounters_enabled" <?= (!empty($emp) && !empty($emp['nursing_encounters_enabled'])) ? 'checked' : '' ?>/> Nursing Encounters</label>
 </div>

 <?php if ($isNew): ?>
 <div style="margin-top:12px">
 <label>Initial Password</label>
 <input class="input" type="password" name="password" minlength="8" required/>
 </div>
 <?php endif; ?>

 <div style="margin-top:14px">
 <button class="btn ok" type="submit">Save</button>
 </div>
 </form>

 <?php if (!$isNew): ?>
 <hr class="sep"/>

 <div class="grid">
 <div class="card">
 <div class="hd"><div style="font-weight:800">Reset Password (2-step)</div></div>
 <div class="bd">
 <form method="post" action="<?= e(url('/admin/employees/reset-password')) ?>" onsubmit="return confirm('Reset password for this user?');">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
 <input type="hidden" name="emp_id" value="<?= e((string)$emp['id']) ?>"/>
 <label>Admin password</label>
 <input class="input" type="password" name="admin_password" required/>
 <div style="height:10px"></div>
 <label>Type employee email to confirm</label>
 <input class="input" name="confirm_email" placeholder="<?= e($emp['email']) ?>" required/>
 <div style="height:10px"></div>
 <label>New password</label>
 <input class="input" type="password" name="new_password" minlength="8" required/>
 <div style="margin-top:12px">
 <button class="btn danger" type="submit">Change Password</button>
 </div>
 </form>
 </div>
 </div>

 <div class="card">
 <div class="hd"><div style="font-weight:800">Hard Delete Employee (2-step)</div></div>
 <div class="bd">
 <div class="notice"><strong>Warning:</strong> This permanently deletes the user and related timecard data (cannot be undone).</div>
 <form method="post" action="<?= e(url('/admin/employees/delete')) ?>" onsubmit="return confirm('Hard delete this employee and related records. Continue?');" style="margin-top:12px">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>
 <input type="hidden" name="emp_id" value="<?= e((string)$emp['id']) ?>"/>
 <label>Admin password</label>
 <input class="input" type="password" name="admin_password" required/>
 <div style="height:10px"></div>
 <label>Type DELETE to confirm</label>
 <input class="input" name="confirm_text" required/>
 <div style="margin-top:12px">
 <button class="btn danger" type="submit">Delete Employee</button>
 </div>
 </form>
 </div>
 </div>
 </div>
 <?php endif; ?>
 </div>
</div>
