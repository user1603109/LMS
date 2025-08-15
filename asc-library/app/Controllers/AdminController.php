<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class AdminController
{
  public function staffManagement(Request $request): void { View::render('administration/staff.php', ['title' => 'Staff Management - ' . APP_NAME]); }
  public function rolesPermissions(Request $request): void { View::render('administration/roles_permissions.php', ['title' => 'Roles & Permissions - ' . APP_NAME]); }
  public function userAdmin(Request $request): void { View::render('administration/user_admin.php', ['title' => 'User Admin - ' . APP_NAME]); }
}