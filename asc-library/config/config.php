<?php
// Basic configuration for ASC Library Management System

// Database config (use env or defaults for local dev)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'asc_library');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// App config
define('APP_NAME', 'ASC Library Management System');

define('BASE_URL', (function() {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
  $base = rtrim(str_replace('index.php', '', $scriptName), '/');
  return $scheme . '://' . $host . $base;
})());