<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Request;
use App\Core\View;

final class SearchController
{
  public function search(Request $request): void
  {
    $q = trim((string)$request->query('q', ''));
    $results = [];
    if ($q !== '') {
      $pdo = DB::pdo();
      $like = '%' . $q . '%';
      // Search across books titles and patrons names for demo
      $stmt1 = $pdo->prepare('SELECT id, title AS label, "Book" AS type FROM books WHERE title LIKE :q LIMIT 10');
      $stmt1->execute([':q' => $like]);
      $stmt2 = $pdo->prepare('SELECT id, name AS label, "Patron" AS type FROM patrons WHERE name LIKE :q LIMIT 10');
      $stmt2->execute([':q' => $like]);
      $results = array_merge($stmt1->fetchAll(), $stmt2->fetchAll());
    }

    View::render('search/results.php', [
      'title' => 'Search - ' . APP_NAME,
      'query' => $q,
      'results' => $results,
    ]);
  }
}