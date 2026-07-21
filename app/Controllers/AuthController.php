<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Core\Csrf;
use App\Services\LoginThrottle;

final class AuthController {
  public static function showLogin(): void {
    if (Auth::check()) redirect(Auth::homePath());
    View::render('auth/login', [
      'title' => 'Login',
    ]);
  }

  public static function login(): void {
    Csrf::check($_POST['_csrf'] ?? null);

    if (LoginThrottle::tooManyAttempts()) {
      \flash_set('error', 'Too many unsuccessful login attempts. Please wait 15 minutes and try again.');
      redirect('/login');
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    if (strlen($email) > 190 || strlen($pass) > 4096) {
      LoginThrottle::recordFailure();
      \flash_set('error', 'Invalid login.');
      redirect('/login');
    }
    if (Auth::login($email, $pass)) {
      LoginThrottle::clear();
      redirect(Auth::homePath());
    }
    LoginThrottle::recordFailure();
    usleep(250000);
    \flash_set('error', 'Invalid login.');
    redirect('/login');
  }

  public static function logout(): void {
    Csrf::check($_POST['_csrf'] ?? null);
    Auth::logout();
    redirect('/login');
  }

  public static function home(): void {
    Auth::requireLogin();
    redirect(Auth::homePath());
  }
}
