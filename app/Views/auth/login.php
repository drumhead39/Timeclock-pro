<?php
use App\Core\Csrf;
?>
<div class="card">
  <div class="hd">
    <div>
      <div style="font-weight:800; font-size:18px">Login</div>
      <div class="badge">Standalone PHP Time Clock</div>
    </div>
  </div>

  <div class="bd">
    <form method="post" action="<?= e(url('/login')) ?>">
      <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"/>

      <div class="row">
        <div>
          <label>Email</label>
          <input class="input" type="email" name="email" required/>
        </div>
        <div>
          <label>Password</label>
          <input class="input" type="password" name="password" required/>
        </div>
      </div>

      <div style="margin-top:12px">
        <button class="btn" type="submit">Login</button>
      </div>

      <div class="notice" style="margin-top:12px">
        If you have not installed yet, run <span class="mono">/install</span>.
      </div>
    </form>
  </div>
</div>
