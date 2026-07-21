<?php
/** @var array $employees */
?>
<div class="card">
 <div class="hd">
 <div>
 <div style="font-weight:800; font-size:18px">Employees</div>
 <div class="badge">Create, edit, reset passwords, assign roles, hard delete</div>
 </div>
 <div>
 <a class="btn ok" href="<?= e(url('/admin/employees/new')) ?>">+ Add Employee</a>
 </div>
 </div>
 <div class="bd">
 <table class="table">
 <thead>
 <tr>
 <th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>PTO Bank</th><th></th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($employees as $e): ?>
 <tr>
 <td><?= e($e['full_name']) ?></td>
 <td class="mono"><?= e($e['email']) ?></td>
 <td><?= e($e['role']) ?></td>
 <td><?= (int)$e['active']===1 ? '<span class="badge ok">Yes</span>' : '<span class="badge danger">No</span>' ?></td>
 <td class="mono"><?= e(minutes_to_hours_decimal((int)$e['pto_bank_minutes'])) ?></td>
 <td style="text-align:right"><a class="btn secondary" href="<?= e(url('/admin/employees/' . (string)$e['id'])) ?>">Edit</a></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
</div>
