<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "This command can only be run from the command line.\n");
  exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\User;

function prompt_value(string $label): string {
  if (function_exists('readline')) {
    return trim((string)readline($label));
  }
  fwrite(STDOUT, $label);
  return trim((string)fgets(STDIN));
}

$name = trim((string)($argv[1] ?? ''));
$email = strtolower(trim((string)($argv[2] ?? '')));
if ($name === '') $name = prompt_value('Administrator name: ');
if ($email === '') $email = strtolower(prompt_value('Administrator email: '));

$password = (string)(getenv('TIMECLOCK_ADMIN_PASSWORD') ?: '');
if ($password === '') {
  $password = prompt_value('Administrator password (input will be visible): ');
}

if ($name === '' || strlen($name) > 190) {
  fwrite(STDERR, "Enter an administrator name between 1 and 190 characters.\n");
  exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
  fwrite(STDERR, "Enter a valid administrator email address.\n");
  exit(1);
}
if (strlen($password) < 12) {
  fwrite(STDERR, "The administrator password must contain at least 12 characters.\n");
  exit(1);
}
if (User::findByEmail($email)) {
  fwrite(STDERR, "A user with that email address already exists.\n");
  exit(1);
}

User::create([
  'full_name' => $name,
  'email' => $email,
  'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  'role' => 'super',
  'active' => 1,
  'weight_loss_consultant' => 0,
  'nursing_encounters_enabled' => 0,
  'pto_bank_minutes' => 0,
]);

fwrite(STDOUT, "Administrator created successfully.\n");
