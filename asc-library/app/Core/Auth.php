<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UserAdmin;

final class Auth
{
  private const SESSION_USER_KEY = 'auth_user';

  public static function check(): bool
  {
    return (bool)Session::get(self::SESSION_USER_KEY);
  }

  public static function user(): ?array
  {
    /** @var array|null $user */
    $user = Session::get(self::SESSION_USER_KEY);
    return $user;
  }

  public static function attempt(string $username, string $password): bool
  {
    $user = UserAdmin::findByUsername($username);
    if (!$user) {
      return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
      return false;
    }

    Session::regenerate();
    Session::set(self::SESSION_USER_KEY, [
      'id' => (int)$user['id'],
      'username' => $user['username'],
      'role' => $user['role'],
      'status' => $user['status']
    ]);
    return true;
  }

  public static function logout(): void
  {
    Session::forget(self::SESSION_USER_KEY);
    Session::regenerate();
  }

  public static function hasRole(string $role): bool
  {
    $user = self::user();
    return $user && $user['role'] === $role;
  }

  public static function hasAnyRole(array $roles): bool
  {
    $user = self::user();
    if (!$user) return false;
    return in_array($user['role'], $roles, true);
  }
}