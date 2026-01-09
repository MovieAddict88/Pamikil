<?php
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$dir = __DIR__ . '/assets/images/';

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    $bgColor = imagecolorallocate($image, 37, 99, 235);
    imagefill($image, 0, 0, $bgColor);
    
    $white = imagecolorallocate($image, 255, 255, 255);
    
    $fontSize = $size / 8;
    $text = "DH";
    
    $bbox = imagettfbbox($fontSize, 0, __DIR__ . '/assets/fonts/arial.ttf', $text);
    if ($bbox === false) {
        $x = $size / 3;
        $y = $size / 2;
        imagestring($image, 5, $x, $y, $text, $white);
    } else {
        $textWidth = abs($bbox[4] - $bbox[0]);
        $textHeight = abs($bbox[5] - $bbox[1]);
        $x = ($size - $textWidth) / 2;
        $y = ($size + $textHeight) / 2;
        imagettftext($image, $fontSize, 0, $x, $y, $white, __DIR__ . '/assets/fonts/arial.ttf', $text);
    }
    
    $carIconY = $size * 0.7;
    $carWidth = $size * 0.5;
    $carHeight = $size * 0.2;
    $carX = ($size - $carWidth) / 2;
    
    imagefilledrectangle($image, $carX, $carIconY, $carX + $carWidth, $carIconY + $carHeight, $white);
    
    imagepng($image, $dir . "icon-{$size}.png");
    imagedestroy($image);
    
    echo "Generated icon-{$size}.png\n";
}

$screenshot = imagecreatetruecolor(540, 720);
$bgColor = imagecolorallocate($screenshot, 241, 245, 249);
imagefill($screenshot, 0, 0, $bgColor);

$headerColor = imagecolorallocate($screenshot, 37, 99, 235);
imagefilledrectangle($screenshot, 0, 0, 540, 80, $headerColor);

$white = imagecolorallocate($screenshot, 255, 255, 255);
$text = "DriveHub";
imagestring($screenshot, 5, 20, 30, $text, $white);

$cardColor = imagecolorallocate($screenshot, 255, 255, 255);
imagefilledrectangle($screenshot, 20, 100, 520, 300, $cardColor);

$textColor = imagecolorallocate($screenshot, 30, 41, 59);
imagestring($screenshot, 4, 30, 120, "Premium Car Management", $textColor);
imagestring($screenshot, 3, 30, 160, "Browse our extensive collection", $textColor);
imagestring($screenshot, 3, 30, 190, "of quality vehicles", $textColor);

imagepng($screenshot, $dir . "screenshot1.png");
imagedestroy($screenshot);

echo "Generated screenshot1.png\n";
echo "\nAll PWA icons generated successfully!\n";
