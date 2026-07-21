<?php
declare(strict_types=1);

return [
  'app' => [
    'base_url' => '',            // e.g. https://example.com/timeclock-pro
    'timezone' => 'America/New_York',
    'debug' => false,            // Or create storage/DEBUG_ON to enable debugging
    // 'base_url' can be left empty; the app will infer the subfolder automatically.

  ],
  'db' => [
    'host' => 'localhost',
    'name' => 'timeclock',
    'user' => 'timeclock_user',
    'pass' => 'CHANGE_ME',
    'charset' => 'utf8mb4',
  ],
];
