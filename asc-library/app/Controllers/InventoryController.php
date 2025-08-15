<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class InventoryController
{
  public function management(Request $request): void { View::render('inventory/management.php', ['title' => 'Inventory Management - ' . APP_NAME]); }
  public function acquisition(Request $request): void { View::render('inventory/acquisition.php', ['title' => 'Acquisition - ' . APP_NAME]); }
}