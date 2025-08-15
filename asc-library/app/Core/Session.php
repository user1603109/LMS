<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
  public static function get(string $key, $default = null)
  {
    return $_SESSION[$key] ?? $default;
  }

  public static function set(string $key, $value): void
  {
    $_SESSION[$key] = $value;
  }

  public static function forget(string $key): void
  {
    unset($_SESSION[$key]);
  }

  public static function regenerate(): void
  {
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  public static function csrfToken(): string
  {
    $token = self::get('_csrf');
    if (!$token) {
      $token = bin2hex(random_bytes(32));
      self::set('_csrf', $token);
    }
    return $token;
  }

  public static function validateCsrfToken(string $token): bool
  {
    $sessionToken = self::get('_csrf', '');
    return hash_equals((string)$sessionToken, (string)$token);
  }
}