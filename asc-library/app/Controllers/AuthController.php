<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
  public function showLogin(Request $request): void
  {
    if (Auth::check()) {
      header('Location: /dashboard');
      exit;
    }
    View::render('auth/login.php', [
      'csrf' => Session::csrfToken(),
      'title' => 'Login - ' . APP_NAME,
    ]);
  }

  public function login(Request $request): void
  {
    $username = trim((string)$request->post('username', ''));
    $password = (string)$request->post('password', '');

    if ($username === '' || $password === '') {
      View::render('auth/login.php', [
        'error' => 'Please provide both username and password.',
        'csrf' => Session::csrfToken(),
        'title' => 'Login - ' . APP_NAME,
      ]);
      return;
    }

    if (!Auth::attempt($username, $password)) {
      View::render('auth/login.php', [
        'error' => 'Invalid credentials.',
        'csrf' => Session::csrfToken(),
        'title' => 'Login - ' . APP_NAME,
      ]);
      return;
    }

    $user = Auth::user();
    if ($user && $user['role'] === 'Admin') {
      header('Location: /administration/users');
    } elseif ($user && $user['role'] === 'Librarian') {
      header('Location: /cataloging/books');
    } else {
      header('Location: /dashboard');
    }
    exit;
  }

  public function logout(Request $request): void
  {
    Auth::logout();
    header('Location: /login');
    exit;
  }
}