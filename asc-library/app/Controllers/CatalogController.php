<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

final class CatalogController
{
  public function books(Request $request): void { View::render('cataloging/books.php', ['title' => 'Books - ' . APP_NAME]); }
  public function audioVisual(Request $request): void { View::render('cataloging/audio_visual.php', ['title' => 'Audio-Visual Materials - ' . APP_NAME]); }
  public function academicCoursework(Request $request): void { View::render('cataloging/academic_coursework.php', ['title' => 'Academic Coursework - ' . APP_NAME]); }
  public function electronicResources(Request $request): void { View::render('cataloging/electronic_resources.php', ['title' => 'Electronic Resources - ' . APP_NAME]); }
  public function audioRecords(Request $request): void { View::render('cataloging/audio_records.php', ['title' => 'Audio Records - ' . APP_NAME]); }
  public function videoRecords(Request $request): void { View::render('cataloging/video_records.php', ['title' => 'Video Records - ' . APP_NAME]); }
  public function serials(Request $request): void { View::render('cataloging/serials.php', ['title' => 'Serials - ' . APP_NAME]); }
  public function patron(Request $request): void { View::render('cataloging/patron.php', ['title' => 'Patron - ' . APP_NAME]); }
}