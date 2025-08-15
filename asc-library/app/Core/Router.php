<?php
declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
  private array $routes = [
    'GET' => [],
    'POST' => [],
  ];

  public function get(string $path, array $handler, array $options = []): void
  {
    $this->routes['GET'][$this->normalize($path)] = ['handler' => $handler, 'options' => $options];
  }

  public function post(string $path, array $handler, array $options = []): void
  {
    $this->routes['POST'][$this->normalize($path)] = ['handler' => $handler, 'options' => $options];
  }

  public function dispatch(): void
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = $this->normalize($uri);

    $route = $this->routes[$method][$path] ?? null;

    if (!$route) {
      http_response_code(404);
      echo '404 Not Found';
      return;
    }

    $options = $route['options'] ?? [];

    if (($options['auth'] ?? false) && !Auth::check()) {
      $this->redirect('/login');
      return;
    }

    if (!empty($options['roles'])) {
      $allowed = (array)$options['roles'];
      if (!Auth::hasAnyRole($allowed)) {
        http_response_code(403);
        echo '403 Forbidden';
        return;
      }
    }

    if (($options['csrf'] ?? false) && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $token = $_POST['_csrf'] ?? '';
      if (!Session::validateCsrfToken($token)) {
        http_response_code(419);
        echo 'Invalid CSRF token';
        return;
      }
    }

    [$class, $methodName] = $route['handler'];
    $controller = new $class();
    call_user_func([$controller, $methodName], new Request());
  }

  private function normalize(string $path): string
  {
    if ($path === '') {
      return '/';
    }
    $path = '/' . trim($path, '/');
    if ($path === '//') {
      return '/';
    }
    return $path;
  }

  private function redirect(string $to): void
  {
    header('Location: ' . $to);
    exit;
  }
}