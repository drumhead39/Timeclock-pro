<?php
use App\Core\Csrf;
/** @var array $state */
/** @var array $current */
$cfg = $current['config'];
$inc = (int)($cfg['time_increment_minutes'] ?? 15);
$mode = (string)($cfg['rounding_mode'] ?? 'nearest');
$days = $cfg['days_to_show'] ?? [1,2,3,4,5,6];
$dayNames = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
?>
<div class="card">
 <div class="hd">
 <div>
 <div style="font-weight:800; font-size:18px">Program Settings</div>
 <div class="badge">Preferences are versioned and can be applied immediately or next pay period</div>
 </div>
 </div>
 <div class="bd">
 <form method="post" action="<?= e(url('/admin/settings/save')) ?>">
 <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>

 <div class="grid">
 <div class="card">
 <div class="hd"><div style="font-weight:800">Time Rounding</div></div>
 <div class="bd">
 <div class="row">
 <div>
 <label>Increment minutes</label>
 <select class="input" name="time_increment_minutes">
 <?php foreach ([5,10,15,20,30] as $opt): ?>
 <option value="<?= e((string)$opt) ?>" <?= $inc===$opt ? 'selected' : '' ?>><?= e((string)$opt) ?></option>
 <?php endforeach; ?>
 </select>
 </div>
 <div>
 <label>Rounding mode</label>
 <select class="input" name="rounding_mode">
 <option value="nearest" <?= $mode==='nearest'?'selected':'' ?>>nearest</option>
 <option value="down" <?= $mode==='down'?'selected':'' ?>>down</option>
 <option value="up" <?= $mode==='up'?'selected':'' ?>>up</option>
 </select>
 </div>
 </div>

 <div style="margin-top:12px">
 <label>Days shown on timecard</label>
 <div class="row">
 <?php for ($i=1;$i<=7;$i++): ?>
 <label style="display:flex; gap:8px; align-items:center">
 <input type="checkbox" name="days_to_show[]" value="<?= e((string)$i) ?>" <?= in_array($i,$days,true)?'checked':'' ?>/>
 <?= e($dayNames[$i]) ?>
 </label>
 <?php endfor; ?>
 </div>
 <small class="mono">Choose the days that should appear on each timecard.</small>
 </div>
 </div>
 </div>

 <div class="card">
 <div class="hd"><div style="font-weight:800">Pay Period + Company</div></div>
 <div class="bd">
 <div class="row">
 <div>
 <label>Company name</label>
 <input class="input" name="company_name" value="<?= e((string)$state['company_name']) ?>"/>
 </div>
 <div>
 <label>Report email destination</label>
 <input class="input" type="email" name="company_email" value="<?= e((string)$state['company_email']) ?>"/>
 </div>
 </div>

 <div class="row" style="margin-top:12px">
 <div>
 <label>Pay period anchor date</label>
 <input class="input" type="date" name="pay_period_anchor_date" value="<?= e((string)$state['pay_period_anchor_date']) ?>"/>
 <small class="mono">Bi-weekly periods are calculated forward/backward from this date.</small>
 </div>
 <div>
 <label>Pay period length (days)</label>
 <input class="input" type="number" min="7" max="31" name="pay_period_length_days" value="<?= e((string)$state['pay_period_length_days']) ?>"/>
 <small class="mono">Default is 14 for bi-weekly.</small>
 </div>
 </div>
 </div>
 </div>
 </div>

 <hr class="sep"/>

 <div class="card">
 <div class="hd"><div style="font-weight:800">Apply Scope</div></div>
 <div class="bd">
 <div class="notice">
 <strong>Immediate</strong> updates the current (unlocked) pay period's rules. <strong>Next pay period</strong> leaves the current period unchanged.
 </div>

 <div style="margin-top:10px">
 <label style="display:flex; gap:10px; align-items:center">
 <input type="radio" name="apply_scope" value="immediate"/>
 Apply immediately to current unlocked pay period
 </label>
 <label style="display:flex; gap:10px; align-items:center; margin-top:8px">
 <input type="radio" name="apply_scope" value="next" checked/>
 Apply starting next pay period only
 </label>
 </div>

 <div style="margin-top:10px">
 <label style="display:flex; gap:10px; align-items:center">
 <input type="checkbox" name="reround_existing" value="1"/>
 Re-round existing IN/OUT times for any unlocked timecards in the current pay period (recommended when applying immediately)
 </label>
 </div>

 <div style="margin-top:12px">
 <button class="btn ok" type="submit">Save Settings</button>
 </div>
 </div>
 </div>

 <div class="notice" style="margin-top:12px">
 Settings are saved as a new version each time (history is preserved).
 </div>
 </form>
 </div>
</div>
