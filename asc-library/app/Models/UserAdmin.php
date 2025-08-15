<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

final class UserAdmin
{
  public static function findByUsername(string $username): ?array
  {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT * FROM user_admin WHERE username = :u AND status = "Active" LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }
}