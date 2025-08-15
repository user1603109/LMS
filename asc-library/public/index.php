<?php
declare(strict_types=1);

// Front controller for ASC Library Management System

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

define('PUBLIC_PATH', __DIR__);

// Start a secure session early
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
  'lifetime' => 0,
  'path' => $cookieParams['path'] ?? '/',
  'domain' => $cookieParams['domain'] ?? '',
  'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Bootstrap and autoload
require_once APP_PATH . '/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Router;
use App\Core\Auth;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SearchController;
use App\Controllers\CatalogController;
use App\Controllers\CirculationController;
use App\Controllers\InventoryController;
use App\Controllers\ReportsController;
use App\Controllers\AdminController;
use App\Controllers\SettingsController;
use App\Controllers\AboutController;
use App\Controllers\HelpController;

require_once BASE_PATH . '/config/config.php';

$router = new Router();

// Public routes
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], ['csrf' => true]);
$router->get('/logout', [AuthController::class, 'logout'], ['auth' => true]);

// Authenticated routes
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth' => true]);
$router->get('/search', [SearchController::class, 'search'], ['auth' => true]);

// Cataloging
$router->get('/cataloging/books', [CatalogController::class, 'books'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/audio-visual', [CatalogController::class, 'audioVisual'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/academic-coursework', [CatalogController::class, 'academicCoursework'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/electronic-resources', [CatalogController::class, 'electronicResources'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/audio-records', [CatalogController::class, 'audioRecords'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/video-records', [CatalogController::class, 'videoRecords'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/serials', [CatalogController::class, 'serials'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/cataloging/patron', [CatalogController::class, 'patron'], ['auth' => true, 'roles' => ['Admin','Librarian']]);

// Circulation
$router->get('/circulation/borrow-return', [CirculationController::class, 'borrowReturn'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/circulation/reservations', [CirculationController::class, 'reservations'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/circulation/fines-payment', [CirculationController::class, 'finesPayment'], ['auth' => true, 'roles' => ['Admin','Librarian']]);

// Inventory
$router->get('/inventory/management', [InventoryController::class, 'management'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/inventory/acquisition', [InventoryController::class, 'acquisition'], ['auth' => true, 'roles' => ['Admin','Librarian']]);

// Reports
$router->get('/reports/accession-list', [ReportsController::class, 'accessionList'], ['auth' => true, 'roles' => ['Admin','Librarian']]);
$router->get('/reports/patron-masterlist', [ReportsController::class, 'patronMasterlist'], ['auth' => true, 'roles' => ['Admin','Librarian']]);

// Administration
$router->get('/administration/staff', [AdminController::class, 'staffManagement'], ['auth' => true, 'roles' => ['Admin']]);
$router->get('/administration/roles', [AdminController::class, 'rolesPermissions'], ['auth' => true, 'roles' => ['Admin']]);
$router->get('/administration/users', [AdminController::class, 'userAdmin'], ['auth' => true, 'roles' => ['Admin']]);

// Settings
$router->get('/settings/export-backups', [SettingsController::class, 'exportBackups'], ['auth' => true, 'roles' => ['Admin']]);
$router->get('/settings/audit-logs', [SettingsController::class, 'auditLogs'], ['auth' => true, 'roles' => ['Admin']]);
$router->get('/settings/user-settings', [SettingsController::class, 'userSettings'], ['auth' => true]);

// About
$router->get('/about/system', [AboutController::class, 'system'], ['auth' => true]);
$router->get('/about/developers', [AboutController::class, 'developers'], ['auth' => true]);

// Help
$router->get('/help/contact-us', [HelpController::class, 'contactUs'], ['auth' => true]);
$router->post('/help/send-report', [HelpController::class, 'sendReport'], ['auth' => true, 'csrf' => true]);

$router->dispatch();