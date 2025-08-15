<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\DB;

final class HelpController
{
  public function contactUs(Request $request): void
  {
    View::render('help/contact_us.php', [
      'title' => 'Contact Us - ' . APP_NAME,
      'csrf' => Session::csrfToken(),
    ]);
  }

  public function sendReport(Request $request): void
  {
    $userId = 0; // optionally set from auth
    $message = trim((string)$request->post('message', ''));
    $email = trim((string)$request->post('email', ''));
    $phone = trim((string)$request->post('phone', ''));

    if ($message === '') {
      View::render('help/contact_us.php', [
        'title' => 'Contact Us - ' . APP_NAME,
        'csrf' => Session::csrfToken(),
        'error' => 'Message cannot be empty.'
      ]);
      return;
    }

    $pdo = DB::pdo();
    $stmt = $pdo->prepare('INSERT INTO help_contact (user_id, message, contact_email, contact_phone, date_sent) VALUES (:u,:m,:e,:p, NOW())');
    $stmt->execute([':u' => $userId, ':m' => $message, ':e' => $email, ':p' => $phone]);

    View::render('help/contact_us.php', [
      'title' => 'Contact Us - ' . APP_NAME,
      'csrf' => Session::csrfToken(),
      'success' => 'Thank you. Your report has been sent.'
    ]);
  }
}