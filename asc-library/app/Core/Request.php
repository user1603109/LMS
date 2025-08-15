<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
  public function input(string $key, $default = null)
  {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
  }

  public function all(): array
  {
    return array_merge($_GET, $_POST);
  }

  public function query(string $key, $default = null)
  {
    return $_GET[$key] ?? $default;
  }

  public function post(string $key, $default = null)
  {
    return $_POST[$key] ?? $default;
  }
}