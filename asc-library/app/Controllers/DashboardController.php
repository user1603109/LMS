<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;

final class DashboardController
{
  public function index(Request $request): void
  {
    $user = Auth::user();
    View::render('dashboard/index.php', [
      'title' => 'Dashboard - ' . APP_NAME,
      'user' => $user,
    ]);
  }
}