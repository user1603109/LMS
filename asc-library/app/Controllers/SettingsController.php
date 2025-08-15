<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class SettingsController
{
  public function exportBackups(Request $request): void { View::render('settings/export_backups.php', ['title' => 'Export & Backups - ' . APP_NAME]); }
  public function auditLogs(Request $request): void { View::render('settings/audit_logs.php', ['title' => 'Audit Logs - ' . APP_NAME]); }
  public function userSettings(Request $request): void { View::render('settings/user_settings.php', ['title' => 'User Settings - ' . APP_NAME]); }
}