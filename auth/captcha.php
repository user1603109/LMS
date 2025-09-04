<?php
session_start();
require_once '../includes/auth.php';

// Generate CAPTCHA
$captcha = generateCaptcha();

// Create image
$width = 120;
$height = 40;
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 51, 160);
$line_color = imagecolorallocate($image, 255, 215, 0);

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some lines for security
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add text
$font_size = 5;
$x = ($width - strlen($captcha) * imagefontwidth($font_size)) / 2;
$y = ($height - imagefontheight($font_size)) / 2;
imagestring($image, $font_size, $x, $y, $captcha, $text_color);

// Output image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>