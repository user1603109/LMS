<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class AboutController
{
  public function system(Request $request): void { View::render('about/system.php', ['title' => 'About System - ' . APP_NAME]); }
  public function developers(Request $request): void { View::render('about/developers.php', ['title' => 'Developers - ' . APP_NAME]); }
}