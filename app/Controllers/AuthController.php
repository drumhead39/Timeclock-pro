<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Core\Csrf;

final class AuthController {
  public static function showLogin(): void {
    if (Auth::check()) redirect('/timecards');
    View::render('auth/login', [
      'title' => 'Login',
    ]);
  }

  public static function login(): void {
    Csrf::check($_POST['_csrf'] ?? null);
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    if (Auth::login($email, $pass)) {
      redirect('/timecards');
    }
    \flash_set('error', 'Invalid login.');
    redirect('/login');
  }

  public static function logout(): void {
    Auth::logout();
    redirect('/login');
  }
}
