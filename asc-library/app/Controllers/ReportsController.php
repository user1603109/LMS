<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class ReportsController
{
  public function accessionList(Request $request): void { View::render('reports/accession_list.php', ['title' => 'Accession List - ' . APP_NAME]); }
  public function patronMasterlist(Request $request): void { View::render('reports/patron_masterlist.php', ['title' => 'Patron Masterlist - ' . APP_NAME]); }
}