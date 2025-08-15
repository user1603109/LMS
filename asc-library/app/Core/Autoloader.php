<?php
declare(strict_types=1);

namespace App\Core;

final class Autoloader
{
  public static function register(): void
  {
    spl_autoload_register(function (string $class): void {
      $prefix = 'App\\';
      $baseDir = dirname(__DIR__, 1) . '/';

      $len = strlen($prefix);
      if (strncmp($prefix, $class, $len) !== 0) {
        return;
      }

      $relativeClass = substr($class, $len);
      $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

      if (is_file($file)) {
        require $file;
      }
    });
  }
}