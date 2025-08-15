<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class CirculationController
{
  public function borrowReturn(Request $request): void { View::render('circulation/borrow_return.php', ['title' => 'Borrow & Return - ' . APP_NAME]); }
  public function reservations(Request $request): void { View::render('circulation/reservations.php', ['title' => 'Resource Reservation - ' . APP_NAME]); }
  public function finesPayment(Request $request): void { View::render('circulation/fines_payment.php', ['title' => 'Fines & Payment - ' . APP_NAME]); }
}