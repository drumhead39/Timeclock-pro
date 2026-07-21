<?php
/** @var string $title */
/** @var string $viewFile */
use App\Core\Auth;

$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8"/>
 <meta name="viewport" content="width=device-width, initial-scale=1"/>
 <title><?= e($title ?? 'TimeClock') ?></title>
 <link rel="stylesheet" href="<?= e(url('/assets/css/app.css') . '?v=20260427-2') ?>"/>
 <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
 <link rel="icon" type="image/png" href="<?= e(url('/favicon-32x32.png')) ?>" sizes="32x32">
 <link rel="icon" type="image/png" href="<?= e(url('/favicon-16x16.png')) ?>" sizes="16x16">
 <link rel="apple-touch-icon" href="<?= e(url('/apple-touch-icon.png')) ?>">
 <link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
</head>
<body>
 <div class="container">
  <div class="topbar">
   <div class="brand">TimeClock</div>
   <div class="nav">
    <?php if ($user): ?>
      <a href="<?= e(url('/timecards')) ?>">Time Cards</a>
      <?php if ($user['role']==='super'): ?>
        <details class="nav-dropdown">
          <summary class="nav-dropbtn">Admin ▾</summary>
          <div class="nav-dropdown-menu">
            <a href="<?= e(url('/admin/employees')) ?>">Employees</a>
            <a href="<?= e(url('/admin/providers')) ?>">Providers</a>
            <a href="<?= e(url('/admin/appointment-types')) ?>">Appointment Types</a>
            <a href="<?= e(url('/admin/settings')) ?>">Settings</a>
          </div>
        </details>
      <?php endif; ?>
      <a href="<?= e(url('/logout')) ?>">Logout</a>
    <?php else: ?>
      <a href="<?= e(url('/login')) ?>">Login</a>
    <?php endif; ?>
   </div>
  </div>

  <?php if ($m = flash_get('error')): ?>
    <div class="card"><div class="bd"><div class="notice"><strong style="color:#ff6b6b">Error:</strong> <?= e($m) ?></div></div></div>
  <?php endif; ?>
  <?php if ($m = flash_get('ok')): ?>
    <div class="card"><div class="bd"><div class="notice"><strong style="color:#38d9a9">OK:</strong> <?= e($m) ?></div></div></div>
  <?php endif; ?>

  <?php include $viewFile; ?>
 </div>

 <?php if (debug_enabled()): ?>
 <script>
 (function(){
   try {
     var rid = <?= json_encode(request_id()) ?>;
     console.info("[TimeClock-Pro] Debug ON. Request ID:", rid);
     var csrf = <?= json_encode(\App\Core\Csrf::token()) ?>;
     function send(kind, payload){
       try {
         fetch(<?= json_encode(url('/debug/client-error')) ?>, {
           method: "POST",
           headers: {"Content-Type":"application/json","X-CSRF-Token": csrf},
           body: JSON.stringify({kind: kind, payload: payload, rid: rid, href: location.href})
         }).catch(function(){});
       } catch(e){}
     }
     window.addEventListener("error", function(ev){
       send("error", {message: ev.message, file: ev.filename, line: ev.lineno, col: ev.colno, stack: (ev.error && ev.error.stack) ? ev.error.stack : null});
     });
     window.addEventListener("unhandledrejection", function(ev){
       var r = ev.reason;
       send("unhandledrejection", {reason: (r && r.message) ? r.message : String(r), stack: (r && r.stack) ? r.stack : null});
     });
   } catch(e){}
 })();
 </script>
 <?php endif; ?>
</body>
</html>
