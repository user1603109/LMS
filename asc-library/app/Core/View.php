<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
  public static function render(string $view, array $data = []): void
  {
    extract($data);
    $viewFile = BASE_PATH . '/resources/views/' . ltrim($view, '/');
    if (!is_file($viewFile)) {
      http_response_code(500);
      echo 'View not found: ' . htmlspecialchars($view);
      return;
    }
    // Expose to layout
    $view = $viewFile;
    include BASE_PATH . '/resources/views/layouts/main.php';
  }

  public static function content(string $viewPath, array $data = []): void
  {
    extract($data);
    if (str_starts_with($viewPath, BASE_PATH)) {
      include $viewPath;
    } else {
      $resolved = BASE_PATH . '/resources/views/' . ltrim($viewPath, '/');
      include $resolved;
    }
  }
}